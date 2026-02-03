<?php

$host = "localhost";
$dbname = "beatme_pmc_database";
$dbuser = "beatme_pmc";
$dbpass = "&r(x0xzIuoOS";

header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Create PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Accept POST or GET
    $username  = $_POST['username'] ?? $_GET['username'] ?? null;
    $password  = $_POST['password'] ?? $_GET['password'] ?? null;
    $stationId = $_POST['station_id'] ?? $_GET['station_id'] ?? null;

    if (!$username || !$password || !$stationId) {
        echo json_encode([
            "status" => false,
            "message" => "username, password, and station_id are required"
        ]);
        exit;
    }

    // Fetch user by username, station and usertype
    $stmt = $pdo->prepare("SELECT * FROM baris_userlogin WHERE db_username = ? AND StationId = ? AND db_usertype = ?");
    $stmt->execute([$username, $stationId, 'auditor']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Verify password (if stored using password_hash)
        if (password_verify($password, $user['db_password'])) {
            echo json_encode([
                "status" => true,
                "message" => "Login successful",
                "user" => [
                    "id" => $user['userId'],
                    "username" => $user['db_username'],
                    "station_id" => $user['StationId']
                ]
            ]);
        } else {
            echo json_encode([
                "status" => false,
                "message" => "Invalid password"
            ]);
        }
    } else {
        echo json_encode([
            "status" => false,
            "message" => "Invalid credentials"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>