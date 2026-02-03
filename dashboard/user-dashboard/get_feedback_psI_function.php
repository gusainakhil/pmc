<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// session_start();
// include 'connection.php';

function calculate_feedback_psi_stationwise($conn, $station_id, $from_date, $to_date)




{
    if (!$station_id) {
        throw new Exception("Station ID is required");
    }

    $from_date = $_GET['from'] ?? $_POST['from'] ?? date('Y-m-01');
    $to_date = $_GET['to'] ?? $_POST['to'] ?? date('Y-m-d');

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
    // current date range
    $from_date = date('Y-m-d', strtotime($from_date));
    $to_date = date('Y-m-d', strtotime($to_date));

    $stmt_feedback = $conn->prepare("
        SELECT GROUP_CONCAT(CONCAT(fa.question_id, ':', fa.rating)) AS question_ratings
        FROM feedback_form ff
        LEFT JOIN feedback_answers fa ON ff.id = fa.feedback_form_id
        WHERE ff.station_id = ?
        AND DATE(ff.created_at) BETWEEN ? AND ?
        GROUP BY ff.id
    ");
    $stmt_feedback->bind_param("iss", $station_id, $from_date, $to_date);
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
    $start = new DateTime($from_date);
    $end = new DateTime($to_date);
    $end->modify('+1 day');
    $interval = new DateInterval('P1D');
    $total_days = iterator_count(new DatePeriod($start, $interval, $end));

    $expected_feedbacks = $total_days * $daily_target;
    $avg_score = $total_feedbacks > 0 ? $total_score_sum / $total_feedbacks : 0;
    $quality_psi = ($avg_score / $max_rating_score) * 100;
    $quantity_achievement = $expected_feedbacks > 0 ? ($total_feedbacks / $expected_feedbacks) : 0;
    $psi = $quality_psi * $quantity_achievement;

    return [
        'station_name' => $station_name,
        'psi' => round($psi, 2),
        'quality_psi' => round($quality_psi, 2),
        'quantity_achievement' => round($quantity_achievement * 100, 2),
        'total_feedbacks' => $total_feedbacks,
        'expected_feedbacks' => $expected_feedbacks,
        'average_score' => round($avg_score, 2),
        'max_rating_score' => $max_rating_score
    ];
}

// Example usage:
// try {
//     $station_id = 35;
//     $from_date = $_GET['from'] ?? $_POST['from'] ?? date('Y-m-01');
//     $to_date = $_GET['to'] ?? $_POST['to'] ?? date('Y-m-d');

//     $result = calculate_feedback_psi($conn, $station_id, $from_date, $to_date);

//     echo $result['station_name'] . ' - ' . $result['psi'] . "%\n";
// } catch (Exception $e) {
//     echo "Error: " . $e->getMessage() . "\n";
// } finally {
//     $conn->close();
// }



function daily_surprise_visit_stationwise($conn, $station_id, $firstDay, $lastDay) {
    $sql = "
        SELECT 
            SUM(bas.db_surveyValue) AS total_score,
            COUNT(bas.db_surveyValue) AS total_records,
            brw.weightage 
        FROM baris_param bap
        INNER JOIN baris_survey bas ON bap.paramId = bas.db_surveyParamId
        INNER JOIN baris_page bp ON bas.db_surveyPageId = bp.pageId
        INNER JOIN baris_report_weight brw ON bas.db_surveySubQuestionId = brw.subqueId
        WHERE bas.db_surveyStationId = '$station_id' 
          AND DATE(bas.created_date) BETWEEN '$firstDay' AND '$lastDay'
    ";

    $result = $conn->query($sql);

    if ($result && $data = $result->fetch_assoc()) {
        $total_records = $data['total_records'] ?? 0;
        $total_score = $data['total_score'] ?? 0;
        $weightage = $data['weightage'] ?? 0;

        $score_percent = $total_records > 0 
            ? round(($total_score / ($total_records * 10)) * 100, 2) 
            : 0;

        return [
            'score_percent' => $score_percent,
            'weightage' => $weightage
        ];
    }

    // Return default values if query fails
    return [
        'score_percent' => 0,
        'weightage' => 0
    ];
}


function cleanliness_score_station_wise_new($stationId, $OrgID, $squeld, $month, $year, $conn) {
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
        $total += $fs * floatval($r['Percentage_Weightage']) / 100;
    }

    // Ensure score never exceeds 100
    return number_format(min($total, 100), 2);
}

?>
