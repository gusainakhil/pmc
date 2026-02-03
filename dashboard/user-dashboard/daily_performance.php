<?php
// Start the session
session_start();

// Check if userId is not set
if (!isset($_SESSION['userId']) || empty($_SESSION['userId'])) {
  // Destroy the session
  session_unset();
  session_destroy();
  header("Location: https://pmc.beatleme.co.in/");
  exit();
}

include_once "../../connection.php";

// Inputs
$id = isset($_GET['id']) ? $_GET['id'] : '';

// Get selected month/year from form or default to current
$fromDate = isset($_GET['from_date']) && $_GET['from_date'] !== '' ? $_GET['from_date'] : date('Y-m-01');
$toDate   = isset($_GET['to_date'])   && $_GET['to_date']   !== '' ? $_GET['to_date']   : date('Y-m-t');

$station_id = $_SESSION['stationId'] ?? '';

// Create date range array
$start = new DateTime($fromDate);
$end = new DateTime($toDate);
$end = $end->modify('+1 day');

$interval = new DateInterval('P1D');
$dateRange = new DatePeriod($start, $interval, $end);

// Convert to array and sort in descending order (newest first)
$datesArray = [];
foreach ($dateRange as $dateObj) {
  $datesArray[] = $dateObj;
}

// Sort dates in descending order (newest first)
usort($datesArray, function($a, $b) {
  return $b <=> $a;
});
?>
<!doctype html>
<html lang="en">
<!--begin::Head-->
<?php include "head.php" ?>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<style>
  .report-container {
    width: 99%;
    margin: auto;
    page-break-after: always;
    font-weight: 600;
    font-size: 14px;
    font-family: 'Roboto', sans-serif !important;
  }
  .data-table th,
  .data-table td {
    border: 1px solid #000;
    text-align: center;
    font-weight: 400;
    font-size: 13px;
  }
  .signature-block { text-align: center; font-weight: bold; margin: 30px 0 10px; }
  .signature-labels { display: flex; justify-content: space-between; margin-bottom: 50px; }
  .signature-lines { display: flex; justify-content: space-between; }
  .signature-line { border-top: 1px solid #000; width: 30%; max-width: 200px; }
  .filter-container {
    background-color: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 20px;
    display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: center;
  }
  .action-buttons { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px; }
  .action-buttons .btn { white-space: nowrap; font-size: 14px; display: flex; align-items: center; gap: 5px; }
  .report-header { background-color: #f8f9fa; padding: 15px; border-bottom: 1px solid #e9ecef; }
  .report-header h2 { margin: 0; font-size: 20px; color: #212529; }
  .report-header h3 { margin: 5px 0 0; font-size: 16px; color: #6c757d; font-weight: normal; }
  .meta-info {
    display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;
    background-color: #e9ecef; padding: 10px; margin-bottom: 15px;
  }
  .meta-info span { display: inline-flex; align-items: center; }
  .data-table { width: 100%; border-collapse: collapse; font-size: 11px; font-family: 'Roboto'; }
  .data-table th, .data-table td { border: 1px solid #000; text-align: center; }
  .data-table th { background-color: #f8f9fa; position: sticky; top: 0; z-index: 10; }
  .data-table .desc { text-align: left; }
  .loading { text-align: center; padding: 20px; font-size: 16px; color: #6c757d; }

  /* Print styles */
  @media print {
    .app-wrapper, .app-main, .app-content, .container-fluid, .row, .performance-container, .report-container {
      width: 100% !important; margin: 0 !important; padding: 0 !important;
    }
    header, footer, .sidebar, .app-header, .app-sidebar, .no-print, .app-navbar { display: none !important; }
    .report-container { display: block !important; page-break-after: always; }
    body { margin: 0; padding: 0; background: white; }
  }
</style>

<script>
  function printPage() { window.print(); }
</script>
<!--end::Head-->
<!--begin::Body-->
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
  <!--begin::App Wrapper-->
  <div class="app-wrapper">
    <?php include "header.php" ?>
    <main class="app-main">
      <!--begin::App Content Header-->
      <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
          <div class="row">
            <!-- Date Selection Form -->
            <div class="col-lg-5 mb-3">
              <div class="performance-container p-3">
                <div class="d-flex justify-content-center align-items-center mb-3">
                  <form method="get" class="no-print w-100" style="max-width: 500px;">
                    <div class="row g-2 align-items-end">
                      <div class="col">
                        <input type="date" name="from_date" id="from_date" class="form-control"
                          value="<?= htmlspecialchars($fromDate) ?>">
                      </div>
                      <div class="col">
                        <input type="date" name="to_date" id="to_date" class="form-control"
                          value="<?= htmlspecialchars($toDate) ?>">
                      </div>
                      <div class="col-auto">
                        <button type="submit" class="btn btn-success px-4">
                          <i class="fas fa-sync-alt me-1"></i> Go
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <!-- Summary Buttons -->
            <div class="col-lg-5 mb-3">
              <div class="performance-container p-3">
                <div class="action-buttons no-print">
                  <a href="daily_performance_summary_2.php?id=<?= htmlspecialchars($id) ?>" class="btn btn-success" target="_blank">
                    <i class="fas fa-chart-bar"></i> Summary
                  </a>
                  <?php if (isset($_SESSION['token']) && !empty($_SESSION['token'])): ?>
                    <a href="daily-performance-target.php?id=<?= htmlspecialchars($id) ?>" target="_blank" class="btn btn-success">
                      <i class="fas fa-bullseye"></i> Cleanliness report Target
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Print Button -->
            <div class="col-lg-2 mb-3">
              <div class="performance-container p-3 text-center">
                <button class="btn btn-primary no-print" style="background: #3c8dbc !important;"
                  onclick="printPerformanceContainer()">Print</button>
                <script>
                  function printPerformanceContainer() {
                    var containers = document.querySelectorAll('.performance-container');
                    var printContents = '';
                    containers.forEach(function (container) { printContents += container.outerHTML; });
                    var originalContents = document.body.innerHTML;
                    document.body.innerHTML = printContents;
                    window.print();
                    document.body.innerHTML = originalContents;
                  }
                </script>
              </div>
            </div>
          </div>

          <!-- Loading indicator -->
          <div id="loading-indicator" class="loading no-print">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Calculating performance data...</p>
          </div>

          <!-- Report Container - will be shown after loading -->
          <div class="report-container" id="report-container" style="display: none;">
            <?php
            foreach ($datesArray as $dateObj) {
              $currentDate = $dateObj->format("Y-m-d");

              // 💡 IMPORTANT: reset per-day variables here
              $dayToken = null;         // <-- ensures token doesn't carry over to other dates
              $division = '-';
              $station = '-';
              $contractor = '-';

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
                bas.tokenId AS tokenId,
                CONCAT(SUBSTRING(bp.db_pageChoice2, INSTR(bp.db_pageChoice2, '@')+1),'%') AS percentage_weightage
              FROM 
                baris_param bap
                INNER JOIN Daily_Performance_Log bas ON bap.paramId = bas.db_surveyParamId
                INNER JOIN baris_page bp ON bas.db_surveyPageId = bp.pageId
                INNER JOIN baris_station bs ON bas.db_surveyStationId = bs.stationId
                INNER JOIN baris_organization bo ON bas.OrgID = bo.OrgID
                INNER JOIN baris_division bd ON bas.DivisionId = bd.DivisionId
              WHERE 
                DATE(bas.created_date) = '{$conn->real_escape_string($currentDate)}'
                AND bas.db_surveyStationId = '{$conn->real_escape_string($station_id)}'
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
                  // store FIRST token for the day only
                  if ($dayToken === null && !empty($row['tokenId'])) {
                    $dayToken = $row['tokenId'];
                  }

                  $task = trim($row['Description_of_Items']);
                  $percentage = $row['percentage_weightage'];
                  $frequency = $row['Frequency'];
                  $status = ($row['Quality_of_done_work'] == 1 ? 'Y' : 'N');

                  // Expecting paramName like "Shift 1", "Shift 2", "Shift 3"
                  $shift = strtolower(str_replace(' ', '_', $row['task'])); // shift_1 / shift_2 / shift_3
                  if (!in_array($shift, ['shift_1','shift_2','shift_3'])) {
                    // fallback if paramName is unexpected; do not break table
                    $shift = 'shift_1';
                  }

                  $auditorName = $row['auditor_name'];

                  // Unique by task + percentage
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
                  if (!in_array($auditorName, $auditors[$shift]) && !empty($auditorName)) {
                    $auditors[$shift][] = $auditorName;
                  }

                  // Store division/station/org from row
                  $division = $row['division_name'];
                  $station = $row['station_name'];
                  $contractor = $row['organisation_name'];
                }
                ?>
                <div class="performance-container mb-4">
                  <div class="report-header">
                    <h3 class="text-center">
                      Daily performance log book for cleaning schedule for environmental sanitation,
                      mechanized cleaning and housekeeping contract at Tirupati <?= htmlspecialchars($station) ?> Railway Station
                    </h3>
                  </div>

                  <div class="meta-info">
                    <span><i class="far fa-calendar-alt me-1"></i> <strong>Date:</strong> <?= date('d M Y', strtotime($currentDate)) ?></span>
                    <span><i class="fas fa-building me-1"></i> <strong>Division:</strong> <?= htmlspecialchars($division) ?></span>
                    <span><i class="fas fa-map-marker-alt me-1"></i> <strong>Station:</strong> <?= htmlspecialchars($station) ?></span>
                    <span><i class="fas fa-user-tie me-1"></i> <strong>Contractor:</strong> <?= htmlspecialchars($contractor) ?></span>
                    <span><i class="fas fa-key me-1"></i> <strong>TokenId:</strong> <?= htmlspecialchars($dayToken ?? '-') ?></span>
                  </div>

                  <div class="table-responsive">
                    <table class="data-table">
                      <thead>
                        <tr>
                          <th rowspan="2" style="width: 1%;">S.No</th>
                          <th rowspan="2" style="width: 35%;">Description of Items</th>
                          <th rowspan="2" style="width: 6%;">App. Quantity</th>
                          <th rowspan="2" style="width: 20%;">Frequency</th>
                          <th rowspan="2" style="width:5%;">Percentage Weightage</th>
                          <th colspan="3">Shifts</th>
                          <th rowspan="2" style="width:1%">Remarks</th>
                        </tr>
                        <tr>
                          <th style="width:3%">Shift 1<br>(Y/N)</th>
                          <th style="width:3%">Shift 2<br>(Y/N)</th>
                          <th style="width:3%">Shift 3<br>(Y/N)</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $sn = 1;
                        foreach ($tasks as $taskData): ?>
                          <tr>
                            <td><?= $sn++ ?></td>
                            <td class="desc"><?= htmlspecialchars($taskData['task']) ?></td>
                            <td><?= htmlspecialchars($taskData['quantity']) ?></td>
                            <td><?= htmlspecialchars($taskData['frequency']) ?></td>
                            <td><?= htmlspecialchars($taskData['percentage']) ?></td>
                            <td><?= $taskData['shift_1'] ?: '-' ?></td>
                            <td><?= $taskData['shift_2'] ?: '-' ?></td>
                            <td><?= $taskData['shift_3'] ?: '-' ?></td>
                            <td><?= htmlspecialchars($taskData['remarks']) ?></td>
                          </tr>
                        <?php endforeach; ?>

                        <!-- Final rows for auditor names and signatures -->
                        <tr>
                          <td style="width:9%;" colspan="5"><strong>Name of Auditor</strong></td>
                          <td style="width:3%;"><strong><?= !empty($auditors['shift_1']) ? htmlspecialchars($auditors['shift_1'][0]) : '-' ?></strong></td>
                          <td style="width:3%;"><strong><?= !empty($auditors['shift_2']) ? htmlspecialchars($auditors['shift_2'][0]) : '-' ?></strong></td>
                          <td style="width:3%;"><strong><?= !empty($auditors['shift_3']) ? htmlspecialchars($auditors['shift_3'][0]) : '-' ?></strong></td>
                          <td></td>
                        </tr>
                        <tr>
                          <td colspan="5"><strong>Signature of On DUTY SUPERVISOR</strong></td>
                          <td></td><td></td><td></td><td></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <?php
              } // end if data exists
            } // end foreach loop
            ?>
          </div>
        </div>
      </div>
    </main>
    <?php include "footer.php" ?>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      // Hide loading indicator and show report after page has loaded
      setTimeout(function () {
        document.getElementById('loading-indicator').style.display = 'none';
        document.getElementById('report-container').style.display = 'block';
      }, 500);
    });
  </script>
</body>
</html>
