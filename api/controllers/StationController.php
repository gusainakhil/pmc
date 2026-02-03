<?php
require_once './config/database.php';
require_once './models/Station.php';

class StationController {
    public function getStationName() {
         header("Content-Type: application/json");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $stationId = $_GET['station_id'] ?? null;

        if (!$stationId) {
            echo json_encode([
                "status" => false,
                "message" => "station_id is required"
            ]);
            return;
        }

        $database = new Database();
        $db = $database->connect();
        $station = new Station($db);
        $data = $station->getStationNameById($stationId);

        if ($data) {
            echo json_encode([
                "status" => true,
                "stationName" => $data['stationName'],
                "db_questionsId" => $data['db_questionsId'],
                "stationId" => $data['stationId']
            ]);
        } else {
            echo json_encode([
                "status" => false,
                "message" => "Station not found"
            ]);
        }
    }
}
