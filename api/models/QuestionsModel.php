<?php
class QuestionsModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getQuestionsByUserId($user_id) {
        $query = "
            SELECT 
                u.userId, u.OrgID, u.StationId,
                s.db_questionsId, 
                q.queId, q.queName, q.subqueId , u.DivisionId
            FROM baris_userlogin u
            JOIN baris_station s ON u.OrgID = s.OrgID
            JOIN baris_question q ON s.db_questionsId = q.queId
            
            WHERE u.userId = ?
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
