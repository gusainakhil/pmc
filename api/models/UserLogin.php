<?php
class UserLogin {
    private $conn;
    private $table = "baris_userlogin";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function authenticate($username, $password, $stationId) {
        $query = "SELECT * FROM {$this->table} WHERE db_userLoginName = ? AND StationId = ? AND db_usertype = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$username, $stationId, 'auditor']);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['db_password'])) {
            return [
                "status" => true,
                "message" => "Login successful",
                "user" => [
                    "id" => $user['userId'],
                    "username" => $user['db_username'],
                    "station_id" => $user['StationId']
                ]
            ];
        }

        return [
            "status" => false,
            "message" => $user ? "Invalid password" : "Invalid credentials"
        ];
    }
}
