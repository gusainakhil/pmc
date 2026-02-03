<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "../../connection.php";

// Get selected month/year from form or default to current
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-t');

$station_id = $_SESSION['stationId'];

// Create date range array
$start = new DateTime($fromDate);
$end = new DateTime($toDate);
$end = $end->modify('+1 day');

$interval = new DateInterval('P1D');
$dateRange = new DatePeriod($start, $interval, $end);
?>




<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Performance Log <?php echo $fromDate ."To".$toDate  ?> </title>
  <style>
    body { font-size: 12px;
    font-family: 'Roboto'; }
    h2, h3 {
        text-align: center; margin: 5px 0;
        }
    .meta { 
        text-align: center; margin-bottom: 10px; 
        
    }
    table { width: 100%; border-collapse: collapse;   }
    th, td { border: 1px solid #000; padding: 1px; text-align: center;  }
    th { background-color: #f2f2f2; }
    .desc { text-align: left; }
 
  </style>
</head>
<body>
    <!-- Month & Year Selection Form -->
<!-- Date Range Filter Form -->
<form method="get" style="text-align: center; margin: 10px auto; padding: 10px; border: 1px solid #ccc; width: fit-content; background-color: #f9f9f9; border-radius: 10px;">
    <h3 style="margin-bottom: 15px;">Select Date Range for Report</h3>

    <label for="from_date" style="margin-right: 10px;">From:</label>
    <input type="date" name="from_date" id="from_date" value="<?= isset($_GET['from_date']) ? $_GET['from_date'] : '' ?>" style="padding: 5px 10px; margin-right: 20px;">

    <label for="to_date" style="margin-right: 10px;">To:</label>
    <input type="date" name="to_date" id="to_date" value="<?= isset($_GET['to_date']) ? $_GET['to_date'] : '' ?>" style="padding: 5px 10px; margin-right: 20px;">

    <input type="submit" value="Generate Report" style="padding: 8px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px;">
</form>


<?php
foreach ($dateRange as $dateObj) {
    $currentDate = $dateObj->format("Y-m-d");

    $query = "SELECT 
                bap.paramName AS task, 
                bp.db_pagename AS Description_of_Items, 
                bas.db_surveyValue AS Quality_of_done_work,
                bas.auditorname AS auditor_name,
                bs.stationName AS station_name,
                bo.db_Orgname AS organisation_name,
                bd.DivisionName AS division_name,
                bas.created_date AS report_date,
                bp.db_pageChoice2 AS Frequency,
                CONCAT(SUBSTRING(bp.db_pageChoice2, INSTR(bp.db_pageChoice2, '@')+1),'%') AS percentage_weightage
            FROM 
                baris_param bap
                INNER JOIN Daily_Performance_Log bas ON bap.paramId = bas.db_surveyParamId
                INNER JOIN baris_page bp ON bas.db_surveyPageId = bp.pageId
                INNER JOIN baris_station bs ON bas.db_surveyStationId = bs.stationId
                INNER JOIN baris_organization bo ON bas.OrgID = bo.OrgID
                INNER JOIN baris_division bd ON bas.DivisionId = bd.DivisionId
            WHERE 
                DATE(bas.created_date) = '$currentDate' 
                AND bas.db_surveyStationId = '$station_id'
           ORDER BY bas.db_surveyPageId ASC";

    $result = $conn->query($query);

    $tasks = [];
    $auditors = [
        'shift_1' => [],
        'shift_2' => [],
        'shift_3' => []
    ];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $task = trim($row['Description_of_Items']);
            $percentage = $row['percentage_weightage'];
            $frequency = $row['Frequency'];
            $status = $row['Quality_of_done_work'] == 1 ? 'Y' : 'N';
            $shift = strtolower(str_replace(' ', '_', $row['task']));
            $auditorName = $row['auditor_name'];

            // Use percentage as part of task key to ensure uniqueness only if same percentage
            $uniqueTaskKey = $task . '||' . $percentage;

            if (!isset($tasks[$uniqueTaskKey])) {
                $tasks[$uniqueTaskKey] = [
                    'task' => $task,
                    'quantity' => 'As Available',
                    'frequency' => $frequency,
                    'percentage' => $percentage,
                    'shift_1' => '',
                    'shift_2' => '',
                    'shift_3' => '',
                    'remarks' => ''
                ];
            }

            $tasks[$uniqueTaskKey][$shift] = $status;

            // Track auditors per shift (avoid duplicates)
            if (!in_array($auditorName, $auditors[$shift])) {
                $auditors[$shift][] = $auditorName;
            }

            // Store division/station/org from first row
            $division = $row['division_name'];
            $station = $row['station_name'];
            $contractor = $row['organisation_name'];
        }
?>

<h2>Performance Log</h2>
<h3>Daily performance log book for cleaning schedule for environmental sanitation, mechanized cleaning and housekeeping contract at Tirupati  <?= htmlspecialchars($station) ?> Railway Station</h3>

<div class="meta">
  <strong>Date:</strong> <?= $currentDate ?> &nbsp;&nbsp;
  <strong>Division:</strong> <?= htmlspecialchars($division) ?> &nbsp;&nbsp;
  <strong>Station:</strong> <?= htmlspecialchars($station) ?> &nbsp;&nbsp;
  <strong>Contractor:</strong> <?= htmlspecialchars($contractor) ?>
</div>

<table>
  <thead>
    <tr>
      <th rowspan="2">S.No</th>
      <th rowspan="2">Description of Items</th>
      <th rowspan="2">App. Quantity</th>
      <th rowspan="2">Frequency</th>
      <th rowspan="2">Percentage Weightage</th>
      <th colspan="3" >Shifts</th>
      <th rowspan="2">Remarks</th>
    </tr>
    <tr>
      <th style="width:1%">Shift 1<br>(Y/N)</th>
      <th style="width:1%">Shift 2<br>(Y/N)</th>
      <th style="width:1%">Shift 3<br>(Y/N)</th>
    </tr>
  </thead>
  <tbody>
    <?php
      $sn = 1;
      foreach ($tasks as $taskData): ?>
        <tr>
          <td><?= $sn++ ?></td>
          <td class="desc"><?= htmlspecialchars($taskData['task']) ?></td>
          <td><?= $taskData['quantity'] ?></td>
          <td><?= $taskData['frequency'] ?></td>
          <td><?= $taskData['percentage'] ?></td>
          <td><?= $taskData['shift_1'] ?: '-' ?></td>
          <td><?= $taskData['shift_2'] ?: '-' ?></td>
          <td><?= $taskData['shift_3'] ?: '-' ?></td>
          <td><?= $taskData['remarks'] ?></td>
        </tr>
    <?php endforeach; ?>

    <!-- Final row for auditor names -->
    <tr>
      <td colspan="5"><strong>Name of Auditor</strong></td>
      <td style="width:1%"><strong><?= !empty($auditors['shift_1']) ? $auditors['shift_1'][0] : '-' ?></strong></td>
      <td style="width:1%"><strong><?= !empty($auditors['shift_2']) ? $auditors['shift_2'][0] : '-' ?></strong></td>
      <td style="width:1%"><strong><?= !empty($auditors['shift_3']) ? $auditors['shift_3'][0] : '-' ?></strong></td>
      <td></td>
    </tr>
        <tr>
      <td colspan="5"><strong>Signature of On DUTY SUPERVISOR</strong></td>
      <td></td>
      <td></td>
      <td></td>
      <td></td>
    </tr>
    
    

  </tbody>
  
</table>

<?php
    } // end if data exists
} // end foreach loop
?>


</body>
</html>
