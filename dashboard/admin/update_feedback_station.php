<?php
session_start();

include "../../connection.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stationId = intval($_POST['station_id'] ?? 0);
    $feedbackTarget = intval($_POST['feedback_target'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    
    // Validate input
    $errors = [];
    
    if ($stationId <= 0) {
        $errors[] = "Invalid station ID";
    }
    
    if ($feedbackTarget <= 0) {
        $errors[] = "Feedback target must be greater than 0";
    }
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }
    
    // Check if username already exists for other users
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM feedback_users WHERE username = ? AND station_id != ?");
        $stmt->bind_param("si", $username, $stationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Username already exists for another station";
        }
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $errorMsg = implode(', ', $errors);
        header("Location: view_feedback_stations.php?error=" . urlencode($errorMsg));
        exit();
    }
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Update feedback station
        $stmt = $conn->prepare("UPDATE feedback_stations SET feedback_target = ? WHERE id = ?");
        $stmt->bind_param("ii", $feedbackTarget, $stationId);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating station: " . $stmt->error);
        }
        $stmt->close();
        
        // Update or create feedback user
        $stmt = $conn->prepare("SELECT id FROM feedback_users WHERE station_id = ?");
        $stmt->bind_param("i", $stationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing user
            $stmt->close();
            $stmt = $conn->prepare("UPDATE feedback_users SET username = ? WHERE station_id = ?");
            $stmt->bind_param("si", $username, $stationId);
        } else {
            // Create new user (this shouldn't normally happen, but just in case)
            $stmt->close();
            $defaultPassword = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO feedback_users (username, password_hash, station_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $username, $defaultPassword, $stationId);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating user: " . $stmt->error);
        }
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        $successMsg = "Station updated successfully!";
        header("Location: view_feedback_stations.php?message=" . urlencode($successMsg));
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $errorMsg = "Error: " . $e->getMessage();
        header("Location: view_feedback_stations.php?error=" . urlencode($errorMsg));
        exit();
    }
} else {
    // Redirect if not POST request
    header("Location: view_feedback_stations.php");
    exit();
}

$conn->close();
?>
