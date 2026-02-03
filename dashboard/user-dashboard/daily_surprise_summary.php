<?php
session_start();
include "head.php";
include "../../connection.php";

// Default month & year
$selectedMonth = $_GET['month'] ?? date('m');
$selectedYear  = $_GET['year']  ?? date('Y');
$station_id    = $_SESSION['stationId'];

// Month range
$startDate = "$selectedYear-$selectedMonth-01";
$endDate   = date("Y-m-t 23:59:59", strtotime($startDate));

/* =========================
   TOKEN-WISE QUERY
   ========================= */
$sql = "
    SELECT
        bas.tokenId,
        DATE(bas.created_date) AS report_date,
        bas.db_surveyValue AS score,
        bu.db_username AS auditor,
        bs.stationName,
        bo.db_Orgname AS organisation_name,
        bd.DivisionName
    FROM baris_survey bas
    INNER JOIN baris_userlogin bu ON bas.db_surveyUserid = bu.userId
    INNER JOIN baris_station bs ON bas.db_surveyStationId = bs.stationId
    INNER JOIN baris_organization bo ON bas.OrgID = bo.OrgID
    INNER JOIN baris_division bd ON bas.DivisionId = bd.DivisionId
    WHERE bas.db_surveyStationId = '$station_id'
      AND bas.created_date BETWEEN '$startDate' AND '$endDate'
    ORDER BY report_date, bas.tokenId
";

$result = $conn->query($sql);

/* =========================
   DATA STRUCTURES
   ========================= */
$inspections = []; // date => token => score/count
$auditor = $division = $station = $contractor = '';

// Build full date list
$allDates = [];
$current = strtotime($startDate);
$end     = strtotime($endDate);
while ($current <= $end) {
    $allDates[] = date('Y-m-d', $current);
    $current = strtotime('+1 day', $current);
}

/* =========================
   PROCESS DATA TOKEN-WISE
   ========================= */
while ($row = $result->fetch_assoc()) {

    $date    = $row['report_date'];
    $tokenId = $row['tokenId'];

    if (!$auditor)    $auditor    = $row['auditor'];
    if (!$division)   $division   = $row['DivisionName'];
    if (!$station)    $station    = $row['stationName'];
    if (!$contractor) $contractor = $row['organisation_name'];

    if (!isset($inspections[$date])) {
        $inspections[$date] = [];
    }

    if (!isset($inspections[$date][$tokenId])) {
        $inspections[$date][$tokenId] = [
            'score' => 0,
            'count' => 0
        ];
    }

    $inspections[$date][$tokenId]['score'] += $row['score'];
    $inspections[$date][$tokenId]['count']++;
}

/* =========================
   BUILD FINAL DATE SUMMARY
   ========================= */
$finalSummary = [];

foreach ($allDates as $date) {

    if (!isset($inspections[$date])) {
        $finalSummary[$date] = [
            'date' => $date,
            'total' => 0,
            'score' => 0,
            'percentage' => 0
        ];
        continue;
    }

    $dateScore = 0;
    $dateTotal = 0;

    foreach ($inspections[$date] as $token) {
        $dateScore += $token['score'];
        $dateTotal += $token['count'] * 10;
    }

    $finalSummary[$date] = [
        'date' => $date,
        'total' => $dateTotal,
        'score' => $dateScore,
        'percentage' => $dateTotal > 0
            ? round(($dateScore / $dateTotal) * 100, 2)
            : 0
    ];
}

/* =========================
   OVERALL SUMMARY
   ========================= */
$totalScore = array_sum(array_column($finalSummary, 'score'));
$maxScore   = array_sum(array_column($finalSummary, 'total'));
$overallAverage = $maxScore > 0
    ? round(($totalScore / $maxScore) * 100, 2)
    : 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Summary Report <?php echo $station; ?></title>

<!-- 🔒 YOUR ORIGINAL CSS – UNCHANGED -->
<style>
<?php /* pasted exactly as you gave */ ?>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
}
.container { width: 95%; margin: auto; font-size: 14px; }
h2, h3, p { margin: 2px 0; text-align: center; }
.report-title { font-size: 18px; font-weight: 600; }
.report-subtitle { font-size: 14px; font-weight: 500; }
.info-line { margin-bottom: 5px; }
.filters { text-align: center; margin: 5px 0; }
.filters select, .filters input { padding: 5px; font-size: 14px; }
.filters button {
    background-color: green; color: white; border: none;
    padding: 5px 12px; font-size: 14px;
    border-radius: 4px; cursor: pointer;
}
table {
    width: 100%; border-collapse: collapse;
    font-size: 12px; margin-top: 15px;
    page-break-inside: avoid;
}
table, th, td { border: 1px solid #000; text-align: center; }
th { background-color: #f0f0f0; font-weight: bold; }
.signature-section {
    display: flex; justify-content: space-between;
    margin-top: 50px; padding: 0 30px;
}
.signature-block { text-align: center; font-size: 14px; }
.signature-line {
    margin-top: 60px; border-top: 1px solid #000;
    width: 200px; margin-left: auto; margin-right: auto;
}
</style>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
<?php include "header.php"; ?>

<main class="app-main">
<div class="container">

<div class="filters">
<form method="get">
<select name="month">
<?php
for ($i=1;$i<=12;$i++){
    $m=str_pad($i,2,'0',STR_PAD_LEFT);
    $sel=$m==$selectedMonth?'selected':'';
    echo "<option value='$m' $sel>".date('F',strtotime("2024-$m-01"))."</option>";
}
?>
</select>
<input type="number" name="year" value="<?= $selectedYear ?>">
<button type="submit">GO</button>
<button type="button" onclick="window.print()">Print</button>
</form>
</div>

<h2 class="report-title">WESTERN RAILWAY</h2>
<h3 class="report-subtitle">Daily Surprise Report Summary</h3>

<p class="info-line">
<strong>Month:</strong> <?= date('F Y',strtotime($startDate)) ?> &nbsp;|&nbsp;
<strong>Division:</strong> <?= $division ?> &nbsp;|&nbsp;
<strong>Station:</strong> <?= $station ?> &nbsp;|&nbsp;
<strong>Name Of Contractor:</strong> <?= $contractor ?> &nbsp;|&nbsp;
<strong>Overall Average:</strong> <?= $overallAverage ?>% &nbsp;|&nbsp;
<strong>Total Score:</strong> <?= $totalScore ?>
</p>

<table>
<thead>
<tr>
<th>S.No</th>
<th>Inspection Date</th>
<th>Total</th>
<th>Score</th>
<th>Score (%)</th>
</tr>
</thead>
<tbody>
<?php
$i=1;
foreach ($finalSummary as $row){
    echo "<tr>
        <td>$i</td>
        <td>".date('d-M-Y',strtotime($row['date']))."</td>
        <td>{$row['total']}</td>
        <td>{$row['score']}</td>
        <td>{$row['percentage']}</td>
    </tr>";
    $i++;
}
?>
</tbody>
</table>

<div class="signature-section">
<div class="signature-block">
Signature of Contractor Representative
<div class="signature-line"></div>
</div>
<div class="signature-block">
CHI InCharge
<div class="signature-line"></div>
</div>
</div>

</div>
</main>
</div>
</body>
</html>
