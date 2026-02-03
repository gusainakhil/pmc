<?php
session_start();
include 'connection.php';

// User data
$user_id = $_SESSION['userId'];
$division_id = 53; // Get division ID directly from session

// Get current month dates
$current_month_start = date('Y-m-01'); // First day of current month
$current_month_end = date('Y-m-t');    // Last day of current month

// Fetch all stations in the division with their details
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

// Check if there are stations in the division
if (empty($division_stations)) {
    // If no stations found in division, show error message
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Division PSI - Error</title></head><body>";
    echo "<div style='text-align: center; margin-top: 50px; font-family: Arial, sans-serif;'>";
    echo "<h2>No stations found in Division " . htmlspecialchars($division_id) . "</h2>";
    echo "<p>Please check if the division ID is correct or if stations are assigned to this division.</p>";
    echo "</div></body></html>";
    exit;
}

// Fetch rating parameters for dynamic maximum score (using first station in division)
$first_station_id = $division_stations[0]['id'];
$sql_rating_params = "SELECT value FROM rating_parameters WHERE station_id = ?";
$stmt_rating_params = $conn->prepare($sql_rating_params);
$stmt_rating_params->bind_param("i", $first_station_id);
$stmt_rating_params->execute();
$result_rating_params = $stmt_rating_params->get_result();
$rating_data = $result_rating_params->fetch_assoc();
$max_rating_score = (int) ($rating_data['value'] ?? 3); // Default to 3 if not found
$stmt_rating_params->close();

// Create station IDs string for SQL IN clause
$station_ids = array_column($division_stations, 'id');
$station_ids_placeholder = str_repeat('?,', count($station_ids) - 1) . '?';

// Fetch feedback data for all stations in the division for current month
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

// Calculate PSI metrics
$total_feedbacks = 0;
$total_score_sum = 0;
$station_wise_data = [];

// Initialize station wise data
foreach ($division_stations as $station) {
    $station_wise_data[$station['id']] = [
        'name' => $station['name'],
        'target' => $station['feedback_target'],
        'feedbacks' => 0,
        'score_sum' => 0
    ];
}

// Process feedback data
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
    
    // Add to station wise data
    if (isset($station_wise_data[$row['station_id']])) {
        $station_wise_data[$row['station_id']]['feedbacks']++;
        $station_wise_data[$row['station_id']]['score_sum'] += $individual_score;
    }
}

// Calculate total days in current month
$total_days_in_month = date('t'); // Number of days in current month

// Calculate PSI metrics
$average_total_score = $total_feedbacks > 0 ? $total_score_sum / $total_feedbacks : 0;
$quality_psi = ($average_total_score / $max_rating_score) * 100;

// Calculate total expected feedbacks for the month (all stations in division)
$total_expected_feedbacks = $total_days_in_month * $total_daily_target;
$quantity_achievement = $total_expected_feedbacks > 0 ? ($total_feedbacks / $total_expected_feedbacks) : 0;

// Adjusted PSI = Quality PSI × Quantity Achievement
$adjusted_psi_percentage = $quality_psi * $quantity_achievement;

$stmt_feedback->close();
$conn->close();

// Display results
echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<title>Monthly PSI Count - " . date('F Y') . "</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 40px; background-color: #f5f5f5; }";
echo ".container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }";
echo ".header { text-align: center; margin-bottom: 30px; color: #333; }";
echo ".metric { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; border-radius: 4px; }";
echo ".metric-title { font-weight: bold; color: #555; margin-bottom: 5px; }";
echo ".metric-value { font-size: 1.2em; color: #007bff; font-weight: bold; }";
echo ".psi-value { font-size: 2em; color: #28a745; text-align: center; padding: 20px; background: #e8f5e8; border-radius: 8px; margin: 20px 0; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>Monthly PSI Count - Division Level</h1>";
echo "<h2>" . htmlspecialchars($division_name) . "</h2>";
echo "<p>" . date('F Y') . " (" . $current_month_start . " to " . $current_month_end . ")</p>";
echo "<p><strong>Stations in Division:</strong> " . count($division_stations) . "</p>";
echo "</div>";

