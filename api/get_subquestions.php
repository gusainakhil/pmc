<?php
header("Content-Type: application/json");
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database config
$host = "localhost";
$dbname = "beatme_pmc_database";
$username = "beatme_pmc";
$password = "&r(x0xzIuoOS";

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get user_id from query string
    $user_id = $_GET['user_id'] ?? null;

    if (!$user_id) {
        echo json_encode([
            "status" => false,
            "message" => "user_id is required"
        ]);
        exit;
    }

    // Prepare and execute query
    $query = "
        SELECT 
            u.userId, u.OrgID, u.StationId,
            s.db_questionsId,
            q.queId, q.queName, q.subqueId,
            sq.subqueName, sq.subqueType, sq.subqueId AS subquestion_id
        FROM baris_userlogin u
        JOIN baris_station s ON u.OrgID = s.OrgID
        JOIN baris_question q ON s.db_questionsId = q.queId
        JOIN baris_subquestion sq ON FIND_IN_SET(sq.subqueId, q.subqueId)
        WHERE u.userId = ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode([
            "status" => true,
            "data" => $data
        ]);
    } else {
        echo json_encode([
            "status" => false,
            "message" => "No subquestions found for this user"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
}
