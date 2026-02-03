<?php
require_once './config/database.php';
require_once './models/QuestionsModel.php';

class QuestionsController {
    public function handleRequest() {
        header("Content-Type: application/json");

        try {
            // Use your Database class
            $db = new Database();
            $pdo = $db->connect();

            $user_id = $_GET['user_id'] ?? null;

            if (!$user_id) {
                echo json_encode([
                    "status" => false,
                    "message" => "user_id is required"
                ]);
                exit;
            }

            $model = new QuestionsModel($pdo);
            $data = $model->getQuestionsByUserId($user_id);

            if ($data) {
                echo json_encode([
                    "status" => true,
                    "data" => $data
                ]);
            } else {
                echo json_encode([
                    "status" => false,
                    "message" => "No questions found for this user"
                ]);
            }

        } catch (PDOException $e) {
            echo json_encode([
                "status" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
    }
}
