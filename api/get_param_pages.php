<?php
header('Content-Type: application/json');
include 'config/database.php';

try {
    $subqueId = isset($_GET['subqueId']) ? intval($_GET['subqueId']) : 1;
    $tokenId  = isset($_GET['tokenid']) ? trim($_GET['tokenid']) : '';
    if ($subqueId <= 0) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Invalid subqueId']);
        exit;
    }
    // Validate token
    if ($tokenId === '') {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Missing tokenid']);
        exit;
    }

    $db = new Database();
    $conn = $db->connect(); // now $conn is PDO

    $sql = "SELECT DISTINCT
            bs.subqueId,
            bp.paramName,
            bp.paramId,
            bpage.db_pagename,
            bpage.pageId,
            (
                SELECT COUNT(*)
                FROM baris_survey AS srv
                WHERE srv.db_surveyParamId = bp.paramId
                AND srv.db_surveySubQuestionId = bs.subqueId
                AND srv.tokenid = :tokenId
                AND srv.created_date BETWEEN 
                    IF(
                        NOW() < DATE_FORMAT(NOW(), '%Y-%m-%d 06:00:00'),
                        DATE_FORMAT(NOW() - INTERVAL 1 DAY, '%Y-%m-%d 06:00:00'),
                        DATE_FORMAT(NOW(), '%Y-%m-%d 06:00:00')
                    )
                    AND
                    IF(
                        NOW() < DATE_FORMAT(NOW(), '%Y-%m-%d 06:00:00'),
                        DATE_FORMAT(NOW(), '%Y-%m-%d 06:00:00'),
                        DATE_FORMAT(NOW() + INTERVAL 1 DAY, '%Y-%m-%d 06:00:00')
                    )
            ) AS surveyCount,
            
            (
                SELECT COUNT(*)
               FROM Daily_Performance_Log AS dpl
                WHERE dpl.db_surveyParamId = bp.paramId
                AND dpl.tokenid = :tokenId
                AND dpl.created_date BETWEEN 
                    IF(
                        NOW() < DATE_FORMAT(NOW(), '%Y-%m-%d 06:00:00'),
                        DATE_FORMAT(NOW() - INTERVAL 1 DAY, '%Y-%m-%d 06:00:00'),
                        DATE_FORMAT(NOW(), '%Y-%m-%d 06:00:00')
                    )
                    AND
                    IF(
                        NOW() < DATE_FORMAT(NOW(), '%Y-%m-%d 06:00:00'),
                        DATE_FORMAT(NOW(), '%Y-%m-%d 06:00:00'),
                        DATE_FORMAT(NOW() + INTERVAL 1 DAY, '%Y-%m-%d 06:00:00')
                    )
            ) AS surveyCount1

        FROM baris_param AS bp
        JOIN baris_subquestion bs 
            ON FIND_IN_SET(bp.paramId, bs.db_paramId)
        JOIN baris_page bpage 
            ON FIND_IN_SET(bpage.pageId, bs.db_paramId)
        WHERE bs.subqueId = :subqueId";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':subqueId', $subqueId, PDO::PARAM_INT);
    $stmt->bindParam(':tokenId', $tokenId, PDO::PARAM_STR);
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$result) {
        echo json_encode(['status' => false, 'message' => 'No data found']);
        exit;
    }

    $response = [];
    foreach ($result as $row) {
        $paramId = $row['paramId'];
        if (!isset($response[$paramId])) {
            $response[$paramId] = [
                'subqueId'   => $row['subqueId'],
                'paramId'    => $paramId,
                'tokenId'    => $tokenId,
                'paramName'  => $row['paramName'],
                'surveyCount'  => intval($row['surveyCount']),
                'surveyCount1'  => intval($row['surveyCount1']),
                'pages'      => []
            ];
        }
        $response[$paramId]['pages'][] = [
            'pageId'      => $row['pageId'],
            'db_pagename' => $row['db_pagename']
        ];
    }

    echo json_encode([
        'status'  => true,
        'message' => 'Data fetched successfully',
        'data'    => array_values($response)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Internal Server Error',
        'error' => $e->getMessage()
    ]);
}
?>
