<?php
require_once './config/database.php';
require_once './models/UserLogin.php';

class UserLoginController {
    public function login() {
        header("Content-Type: application/json");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $username  = $_POST['username'] ?? $_GET['username'] ?? null;
        $password  = $_POST['password'] ?? $_GET['password'] ?? null;
        $stationId = $_POST['station_id'] ?? $_GET['station_id'] ?? null;

        if (!$username || !$password || !$stationId) {
            echo json_encode([
                "status" => false,
                "message" => "username, password, and station_id are required"
            ]);
            return;
        }

        $database = new Database();
        $db = $database->connect();
        $userLogin = new UserLogin($db);
        $response = $userLogin->authenticate($username, $password, $stationId);

        echo json_encode($response);
    }
}
