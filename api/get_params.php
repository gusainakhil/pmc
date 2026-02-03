<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once 'config/db.php'; // Adjust path as needed

// Step 1: Get paramId from request
$paramId = isset($_GET['paramId']) ? $_GET['paramId'] : null;

// Initialize response
$response = ["data" => []];

if ($paramId) {
    try {
        // Step 2: Connect to DB
        $database = new Database();
        $conn = $database->getConnection();

        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        // Step 3: Prepare and run query
        $stmt = $conn->prepare("
            SELECT bs.paramName, bp.db_pagename, bp.db_pageChoice ,bp.pageId
            FROM baris_param as bs
            JOIN baris_page as bp ON FIND_IN_SET(bp.pageId, bs.db_pagesId)
            WHERE bs.paramId = :paramId
        ");
        $stmt->bindParam(':paramId', $paramId, PDO::PARAM_STR);
        $stmt->execute();

        // Step 4: Loop through results
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $response["data"][] = [
                  "pageId"     => $row["pageId"],
                "paramName"     => $row["paramName"],
                "db_pagename"   => $row["db_pagename"],
                "db_pageChoice" => $row["db_pageChoice"]
            ];
        }

    } catch (Exception $e) {
        // In production you can log the error and send a friendly message
        $response["error"] = "Something went wrong. Please try again later.";
        // For debugging (remove/comment out in production):
        $response["debug"] = $e->getMessage();
    }
} else {
    $response["error"] = "paramId is required";
}

// Step 5: Return JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
