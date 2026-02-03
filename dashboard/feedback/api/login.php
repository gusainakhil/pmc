<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Origin: *");

include '../connection.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["message" => "DB connection failed", "error" => $conn->connect_error]);
    exit();
}

$inputData = json_decode(file_get_contents("php://input"), true);

if (!isset($inputData['username'], $inputData['password'])) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid request. Username and password required."]);
    exit();
}

$username = $conn->real_escape_string(trim($inputData['username']));
$password = trim($inputData['password']);

$query = "SELECT fu.username,fs.otp_status, fu.password_hash, fu.station_id, fs.name
FROM feedback_users fu
LEFT JOIN feedback_stations fs ON fu.station_id = fs.id
WHERE fu.username = ?";

if (!$stmt = $conn->prepare($query)) {
    http_response_code(500);
    echo json_encode(["message" => "Query prepare failed", "error" => $conn->error]);
    exit();
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password_hash'])) {
        http_response_code(200);
        echo json_encode([
            "station_id" => $user['station_id'],
            "username" => $user['username'],
            "station_name" => $user['name'] ?? "Unknown",
            "otp_status" => $user['otp_status']
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Invalid password."]);
    }
} else {
    http_response_code(404);
    echo json_encode(["message" => "User not found."]);
}

$stmt->close();
$conn->close();
?>
