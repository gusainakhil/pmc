<?php
header("Content-Type: application/json");

include '../connection.php';

try {
   

    // Check connection
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database connection failed"]);
        exit();
    }

    // Get input JSON
    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['station_id'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing station_id"]);
        exit();
    }

    $station_id = intval($data['station_id']);
    $stmt = $conn->prepare("SELECT id, question_text FROM feedback_questions WHERE station_id = ?");
    $stmt->bind_param("i", $station_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $questions = [];
        while ($row = $result->fetch_assoc()) {
            $questions[] = ["parameter_id" => $row["id"], "parameter_name" => $row["question_text"]];
        }
        http_response_code(200);
        echo json_encode(["status" => "success", "questions" => $questions]);
    } else {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "No questions found"]);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
