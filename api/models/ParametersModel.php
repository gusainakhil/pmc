<?php
class ParametersModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getParameters($user_id, $subque_id) {
        $query = "
            SELECT 
                u.userId, 
                q.subqueId, 
                sq.subqueName, 
                sq.subqueType, 
                sq.db_paramId, 
                sq.subqueId AS subquestion_id,
                p.paramId, 
                p.paramName, 
                p.db_pagesId
            FROM baris_userlogin u
            JOIN baris_station s ON u.OrgID = s.OrgID
            JOIN baris_question q ON s.db_questionsId = q.queId
            JOIN baris_subquestion sq ON FIND_IN_SET(sq.subqueId, q.subqueId)
            JOIN baris_param p ON FIND_IN_SET(p.paramId, sq.db_paramId)
            WHERE sq.subqueId = ? AND u.userId = ?
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$subque_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}