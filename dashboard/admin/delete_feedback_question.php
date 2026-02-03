<?php
session_start();

include "../../connection.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'delete') {
            // Delete single question
            $questionId = intval($_POST['question_id'] ?? 0);
            
            if ($questionId <= 0) {
                throw new Exception("Invalid question ID");
            }
            
            $stmt = $conn->prepare("DELETE FROM feedback_questions WHERE id = ?");
            $stmt->bind_param("i", $questionId);
            
            if ($stmt->execute()) {
                $successMsg = "Question deleted successfully!";
                header("Location: view_feedback_questions.php?message=" . urlencode($successMsg));
            } else {
                throw new Exception("Error deleting question: " . $stmt->error);
            }
            
            $stmt->close();
            
        } elseif ($action === 'delete_all') {
            // Delete all questions for a station
            $stationId = intval($_POST['station_id'] ?? 0);
            
            if ($stationId <= 0) {
                throw new Exception("Invalid station ID");
            }
            
            $stmt = $conn->prepare("DELETE FROM feedback_questions WHERE station_id = ?");
            $stmt->bind_param("i", $stationId);
            
            if ($stmt->execute()) {
                $deletedCount = $stmt->affected_rows;
                $successMsg = "$deletedCount questions deleted successfully!";
                header("Location: view_feedback_questions.php?message=" . urlencode($successMsg));
            } else {
                throw new Exception("Error deleting questions: " . $stmt->error);
            }
            
            $stmt->close();
            
        } else {
            throw new Exception("Invalid action");
        }
        
    } catch (Exception $e) {
        $errorMsg = "Error: " . $e->getMessage();
        header("Location: view_feedback_questions.php?error=" . urlencode($errorMsg));
    }
} else {
    // Redirect if not POST request
    header("Location: view_feedback_questions.php");
}

$conn->close();
?>
