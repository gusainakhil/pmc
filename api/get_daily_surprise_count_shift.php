<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php'; // adjust path

$database = new Database();
$conn = $database->getConnection();

$today = date('Y-m-d');

// Get stationId and paramId from request (GET or POST)
$stationId = isset($_REQUEST['stationId']) ? (int)$_REQUEST['stationId'] : null;
$paramId   = isset($_REQUEST['paramId']) ? (int)$_REQUEST['paramId'] : null;
$tokenId   = isset($_REQUEST['tokenid']) ? trim($_REQUEST['tokenid']) : null;

// Check required fields
if (!$stationId || !$paramId) {
    echo json_encode([
        "error" => "Missing required parameters: stationId and paramId"
    ]);
    exit;
}

if (!$tokenId) {
    echo json_encode([
        "error" => "Missing tokenid"
    ]);
    exit;
}

try {
    $sql = "SELECT COUNT(*) as total 
            FROM `baris_survey` 
            WHERE DATE(created_date) = :today
              AND db_surveyStationId = :stationId
              AND db_surveyParamId = :paramId
              AND tokenid = :tokenId";
              

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':today', $today);
    $stmt->bindParam(':stationId', $stationId, PDO::PARAM_INT);
    $stmt->bindParam(':paramId', $paramId, PDO::PARAM_INT);
    $stmt->bindParam(':tokenId', $tokenId, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "date" => $today,
        "stationId" => $stationId,
        "paramId" => $paramId,
        "count" => $row['total']
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
