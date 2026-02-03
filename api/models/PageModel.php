<?php
class PageModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getPageDetails($user_id, $subque_id, $param_id) {
        $query = "
            SELECT 
                u.userId, q.subqueId, sq.subqueName, sq.subqueType, sq.db_paramId, 
                sq.subqueId AS subquestion_id, p.paramId, p.paramName, p.db_pagesId, 
                pg.pageId, pg.db_pagename AS page_name, pg.db_pageChoice2, pg.db_pageChoice
            FROM baris_userlogin u
            JOIN baris_station s ON u.OrgID = s.OrgID
            JOIN baris_question q ON s.db_questionsId = q.queId
            JOIN baris_subquestion sq ON FIND_IN_SET(sq.subqueId, q.subqueId)
            JOIN baris_param p ON FIND_IN_SET(p.paramId, sq.db_paramId)
            JOIN baris_page pg ON FIND_IN_SET(pg.pageId, p.db_pagesId)
            WHERE sq.subqueId = ? AND u.userId = ? AND p.paramId = ?
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$subque_id, $user_id, $param_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
