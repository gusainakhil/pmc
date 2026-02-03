<?php
session_start();
include 'connection.php';


// User data
$user_id = $_SESSION['userId'];
$division_id = 53; // Get division ID directly from session

// Get current month dates
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-d');

// Fetch all stations in the division
$sql_stations = "SELECT id, name, feedback_target FROM feedback_stations WHERE division = ?";
$stmt_stations = $conn->prepare($sql_stations);
$stmt_stations->bind_param("i", $division_id);
$stmt_stations->execute();
$result_stations = $stmt_stations->get_result();

$division_stations = [];
$total_daily_target = 0;
$division_name = "Division " . $division_id;

while ($station = $result_stations->fetch_assoc()) {
    $division_stations[] = $station;
    $total_daily_target += (int) ($station['feedback_target'] ?? 0);
}
$stmt_stations->close();

if (empty($division_stations)) {
    echo "<h2>No stations found in Division " . htmlspecialchars($division_id) . "</h2>";
    exit;
}

// Fetch rating parameters for dynamic maximum score
$first_station_id = $division_stations[0]['id'];
$sql_rating_params = "SELECT value FROM rating_parameters WHERE station_id = ?";
$stmt_rating_params = $conn->prepare($sql_rating_params);
$stmt_rating_params->bind_param("i", $first_station_id);
$stmt_rating_params->execute();
$result_rating_params = $stmt_rating_params->get_result();
$rating_data = $result_rating_params->fetch_assoc();
$max_rating_score = (int) ($rating_data['value'] ?? 3);
$stmt_rating_params->close();

// Prepare station IDs for SQL
$station_ids = array_column($division_stations, 'id');
$station_ids_placeholder = str_repeat('?,', count($station_ids) - 1) . '?';

// Fetch feedback data
$sql_feedback = "
    SELECT 
        ff.id AS form_id, 
        ff.station_id,
        ff.created_at,
        GROUP_CONCAT(CONCAT(fa.question_id, ':', fa.rating)) AS question_ratings
    FROM feedback_form ff
    LEFT JOIN feedback_answers fa ON ff.id = fa.feedback_form_id
    WHERE ff.station_id IN ($station_ids_placeholder)
    AND DATE(ff.created_at) BETWEEN ? AND ?
    GROUP BY ff.id
    ORDER BY ff.created_at DESC
";

$stmt_feedback = $conn->prepare($sql_feedback);
$types = str_repeat('i', count($station_ids)) . 'ss';
$params = array_merge($station_ids, [$current_month_start, $current_month_end]);
$stmt_feedback->bind_param($types, ...$params);
$stmt_feedback->execute();
$feedback_data = $stmt_feedback->get_result();

// Initialize station wise data
$total_feedbacks = 0;
$total_score_sum = 0;
$station_wise_data = [];
foreach ($division_stations as $station) {
    $station_wise_data[$station['id']] = [
        'name' => $station['name'],
        'target' => $station['feedback_target'],
        'feedbacks' => 0,
        'score_sum' => 0
    ];
}

// Process feedback
while ($row = $feedback_data->fetch_assoc()) {
    $overall_score = 0;
    $rating_count = 0;
    if (!empty($row['question_ratings'])) {
        foreach (explode(',', $row['question_ratings']) as $rating_data) {
            [$question_id, $rating] = explode(':', $rating_data);
            $overall_score += (int) $rating;
            $rating_count++;
        }
    }
    $individual_score = $rating_count > 0 ? $overall_score / $rating_count : 0;
    $total_score_sum += $individual_score;
    $total_feedbacks++;

    if (isset($station_wise_data[$row['station_id']])) {
        $station_wise_data[$row['station_id']]['feedbacks']++;
        $station_wise_data[$row['station_id']]['score_sum'] += $individual_score;
    }
}

// Calculate PSI
$current_date = new DateTime();
$start_date = new DateTime($current_month_start);
$total_days_so_far = $current_date->diff($start_date)->days + 1;

$average_total_score = $total_feedbacks > 0 ? $total_score_sum / $total_feedbacks : 0;
$quality_psi = ($average_total_score / $max_rating_score) * 100;

$total_expected_feedbacks = $total_days_so_far * $total_daily_target;
$quantity_achievement = $total_expected_feedbacks > 0 ? ($total_feedbacks / $total_expected_feedbacks) : 0;

$adjusted_psi_percentage = $quality_psi * $quantity_achievement;

$stmt_feedback->close();
$conn->close();


echo "<h2>" . htmlspecialchars($division_name) . " - " . date('F Y') . "</h2>";
echo "<p>Date: " . date('F j, Y') . "</p>";
echo "<p><strong>Division PSI:</strong> " . number_format($adjusted_psi_percentage, 1) . "%</p>";

echo "<h3>Station-wise PSI:</h3>";
echo "<ul>";
foreach ($station_wise_data as $station_id => $station_data) {
    $station_avg_score = $station_data['feedbacks'] > 0 ? $station_data['score_sum'] / $station_data['feedbacks'] : 0;
    $station_quality_psi = ($station_avg_score / $max_rating_score) * 100;
    $station_expected = $total_days_so_far * $station_data['target'];
    $station_quantity_achievement = $station_expected > 0 ? ($station_data['feedbacks'] / $station_expected) : 0;
    $station_psi = $station_quality_psi * $station_quantity_achievement;

    echo "<li>" . htmlspecialchars($station_data['name']) . " - " . number_format($station_psi, 1) . "%</li>";
}
echo "</ul>";
?>
