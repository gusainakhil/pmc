<?php
class Station {
    private $conn;
    private $table = "baris_station";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getStationNameById($stationId) {
        $query = "SELECT stationName , stationId , 	db_questionsId FROM {$this->table} WHERE 	db_stLoginId = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$stationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
