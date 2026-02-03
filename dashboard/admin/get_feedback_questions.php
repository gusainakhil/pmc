<?php
session_start();

include "../../connection.php";

// Check if station_id is provided
if (!isset($_GET['station_id']) || empty($_GET['station_id'])) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

$stationId = intval($_GET['station_id']);

// Get existing questions for the station
$questions = [];
$query = "SELECT id, question_text FROM feedback_questions WHERE station_id = ? ORDER BY id ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $stationId);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $questions[] = [
            'id' => $row['id'],
            'question_text' => htmlspecialchars($row['question_text'])
        ];
    }
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($questions);
?>
