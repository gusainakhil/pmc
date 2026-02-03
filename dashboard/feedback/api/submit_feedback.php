<?php
header("Content-Type: application/json");
include '../connection.php';

// Get the JSON data from the request body
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

if (!isset($data['question_ids'], $data['ratingValues'], $data['passengerMobile'], $data['passengerName'], $data['platformNumber'], $data['pnr'], $data['supervisorName'], $data['stationId'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);
try {
    // Insert into feedback_form table
    $query = "INSERT INTO feedback_form (supervisor_name, platform_no, passenger_name, passenger_mobile, pnr_number, station_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ssssss', $data['supervisorName'], $data['platformNumber'], $data['passengerName'], $data['passengerMobile'], $data['pnr'], $data['stationId']);
    mysqli_stmt_execute($stmt);

    // Get the last inserted feedback_form ID
    $feedback_form_id = mysqli_insert_id($conn);

    // Insert into feedback_answers table
    $query = "INSERT INTO feedback_answers (feedback_form_id, question_id, rating) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);

    foreach ($data['question_ids'] as $index => $question_id) {
        $rating = $data['ratingValues'][$index];
        mysqli_stmt_bind_param($stmt, 'iii', $feedback_form_id, $question_id, $rating);
        mysqli_stmt_execute($stmt);
    }

    // Commit the transaction
    mysqli_commit($conn);

    echo json_encode(['status' => 'success', 'message' => 'Data inserted successfully']);
} catch (Exception $e) {
    // Rollback the transaction on error
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => 'Data insertion failed: ' . $e->getMessage()]);
}

// Close the database connection
mysqli_close($conn);
