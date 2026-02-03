<?php
class GetUserNameReportModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getUserReport($user_id, $subque_id) {
        $query = "
            SELECT 
                u.db_username,
                u.db_userLoginName,
                u.userId, 
                q.queName, 
                q.subqueId, 
                sq.subqueName,
                sq.subqueType,
                sq.subqueId AS subquestion_id
            FROM baris_userlogin u
            JOIN baris_station s ON u.OrgID = s.OrgID 
            JOIN baris_question q ON s.db_questionsId = q.queId 
            JOIN baris_subquestion sq ON FIND_IN_SET(sq.subqueId, q.subqueId)
            WHERE sq.subqueId = ? AND u.userId = ?
        ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$subque_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
