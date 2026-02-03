<?php
session_start();
$station_id = $_SESSION['stationId'];
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

$startDate = "$selectedYear-$selectedMonth-01";
$endDate = date("Y-m-t 23:59:59", strtotime($startDate));

include "../../connection.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

$reportQuery = "
    SELECT 
        DATE(dpl.created_date) AS report_date,
        dpl.db_surveyValue AS quality,
        bs.stationName,
        bd.divisionName, 
        bg.db_Orgname 
    FROM 
        Daily_Performance_Log dpl
    JOIN 
        baris_station bs ON dpl.db_surveyStationId = bs.stationId
    JOIN 
        baris_division bd ON bs.DivisionId = bd.DivisionId
    JOIN 
        baris_organization bg ON bd.OrgID = bg.OrgID
    WHERE 
        dpl.db_surveyStationId = '$station_id'
        AND dpl.created_date BETWEEN '$startDate' AND '$endDate';
";

$reportResult = $conn->query($reportQuery);
if (!$reportResult) {
    die("Query failed: " . $conn->error);
}

$dailyData = [];
$stationDetails = [];

while ($row = $reportResult->fetch_assoc()) {
    $date = $row['report_date'];
    $value = floatval($row['quality']);

    if (empty($stationDetails)) {
        $stationDetails = [
            'stationName' => $row['stationName'],
            'divisionName' => $row['divisionName'],
            'db_Orgname' => $row['db_Orgname']
        ];
    }

    if (!isset($dailyData[$date])) {
        $dailyData[$date] = [
            'total' => 0,
            'count' => 0,
        ];
    }

    $dailyData[$date]['total'] += $value;
    $dailyData[$date]['count']++;
}

$finalReport = [];

foreach ($dailyData as $date => $data) {
    $score = $data['count'] > 0 ? round(($data['total'] / $data['count']) * 100, 2) : 0;
    $finalReport[] = [
        'date' => date("d-M-Y", strtotime($date)),
        'score' => $score
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <title>Daily Report - <?= $stationDetails['stationName'] ?> - <?= date('F Y', strtotime($startDate)) ?></title>
  <?php include "head.php"; ?>

  <style>
    .body {
      width: 100%;
      margin: auto;
      font-weight: bold;
      font-size: 11px;
      font-family: 'Roboto' !important;
      text-align: center;
    }

    h2 {
      text-align: center;
      font-weight: bold;
      margin: 5px 0;
      color: #333;
    }

    .subtitle {
      text-align: center;
      font-weight: bold;
      margin: 5px 0;
      font-size: 14px;
      color: #555;
    }

    .meta {
      text-align: center;
      margin: 5px 0;
      font-weight: bold;
    }

    .meta span {
      display: inline-block;
      margin: 5px 15px;
      font-weight: bold;
    }

    table {
      border-collapse: collapse;
      width: 80%;
      background: #fff;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      margin: 5px auto;
    }

    th,
td {
  border: 1px solid #000;
  padding: 2px 4px;           /* Reduced vertical padding */
  line-height: 1;           /* Slightly tighter line height */
  text-align: center;
  vertical-align: middle;
  font-weight: 500;
  font-size: 13px;            /* Optional: slightly smaller font */
}

    th {
      background-color: #eaeaea;
      font-weight: 400;
      color: #333;
    }

    td:nth-child(1),
    th:nth-child(1) {
      width: 80px;
      text-align: center;
    }

    .signature {
      text-align: right;
      font-weight: bold;
      font-style: italic;
      margin-right: 10%;
      margin-top: 30px;
    }

    .custom-month-year-form {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 15px;
      flex-wrap: wrap;
      padding: 15px 25px;
      border-radius: 12px;
      font-family: 'Arial', sans-serif;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      margin: 30px auto;
      max-width: fit-content;
    }

    .custom-month-year-form label {
      font-weight: bold;
      color: #333;
    }

    .custom-month-year-form select {
      padding: 4px 6px;
      font-size: 14px;
      border: 1px solid #ccc;
      border-radius: 6px;
      background-color: #fff;
      color: #333;
      font-weight: bold;
      text-align: center;
    }

    .custom-month-year-form input[type="submit"] {
      padding: 5px 8px;
      font-size: 14px;
      border: none;
      border-radius: 6px;
      background-color: #008000;
      color: white;
      cursor: pointer;
      transition: background-color 0.3s ease;
      font-weight: bold;
    }

    .custom-month-year-form input[type="submit"]:hover {
      background-color: #006400;
    }
  </style>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
  <div class="app-wrapper">
    <?php include "header.php"; ?>
    <main class="app-main">
      <div class="app-content">
        <div class="container-fluid">
          <div class="row g-4">
            <div class="col-md-12">
              <div class="body">

                <form method="get" class="custom-month-year-form">
                  <label for="month">Select Month:</label>
                  <select name="month" id="month">
                    <?php
                    for ($m = 1; $m <= 12; $m++) {
                      $monthPadded = str_pad($m, 2, '0', STR_PAD_LEFT);
                      $selected = ($selectedMonth == $monthPadded) ? "selected" : "";
                      echo "<option value=\"$monthPadded\" $selected>" . date('F', mktime(0, 0, 0, $m, 10)) . "</option>";
                    }
                    ?>
                  </select>

                  <label for="year">Select Year:</label>
                  <select name="year" id="year">
                    <?php
                    $currentYear = date("Y");
                    for ($y = $currentYear; $y >= 2020; $y--) {
                      $selected = ($selectedYear == $y) ? "selected" : "";
                      echo "<option value=\"$y\" $selected>$y</option>";
                    }
                    ?>
                  </select>
                  <!--<button type="submit">View Report</button>-->
                  <input type="submit" value="Generate Report">
                </form>

                <h2>Cleanliness Report Summary</h2>
                <div class="subtitle">
                  Daily uses of type and quantity of consumables of environmental sanitation, mechanized cleaning and housekeeping contract at Railway station
                </div>

                <div class="meta">
                  <div>
                    <span>Month: <u><?= date('F', strtotime($startDate)) ?></u></span>
                    <span>Division: <u><?= $stationDetails['divisionName'] ?></u></span>
                    <span>Station: <u><?= $stationDetails['stationName'] ?></u></span>
                    <span>Name Of Contractor: <u><?= $stationDetails['db_Orgname'] ?></u></span>
                    <span>Average: <u>
                        <?php
                        $avgScore = count($finalReport) ? round(array_sum(array_column($finalReport, 'score')) / count($finalReport), 2) : 0;
                        echo $avgScore . '%';
                        ?>
                      </u></span>
                  </div>
                </div>

                <table>
                  <thead>
                    <tr>
                      <th>S.No</th>
                      <th>Date</th>
                      <th>Score(%)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($finalReport as $index => $row): ?>
                      <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= $row['date'] ?></td>
                        <td><?= $row['score'] ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>

                <div class="signature">
                  Signature of Contractor Representative
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>

</html>