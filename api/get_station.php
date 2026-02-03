<?php
// Show all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set response to JSON
header("Content-Type: application/json");

// Database credentials
//require_once __DIR__ . 'config/db.php';
 include"../connection.php";

try {
    // DB Connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get input parameter
    $station_id = $_GET['station_id'] ?? null;

    if (!$station_id) {
        echo json_encode([
            "status" => false,
            "message" => "station_id is required"
        ]);
        exit;
    }

    // Prepare and execute query
    $stmt = $pdo->prepare("SELECT stationName FROM baris_station WHERE stationId = ?");
    $stmt->execute([$station_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode([
            "status" => true,
            "stationName" => $row['stationName']
        ]);
    } else {
        echo json_encode([
            "status" => false,
            "message" => "Station not found"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
