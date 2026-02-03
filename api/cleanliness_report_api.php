<?php
// insert_survey.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once 'config/db.php'; // Adjust path as needed
// require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

$response = ["status" => false, "message" => ""];

if ($data && isset($data["pageIds"], $data["values"])) {
    $OrgID = intval($data["OrgID"]);
    $questionId = intval($data["db_questionsId"]);
    $subQuestionId = intval($data["subquestion_id"]);
    $paramId = intval($data["paramId"]);
    $stationId = intval($data["station"]);
    $userId = intval($data["userId"]);
    $pageIds = $data["pageIds"];
    $values = $data["values"];
    $DivisionId =$data["DivisionId"];
    $auditorname =$data["employeeName"];
    $tokenid = $data["token"];

    if (count($pageIds) != count($values)) {
        $response["message"] = "pageIds and values count mismatch.";
        echo json_encode($response);
        exit;
    }

    $createdDate = date('Y-m-d H:i:s');
    $successCount = 0;

    $query = "INSERT INTO Daily_Performance_Log 
        (OrgID, db_surveyQuestionId, db_surveySubQuestionId, db_surveyPageId, db_surveyValue, db_surveyUserid, db_surveyParamId, db_surveyStationId, created_date ,DivisionId ,auditorname, tokenid) 
        VALUES (:OrgID, :questionId, :subQuestionId, :pageId, :surveyValue, :userId, :paramId, :stationId, :createdDate, :DivisionId , :auditorname, :tokenid)";
    $stmt = $conn->prepare($query);

    foreach ($pageIds as $index => $pageId) {
        $pageId = intval($pageId);
        $valueText = strtolower($values[$index]);
        $surveyValue = ($valueText === "yes") ? 1 : 0;

        $stmt->bindParam(':OrgID', $OrgID);
        $stmt->bindParam(':questionId', $questionId);
        $stmt->bindParam(':subQuestionId', $subQuestionId);
        $stmt->bindParam(':pageId', $pageId);
        $stmt->bindParam(':surveyValue', $surveyValue);
        $stmt->bindParam(':userId', $userId);
        $stmt->bindParam(':paramId', $paramId);
        $stmt->bindParam(':stationId', $stationId);
        $stmt->bindParam(':createdDate', $createdDate);
        $stmt->bindParam(':DivisionId', $DivisionId);
        $stmt->bindParam(':auditorname',$auditorname);
        $stmt->bindParam(':tokenid',$tokenid);
        

        if ($stmt->execute()) {
            $successCount++;
        }
    }

    if ($successCount == count($pageIds)) {
        $response["status"] = true;
        $response["message"] = "All survey rows inserted successfully.";
    } else {
        $response["message"] = "Some rows may not have been inserted.";
    }
} else {
    $response["message"] = "Invalid request data.";
}

echo json_encode($response);
?>
