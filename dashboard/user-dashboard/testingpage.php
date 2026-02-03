<?php
require_once '../../connection.php'; // secure include

// Sanitize & validate input
function getParam($key, $default = '') {
    return isset($_GET[$key]) ? htmlspecialchars(trim($_GET[$key])) : $default;
}

$stationId = 44;
$OrgID     = 42;     

$month     = (int) getParam('month', date('n'));
$year      = (int) getParam('year', date('Y'));
$startDate = sprintf("%04d-%02d-01", $year, $month);
$endDate = date("Y-m-t", strtotime($startDate));

// Auto-fetch subqueId from Daily_Performance_Log
$subqueId = null;
$subque_query = "
    SELECT DISTINCT db_surveySubQuestionId
    FROM Daily_Performance_Log 
    WHERE db_surveyStationId = ?
      AND created_date BETWEEN ? AND ?
    LIMIT 1
";
$stmt = $conn->prepare($subque_query);
$stmt->bind_param("iss", $stationId, $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $subqueId = (int)$row['db_surveySubQuestionId'];
}
$stmt->close();

if (!$subqueId) {
    die("❌ Subquestion ID not found for the given station and date range.");
}



// Fetch monthly targets
$targets = [];
$t_sql = "
    SELECT pageId,
           SUBSTRING_INDEX(value, ',', 1) AS t1,
           SUBSTRING_INDEX(SUBSTRING_INDEX(value, ',', 2), ',', -1) AS t2,
           SUBSTRING_INDEX(SUBSTRING_INDEX(value, ',', 3), ',', -1) AS t3
    FROM baris_target
    WHERE OrgID = ? AND month = ? AND subqueId = ?
    ORDER BY id DESC
    LIMIT 24
";
$stmt = $conn->prepare($t_sql);
$stmt->bind_param("iii", $OrgID, $month, $subqueId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $targets[$row['pageId']] = [(float)$row['t1'], (float)$row['t2'], (float)$row['t3']];
}
$stmt->close();

// Fetch achievements
$score_sql = "
    SELECT dpl.db_surveyPageId,
           SUBSTRING(bp2.db_pageChoice2, INSTR(bp2.db_pageChoice2, '@') + 1) AS weightage,
           SUM(CASE WHEN bp1.paramName='Shift 1' THEN dpl.db_surveyValue ELSE 0 END) AS a1,
           SUM(CASE WHEN bp1.paramName='Shift 2' THEN dpl.db_surveyValue ELSE 0 END) AS a2,
           SUM(CASE WHEN bp1.paramName='Shift 3' THEN dpl.db_surveyValue ELSE 0 END) AS a3
    FROM Daily_Performance_Log dpl
    JOIN baris_param bp1 ON dpl.db_surveyParamId = bp1.paramId
    JOIN baris_page bp2 ON dpl.db_surveyPageId = bp2.pageId
    WHERE dpl.db_surveyStationId = ? AND dpl.created_date BETWEEN ? AND ?
    GROUP BY dpl.db_surveyPageId
";

$stmt = $conn->prepare($score_sql);
$stmt->bind_param("iss", $stationId, $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$total_weightage = 0;
while ($row = $result->fetch_assoc()) {
    $pageId = $row['db_surveyPageId'];
    $target = $targets[$pageId] ?? [0, 0, 0];

    $target_sum = $target[0] + $target[1] + $target[2];
    $achieved_sum = 
        ($target[0] > 0 ? $row['a1'] : 0) +
        ($target[1] > 0 ? $row['a2'] : 0) +
        ($target[2] > 0 ? $row['a3'] : 0);

    $final_score = $target_sum > 0 ? ($achieved_sum / $target_sum) * 100 : 0;
    $weightage = (float)$row['weightage'];
    $weightage_achieved = ($final_score * $weightage) / 100;

    $total_weightage += $weightage_achieved;
}
$stmt->close();
?>


    <p><?= number_format($total_weightage, 2) ?>%</p>

