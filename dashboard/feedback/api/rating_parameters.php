<?php
header("Content-Type: application/json");

include '../connection.php';

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['station_id'])) {
    echo json_encode(["status" => "error", "message" => "station_id is required"]);
    exit;
}

$station_id = $conn->real_escape_string($data['station_id']);

// Fetch rating parameters
$sql = "SELECT rating_name, value FROM rating_parameters WHERE station_id = '$station_id'";
$result = $conn->query($sql);

$response = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $response]);
} else {
    echo json_encode(["status" => "error", "message" => "No ratings found for this station"]);
}

$conn->close();
?>