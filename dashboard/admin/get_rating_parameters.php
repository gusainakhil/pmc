<?php
session_start();

// Include database connection
include "../../connection.php";

// Get station ID from GET parameter
$stationId = isset($_GET['station_id']) ? intval($_GET['station_id']) : 0;

if ($stationId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid station ID']);
    exit;
}

try {
    // Prepare and execute query to get rating parameters for the station
    $stmt = $conn->prepare("SELECT id, rating_name, value FROM rating_parameters WHERE station_id = ? ORDER BY value DESC");
    $stmt->bind_param("i", $stationId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $parameters = [];
    
    while ($row = $result->fetch_assoc()) {
        $parameters[] = [
            'id' => $row['id'],
            'rating_name' => htmlspecialchars($row['rating_name']),
            'value' => $row['value']
        ];
    }
    
    $stmt->close();
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($parameters);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching parameters: ' . $e->getMessage()]);
}

$conn->close();
?>
