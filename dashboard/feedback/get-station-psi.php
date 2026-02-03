<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
session_start();
include 'connection.php';

function calculate_feedback_psi($conn, $station_id, $from_date, $to_date)
{
    if (!$station_id) {
        throw new Exception("Station ID is required");
    }

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
try {
    $station_id = 35;
    $from_date = $_GET['from'] ?? $_POST['from'] ?? date('Y-m-01');
    $to_date = $_GET['to'] ?? $_POST['to'] ?? date('Y-m-d');

    $result = calculate_feedback_psi($conn, $station_id, $from_date, $to_date);

    echo $result['station_name'] . ' - ' . $result['psi'] . "%\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    $conn->close();
}
?>
