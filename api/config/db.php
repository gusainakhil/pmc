<?php
date_default_timezone_set('Asia/Kolkata');

class Database {
    private $host = "localhost";
    private $dbname = "pmcbeatlemeco_db";
    private $username = "pmcbeatlemeco_user";
    private $password = "q?{a_i8B!c?_hqr*";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=".$this->host.";dbname=".$this->dbname, 
                $this->username, 
                $this->password
            );
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>
