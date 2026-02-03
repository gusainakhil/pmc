<?php
class SubQuestionsModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getSubquestionsByUserId($user_id) {
        $query = "
           SELECT 
                u.userId, u.OrgID, u.StationId,
                s.db_questionsId,
                q.queId, q.queName, q.subqueId,
                sq.subqueName, sq.subqueType, sq.subqueId AS subquestion_id,
                sq.db_paramId as parameteresId
      
            FROM baris_userlogin u
            JOIN baris_station s ON u.OrgID = s.OrgID
            JOIN baris_question q ON s.db_questionsId = q.queId
            JOIN baris_subquestion sq ON FIND_IN_SET(sq.subqueId, q.subqueId)
            
            WHERE u.userId  = ?
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
