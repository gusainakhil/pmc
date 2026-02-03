<?php
session_start();

include "../../connection.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $questionId = intval($_POST['question_id'] ?? 0);
    $questionText = trim($_POST['question_text'] ?? '');
    
    // Validate input
    $errors = [];
    
    if ($questionId <= 0) {
        $errors[] = "Invalid question ID";
    }
    
    if (empty($questionText)) {
        $errors[] = "Question text is required";
    } elseif (strlen($questionText) < 5) {
        $errors[] = "Question text must be at least 5 characters";
    }
    
    if (!empty($errors)) {
        $errorMsg = implode(', ', $errors);
        header("Location: view_feedback_questions.php?error=" . urlencode($errorMsg));
        exit();
    }
    
    try {
        // Update feedback question
        $stmt = $conn->prepare("UPDATE feedback_questions SET question_text = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $questionText, $questionId);
        
        if ($stmt->execute()) {
            $successMsg = "Question updated successfully!";
            header("Location: view_feedback_questions.php?message=" . urlencode($successMsg));
        } else {
            throw new Exception("Error updating question: " . $stmt->error);
        }
        
        $stmt->close();
        
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
