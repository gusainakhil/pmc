<?php
session_start();
$station_id = $_SESSION['stationId'];
if (!isset($_SESSION['id'])) {
    $_SESSION['id'] = $_GET['id']; 
}
$id = $_SESSION['id'];

$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

$startDate = "$selectedYear-$selectedMonth-01";
$endDate = date("Y-m-t", strtotime($startDate));

include "../../connection.php";


$targetSum = 0;
$targetQuery = "SELECT VALUE FROM baris_target WHERE subqueId =  '$id' AND created_date BETWEEN '$startDate' AND '$endDate'";
$targetResult = mysqli_query($conn, $targetQuery);
while ($row = mysqli_fetch_assoc($targetResult)) {
    $values = explode(',', $row['VALUE']);
    foreach ($values as $val) {
        $targetSum += (int)trim($val);
    }
}


$dataQuery = "SELECT 
    bas.db_surveySubQuestionId,
    bas.db_surveyValue AS Quality_of_done_work,
    DATE(bas.created_date) AS report_date,
    bs.stationName AS station_name,
    bo.db_Orgname AS organisation_name,
    bd.DivisionName AS division_name,
    bt.penalty_rate,
    bt.pageId
FROM 
    baris_param bap
    INNER JOIN baris_machine_report bas ON bap.paramId = bas.db_surveyParamId
    INNER JOIN baris_page bp ON bas.db_surveyPageId = bp.pageId
    INNER JOIN baris_station bs ON bas.db_surveyStationId = bs.stationId
    INNER JOIN baris_organization bo ON bas.OrgID = bo.OrgID
    INNER JOIN baris_division bd ON bas.DivisionId = bd.DivisionId
    INNER JOIN baris_target bt ON bas.db_surveyPageId = bt.pageId
WHERE 
    bas.db_surveyStationId = '$station_id ' 
    AND DATE(bas.created_date) BETWEEN '$startDate' AND '$endDate'
    AND DATE(bt.created_date) BETWEEN '$startDate' AND '$endDate'";

$result = mysqli_query($conn, $dataQuery);

$dailyData = [];
$meta = [
    'station' => '',
    'division' => '',
    'contractor' => '',
];

while ($row = mysqli_fetch_assoc($result)) {
    $date = $row['report_date'];
    $value = (int)$row['Quality_of_done_work'];
    $pageId = $row['pageId'];
    $penalty = $row['penalty_rate'];

    if (!isset($dailyData[$date])) {
        $dailyData[$date] = [
            'total_value' => 0,
            'penalty' => 0,
            'zero_flags' => [],
        ];
    }

    $dailyData[$date]['total_value'] += $value;

    if ($value == 0) {
        $dailyData[$date]['penalty'] += $penalty;
    }

    // Save meta info once
    $meta['station'] = $row['station_name'];
    $meta['division'] = $row['division_name'];
    $meta['contractor'] = $row['organisation_name'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PMC - Daily Machine Report</title>
  <style>
    body  {
            width: 70%;
            margin: auto;
            font-weight: 800;
            font-size: 12px;
            font-family: 'Roboto';
        }
    h2 { text-align: center; margin-bottom: 5px; }
    .subtitle { text-align: center; font-weight: bold; margin-bottom: 20px; }
    .meta { display: flex; justify-content: space-between; flex-wrap: wrap; margin-bottom: 10px; font-weight: bold;   }
    .meta span { margin: 5px 15px 5px 0; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    th, td { border: 1px solid #000; padding: 2px; text-align: center; }
    th { background-color: #f2f2f2; }
    .footer { text-align: right; font-weight: bold; padding-right: 10px; }
  </style>
</head>
<body>

<form method="get" style="text-align: center; margin-top: 20px;">
    <label for="month">Select Month:</label>
    <select name="month" id="month">
        <?php for ($m = 1; $m <= 12; $m++): 
            $monthPadded = str_pad($m, 2, '0', STR_PAD_LEFT);
            $selected = ($selectedMonth == $monthPadded) ? "selected" : ""; ?>
            <option value="<?= $monthPadded ?>" <?= $selected ?>><?= date('F', mktime(0, 0, 0, $m, 10)) ?></option>
        <?php endfor; ?>
    </select>

    <label for="year">Select Year:</label>
    <select name="year" id="year">
        <?php $currentYear = date("Y");
        for ($y = $currentYear; $y >= 2020; $y--):
            $selected = ($selectedYear == $y) ? "selected" : ""; ?>
            <option value="<?= $y ?>" <?= $selected ?>><?= $y ?></option>
        <?php endfor; ?>
    </select>
    <input type="submit" value="Generate Report">
</form>

<h2>PMC - Daily Machine Report</h2>
<div class="subtitle">
    Daily uses of type and quantity of consumables of environmental sanitation, mechanized cleaning and housekeeping contract at <?= $meta['station'] ?> station
</div>

<div class="meta">
    <div>
      <span>Month: <u><?= date('F', strtotime($startDate)) ?></u></span>
      <span>Division: <u><?= $meta['division'] ?></u></span>
      <span>Station: <u><?= $meta['station'] ?></u></span>
 
      <span>Name Of Contractor: <u><?= $meta['contractor'] ?></u></span>
      <?php 
        $totalPenalty = 0; $avgScore = 0;
        if (count($dailyData) > 0) {
            foreach ($dailyData as $day => $d) {
                $totalPenalty += $d['penalty'];
                $score = ($targetSum > 0) ? round(($d['total_value'] / $targetSum) * 100, 2) : 0;
                $avgScore += $score;
            }
            $avgScore = round($avgScore / count($dailyData), 2);
        }
      ?>
      <span>Average: <u><?= $avgScore ?></u></span>
      <span>Total Month Penalty: <u><?= $totalPenalty ?></u></span>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>S.No</th>
            <th>Date</th>
            <th>Score(%)</th>
            <th>Penalty for the Day</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $i = 1;
        foreach ($dailyData as $date => $d):
            $score = ($targetSum > 0) ? round(($d['total_value'] / $targetSum) * 100, 2) : 0;
        ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= date('d-M-Y', strtotime($date)) ?></td>
            <td><?= $score ?></td>
            <td><?= $d['penalty'] ?></td>
        </tr>
        <?php endforeach; ?>
        <tr>
            <td colspan="3" class="footer">Total Month Penalty</td>
            <td><strong><?= $totalPenalty ?></strong></td>
        </tr>
    </tbody>
</table>
<span>Signature of Contractor Representative </span>  <span style="margin-left:150px;">CHI IN Charge</span>

</body>
</html>
