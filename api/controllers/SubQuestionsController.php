<?php
require_once './config/database.php';
require_once './models/SubQuestionsModel.php';

class SubQuestionsController {
    public function handleRequest() {
        header("Content-Type: application/json");

        try {
            $db = new Database();
            $pdo = $db->connect();

            $user_id = $_GET['user_id'] ?? null;

            if (!$user_id) {
                echo json_encode([
                    "status" => false,
                    "message" => "user_id is required"
                ]);
                return;
            }

            $model = new SubQuestionsModel($pdo);
            $data = $model->getSubquestionsByUserId($user_id);

            echo json_encode([
                "status" => true,
                "data" => $data
            ]);

        } catch (PDOException $e) {
            echo json_encode([
                "status" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
        }
    }
}
