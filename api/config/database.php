<?php
date_default_timezone_set('Asia/Kolkata');

class Database {
    private $host = "localhost";
    private $dbname = "pmcbeatlemeco_db";
    private $username = "pmcbeatlemeco_user";
    private $password = "q?{a_i8B!c?_hqr*";
    public $conn;

    public function connect() {
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->dbname}", 
                                  $this->username, 
                                  $this->password,
                                  array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch(PDOException $e) {
            echo json_encode([
                "status" => false,
                "message" => "Database error: " . $e->getMessage()
            ]);
            exit;
        }
    }
}