// Display division-wide metrics
echo "<div class='metric'>";
echo "<div class='metric-title'>Total Feedbacks Collected (Division):</div>";
echo "<div class='metric-value'>" . $total_feedbacks . "</div>";
echo "</div>";

echo "<div class='metric'>";
echo "<div class='metric-title'>Expected Feedbacks (Division Target):</div>";
echo "<div class='metric-value'>" . $total_expected_feedbacks . "</div>";
echo "</div>";

echo "<div class='metric'>";
echo "<div class='metric-title'>Daily Target (All Stations Combined):</div>";
echo "<div class='metric-value'>" . $total_daily_target . " feedbacks per day</div>";
echo "</div>";

echo "<div class='metric'>";
echo "<div class='metric-title'>Total Days in Month:</div>";
echo "<div class='metric-value'>" . $total_days_in_month . " days</div>";
echo "</div>";

echo "<div class='metric'>";
echo "<div class='metric-title'>Average Quality Score:</div>";
echo "<div class='metric-value'>" . number_format($average_total_score, 2) . " / " . $max_rating_score . " (" . number_format($quality_psi, 1) . "%)</div>";
echo "</div>";

echo "<div class='metric'>";
echo "<div class='metric-title'>Quantity Achievement:</div>";
echo "<div class='metric-value'>" . number_format($quantity_achievement * 100, 1) . "%</div>";
echo "</div>";

echo "<div class='psi-value'>";
echo "<strong>Division PSI: " . number_format($adjusted_psi_percentage, 1) . "%</strong>";
echo "</div>";

// PSI Rating
if ($adjusted_psi_percentage >= 80) {
    $rating = "Excellent";
    $color = "#28a745";
} elseif ($adjusted_psi_percentage >= 60) {
    $rating = "Good";
    $color = "#ffc107";
} elseif ($adjusted_psi_percentage >= 40) {
    $rating = "Average";
    $color = "#fd7e14";
} else {
    $rating = "Needs Improvement";
    $color = "#dc3545";
}

echo "<div style='text-align: center; padding: 15px; background: " . $color . "; color: white; border-radius: 8px; margin-top: 20px;'>";
echo "<strong>Division PSI Rating: " . $rating . "</strong>";
echo "</div>";

// Station-wise breakdown
echo "<div style='margin-top: 30px;'>";
echo "<h3>Station-wise Breakdown:</h3>";
foreach ($station_wise_data as $station_id => $station_data) {
    $station_avg_score = $station_data['feedbacks'] > 0 ? $station_data['score_sum'] / $station_data['feedbacks'] : 0;
    $station_quality_psi = ($station_avg_score / $max_rating_score) * 100;
    $station_expected = $total_days_in_month * $station_data['target'];
    $station_quantity_achievement = $station_expected > 0 ? ($station_data['feedbacks'] / $station_expected) : 0;
    $station_psi = $station_quality_psi * $station_quantity_achievement;
    
    $station_color = $station_psi >= 80 ? "#28a745" : ($station_psi >= 60 ? "#ffc107" : "#dc3545");
    
    echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid " . $station_color . "; border-radius: 4px;'>";
    echo "<strong>" . htmlspecialchars($station_data['name']) . "</strong><br>";
    echo "Feedbacks: " . $station_data['feedbacks'] . " / " . $station_expected . " (Target: " . $station_data['target'] . "/day)<br>";
    echo "Quality: " . number_format($station_quality_psi, 1) . "% | Quantity: " . number_format($station_quantity_achievement * 100, 1) . "%<br>";
    echo "<strong>Station PSI: " . number_format($station_psi, 1) . "%</strong>";
    echo "</div>";
}
echo "</div>";

echo "<div style='margin-top: 30px; padding: 15px; background: #e9ecef; border-radius: 8px; font-size: 0.9em;'>";
echo "<strong>PSI Calculation Formula:</strong><br>";
echo "PSI = Quality Score (%) × Quantity Achievement (%)<br>";
echo "Quality Score = (Average Rating / Maximum Rating) × 100<br>";
echo "Quantity Achievement = (Actual Feedbacks / Expected Feedbacks) × 100";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
