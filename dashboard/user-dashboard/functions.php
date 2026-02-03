<?php
include '../../connection.php';

function cleanliness_score_station_wise($stationId, $OrgID, $squeld, $month, $year, $conn) {
    $start = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
    $end = date("Y-m-t", strtotime($start));

    // Fetch achievements
    $achievement_sql = "
        SELECT dpl.db_surveyPageId,
               SUBSTRING(bpage.db_pageChoice2, INSTR(bpage.db_pageChoice2,'@')+1) AS Percentage_Weightage,
               SUM(CASE WHEN bp.paramName='Shift 1' THEN dpl.db_surveyValue ELSE 0 END) AS s1,
               SUM(CASE WHEN bp.paramName='Shift 2' THEN dpl.db_surveyValue ELSE 0 END) AS s2,
               SUM(CASE WHEN bp.paramName='Shift 3' THEN dpl.db_surveyValue ELSE 0 END) AS s3
        FROM Daily_Performance_Log dpl
        JOIN baris_param bp ON dpl.db_surveyParamId=bp.paramId
        JOIN baris_page bpage ON dpl.db_surveyPageId=bpage.pageId
        WHERE dpl.db_surveyStationId='$stationId' AND dpl.created_date BETWEEN '$start' AND '$end'
        GROUP BY dpl.db_surveyPageId";
    $res = $conn->query($achievement_sql);

    // Fetch targets
    $t_res = $conn->query("
        SELECT pageId,
               SUBSTRING_INDEX(value, ',',1) AS t1,
               SUBSTRING_INDEX(SUBSTRING_INDEX(value,',',2),',',-1) AS t2,
               SUBSTRING_INDEX(SUBSTRING_INDEX(value,',',3),',',-1) AS t3
        FROM baris_target
        WHERE OrgID='$OrgID' AND month='$month' AND subqueId='$squeld'
        ORDER BY id DESC LIMIT 24");

    $targets = [];
    while($t = $t_res->fetch_assoc()) {
        $targets[$t['pageId']] = $t;
    }

    // Calculate total
    $total = 0;
    while($r = $res->fetch_assoc()) {
        $t = $targets[$r['db_surveyPageId']] ?? ['t1'=>0,'t2'=>0,'t3'=>0];
        foreach(['1','2','3'] as $i) if($t["t$i"]==0) $r["s$i"]=0;
        $t_sum = $t['t1'] + $t['t2'] + $t['t3'];
        $a_sum = $r['s1'] + $r['s2'] + $r['s3'];
        $fs = ($t_sum > 0) ? ($a_sum / $t_sum) * 100 : 0;
        if ($fs > 100) $fs = 100; // Cap at 100%
        $total += $fs * floatval($r['Percentage_Weightage']) / 100;
    }

    return number_format($total > 100 ? 100 : $total, 2); // Cap final score at 100%
}

// ====== CALL FUNCTION ======
$stationId = $_SESSION['stationId'];
$OrgID = $_SESSION['OrgID'];
if (isset($_GET['id'])) $_SESSION['squeld'] = $_GET['id'];
$squeld = $_SESSION['daily_performance'];
$month = $_GET['month'] ?? date('n');
$year = $_GET['year'] ?? date('Y');

// echo "Monthly Final Score: " . cleanliness_score_station_wise($stationId, $OrgID, $squeld, $month, $year, $conn);

function calculate_feedback_psi($conn, $station_id, $firstDay, $lastDay) {
    // Print station id
    // echo "Station ID: $station_id\n";

    try {
        // Fetch station
        $stmt_station = $conn->prepare("SELECT name, feedback_target FROM feedback_stations WHERE id = ?");
        $stmt_station->bind_param("i", $station_id);
        $stmt_station->execute();
        $station_result = $stmt_station->get_result();
        $station = $station_result->fetch_assoc();
        $stmt_station->close();

        if (!$station) {
            throw new Exception("Station not found");
        }

        $station_name = $station['name'];
        $daily_target = (int)($station['feedback_target'] ?? 0);

        // Get max rating score
        $stmt_max = $conn->prepare("SELECT value FROM rating_parameters WHERE station_id = ?");
        $stmt_max->bind_param("i", $station_id);
        $stmt_max->execute();
        $max_result = $stmt_max->get_result();
        $max_rating_score = (int)($max_result->fetch_assoc()['value'] ?? 3);
        $stmt_max->close();

        // Fetch feedback
        $stmt_feedback = $conn->prepare("
            SELECT GROUP_CONCAT(CONCAT(fa.question_id, ':', fa.rating)) AS question_ratings
            FROM feedback_form ff
            LEFT JOIN feedback_answers fa ON ff.id = fa.feedback_form_id
            WHERE ff.station_id = ?
            AND DATE(ff.created_at) BETWEEN ? AND ?
            GROUP BY ff.id
        ");
        $stmt_feedback->bind_param("iss", $station_id, $firstDay, $lastDay);
        $stmt_feedback->execute();
        $feedback_result = $stmt_feedback->get_result();

        $total_feedbacks = 0;
        $total_score_sum = 0;

        while ($row = $feedback_result->fetch_assoc()) {
            $ratings = explode(',', $row['question_ratings']);
            $sum = 0;
            $count = 0;
            foreach ($ratings as $rating_pair) {
                [$q, $r] = explode(':', $rating_pair);
                $sum += (int)$r;
                $count++;
            }
            if ($count > 0) {
                $avg = $sum / $count;
                $total_score_sum += $avg;
                $total_feedbacks++;
            }
        }

        $stmt_feedback->close();

        // PSI Calculation
        $start = new DateTime($firstDay);
        $end = new DateTime($lastDay);
        $end->modify('+1 day');
        $interval = new DateInterval('P1D');
        $total_days = iterator_count(new DatePeriod($start, $interval, $end));

        $expected_feedbacks = $total_days * $daily_target;
        $avg_score = $total_feedbacks > 0 ? $total_score_sum / $total_feedbacks : 0;
        $quality_psi = ($avg_score / $max_rating_score) * 100;
        $quantity_achievement = $expected_feedbacks > 0 ? ($total_feedbacks / $expected_feedbacks) : 0;
        $psi = $quality_psi * $quantity_achievement;

        // Final Output
        // echo $station_name . ' - ' . number_format($psi, 2) . "%\n";
        return number_format($psi,  2)."%";

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Example call:
// $station_id = 33;

// $conn already declared outside
// calculate_feedback_psi($conn, 33, $firstDay, $lastDay);





///this is for daily surprise visit report station wise data 
function calculate_daily_surprise_visit($conn, $station_id, $selectedMonth = null, $selectedYear = null) {
    // Default month and year
    $selectedMonth = $selectedMonth ?: date('m');
    $selectedYear = $selectedYear ?: date('Y');

    $startDate = "$selectedYear-$selectedMonth-01";
    $endDate = date("Y-m-t 23:59:59", strtotime($startDate));

    $sql = "
        SELECT
            bas.db_surveyValue AS Quality_of_done_work,
            DATE(bas.created_date) AS report_date
        FROM
            baris_survey bas
        WHERE
            bas.db_surveyStationId = '$station_id'
            AND bas.created_date >= '$startDate'
            AND bas.created_date <= '$endDate'
    ";

    $result = $conn->query($sql);

    // Prepare data
    $inspections = [];

    while ($row = $result->fetch_assoc()) {
        $reportDate = $row['report_date'];
        if (!isset($inspections[$reportDate])) {
            $inspections[$reportDate] = [
                'count' => 0,
                'total' => 0,
                'score' => 0
            ];
        }

        $inspections[$reportDate]['count'] += 1;
        $inspections[$reportDate]['total'] = $inspections[$reportDate]['count'] * 10;
        $inspections[$reportDate]['score'] += $row['Quality_of_done_work'];
    }

    // Calculate overall
    $totalScore = array_sum(array_column($inspections, 'score'));
    $maximumPossibleScore = array_sum(array_column($inspections, 'total'));
    $overallAverage = ($maximumPossibleScore > 0) ? round(($totalScore / $maximumPossibleScore) * 100, 2) : 0;

    return $overallAverage;
}

// Call function
$station_id = $_SESSION['stationId'];
$average = calculate_daily_surprise_visit($conn, $station_id);

// Print in HTML
// echo "<p>Overall Average: $average%</p>";

?>

