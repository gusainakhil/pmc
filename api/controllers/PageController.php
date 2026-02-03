<?php
require_once './config/database.php';
require_once './models/PageModel.php';

class PageController {
    public function handleRequest() {
        header("Content-Type: application/json");

        try {
            $db = new Database();
            $pdo = $db->connect();

            $user_id = $_GET['user_id'] ?? null;
            $subque_id = $_GET['subque_id'] ?? null;
            $param_id = $_GET['param_id'] ?? null;

            if (!$user_id || !$subque_id || !$param_id) {
                echo json_encode([
                    "status" => false,
                    "message" => "user_id, subque_id, and param_id are required"
                ]);
                return;
            }

            $model = new PageModel($pdo);
            $data = $model->getPageDetails($user_id, $subque_id, $param_id);

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
