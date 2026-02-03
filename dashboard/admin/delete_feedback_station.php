<?php
session_start();

include "../../connection.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $stationId = intval($_POST['station_id'] ?? 0);
    
    if ($stationId > 0) {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Delete the feedback user first (foreign key constraint)
            $stmt = $conn->prepare("DELETE FROM feedback_users WHERE station_id = ?");
            $stmt->bind_param("i", $stationId);
            $stmt->execute();
            $stmt->close();
            
            // Delete the feedback station
            $stmt = $conn->prepare("DELETE FROM feedback_stations WHERE id = ?");
            $stmt->bind_param("i", $stationId);
            $stmt->execute();
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Redirect with success message
            header("Location: view_feedback_stations.php?message=Station deleted successfully");
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            
            // Redirect with error message
            header("Location: view_feedback_stations.php?error=" . urlencode("Error deleting station: " . $e->getMessage()));
            exit;
        }
    } else {
        header("Location: view_feedback_stations.php?error=Invalid station ID");
        exit;
    }
} else {
    // If not a valid POST request, redirect to view page
    header("Location: view_feedback_stations.php");
    exit;
}
?>
