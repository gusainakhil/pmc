<?php
require_once './config/database.php';
require_once './models/ParametersModel.php';

class ParametersController {
    public function handleRequest() {
        header("Content-Type: application/json");

        try {
            $db = new Database();
            $pdo = $db->connect();

            $user_id = $_GET['user_id'] ?? null;
            $subque_id = $_GET['subque_id'] ?? null;

            if (!$user_id || !$subque_id) {
                echo json_encode([
                    "status" => false,
                    "message" => "user_id and subque_id are required"
                ]);
                return;
            }

            $model = new ParametersModel($pdo);
            $data = $model->getParameters($user_id, $subque_id);

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
