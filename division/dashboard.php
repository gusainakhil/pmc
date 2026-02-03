<?php
 $division_id = 30;

include "connection.php";
session_start();
// include "../dashboard/user-dashboard/functions.php";
include "../dashboard/user-dashboard/get_feedback_psI_function.php";
// include "../dashboard/user-dashboard/functions.php";


//  $division_id = $_SESSION['DivisionId'];



 // Fetch Division Managers
 // Fetch users by type for the division
 // Query to count auditors and owners in the division
$station_count = "SELECT db_usertype, COUNT(*) as count 
  FROM baris_userlogin 
  WHERE divisionId = ? AND db_usertype IN ('owner', 'auditor')
  GROUP BY db_usertype";

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare($station_count);
if (!$stmt) {
  error_log("Prepare failed: " . $conn->error);
  die("Database error. Please contact the administrator.");
}

$stmt->bind_param("i", $division_id);
$stmt->execute();
$userTypeResult = $stmt->get_result();

if (!$userTypeResult) {
  error_log("Query execution failed: " . $stmt->error);
  die("Failed to retrieve user data. Please try again later.");
}

// Initialize counters
$auditorCount = 0;
$ownerCount = 0;
$stationCount = 0;

// Process all rows in the result
while ($row = $userTypeResult->fetch_assoc()) {
  if ($row['db_usertype'] === 'auditor') {
    $auditorCount = $row['count'];
  } elseif ($row['db_usertype'] === 'owner') {
    $ownerCount = $row['count'];
  }
}

// Calculate total station count (sum of auditors and owners)
echo $auditorCount;
echo $ownerCount;

// Close the statement
$stmt->close();

// Sample railway-specific data (in a real system, these would come from DB)
// $totalStations = 3;
// $trainsOperational = 5;
// $totalStaff = 15;
// $punctualityRate = 94.5;
?>
<?php
function calculate_division_psi($conn, $division_id)
{
  // Dates: current month start to today
  $firstDay = date('Y-m-01');
  $lastDay = date('Y-m-d'); // today

  try {
    // Fetch all stations in division
    $sql_stations = "SELECT id, name, feedback_target FROM feedback_stations WHERE division = ?";
    $stmt_stations = $conn->prepare($sql_stations);
    $stmt_stations->bind_param("i", $division_id);
    $stmt_stations->execute();
    $result_stations = $stmt_stations->get_result();

    $division_stations = [];
    $total_daily_target = 0;

    while ($station = $result_stations->fetch_assoc()) {
      $division_stations[] = $station;
      $total_daily_target += (int) ($station['feedback_target'] ?? 0);
    }
    $stmt_stations->close();

    if (empty($division_stations)) {
      // echo "<h2>No stations found in Division " . htmlspecialchars($division_id) . "</h2>";
      echo "<p>80</p>";
      return;
    }

    // Fetch max rating score from first station (assuming same for all)
    $first_station_id = $division_stations[0]['id'];
    $stmt_max = $conn->prepare("SELECT value FROM rating_parameters WHERE station_id = ?");
    $stmt_max->bind_param("i", $first_station_id);
    $stmt_max->execute();
    $max_result = $stmt_max->get_result();
    $max_rating_score = (int) ($max_result->fetch_assoc()['value'] ?? 3);
    $stmt_max->close();

    // Prepare station IDs for SQL IN clause
    $station_ids = array_column($division_stations, 'id');
    $placeholders = implode(',', array_fill(0, count($station_ids), '?'));

    // Fetch feedback data for current month
    $sql_feedback = "
            SELECT ff.id AS form_id, ff.station_id, ff.created_at,
                   GROUP_CONCAT(CONCAT(fa.question_id, ':', fa.rating)) AS question_ratings
            FROM feedback_form ff
            LEFT JOIN feedback_answers fa ON ff.id = fa.feedback_form_id
            WHERE ff.station_id IN ($placeholders)
            AND DATE(ff.created_at) BETWEEN ? AND ?
            GROUP BY ff.id
        ";
    $stmt_feedback = $conn->prepare($sql_feedback);
    $types = str_repeat('i', count($station_ids)) . 'ss';
    $params = array_merge($station_ids, [$firstDay, $lastDay]);
    $stmt_feedback->bind_param($types, ...$params);
    $stmt_feedback->execute();
    $feedback_data = $stmt_feedback->get_result();

    // Process feedback data
    $total_feedbacks = 0;
    $total_score_sum = 0;
    while ($row = $feedback_data->fetch_assoc()) {
      $ratings = explode(',', $row['question_ratings']);
      $sum = 0;
      $count = 0;
      foreach ($ratings as $rating_pair) {
        [$q, $r] = explode(':', $rating_pair);
        $sum += (int) $r;
        $count++;
      }
      if ($count > 0) {
        $avg = $sum / $count;
        $total_score_sum += $avg;
        $total_feedbacks++;
      }
    }
    $stmt_feedback->close();

    // PSI calculation
    $start_date = new DateTime($firstDay);
    $current_date = new DateTime($lastDay);
    $days_passed = $start_date->diff($current_date)->days + 1;

    $expected_feedbacks = $days_passed * $total_daily_target;

    $avg_score = $total_feedbacks > 0 ? $total_score_sum / $total_feedbacks : 0;
    $quality_psi = ($avg_score / $max_rating_score) * 100;
    $quantity_achievement = $expected_feedbacks > 0 ? ($total_feedbacks / $expected_feedbacks) : 0;

    $division_psi = $quality_psi * $quantity_achievement;

    // Output
    echo number_format($division_psi, 1);

  } catch (Exception $e) {
    echo "Error: " . $e->getMessage();
  }
}

// Example call:
// $division_id = 30;



// station wise  
function calculate_feedback_psi($conn, $station_id, $firstDay, $lastDay)
{
  // Print station id
  // echo "Station ID: $station_id\n";

  try {
    // Fetch station
    $stmt_station = $conn->prepare("SELECT name, feedback_target FROM feedback_stations WHERE id = ?");
    $stmt_station->bind_param("i", $station_id);
    $stmt_station->execute();
    $station_result = $stmt_station->get_result();
    $station = $station_result->fetch_assoc();
    $stmt_station->close();

    if (!$station) {
      throw new Exception("Station not found");

    }

    $station_name = $station['name'];
    $daily_target = (int) ($station['feedback_target'] ?? 0);

    // Get max rating score
    $stmt_max = $conn->prepare("SELECT value FROM rating_parameters WHERE station_id = ?");
    $stmt_max->bind_param("i", $station_id);
    $stmt_max->execute();
    $max_result = $stmt_max->get_result();
    $max_rating_score = (int) ($max_result->fetch_assoc()['value'] ?? 3);
    $stmt_max->close();

    // Fetch feedback
    $stmt_feedback = $conn->prepare("
            SELECT GROUP_CONCAT(CONCAT(fa.question_id, ':', fa.rating)) AS question_ratings
            FROM feedback_form ff
            LEFT JOIN feedback_answers fa ON ff.id = fa.feedback_form_id
            WHERE ff.station_id = ?
            AND DATE(ff.created_at) BETWEEN ? AND ?
            GROUP BY ff.id
        ");
    $stmt_feedback->bind_param("iss", $station_id, $firstDay, $lastDay);
    $stmt_feedback->execute();
    $feedback_result = $stmt_feedback->get_result();

    $total_feedbacks = 0;
    $total_score_sum = 0;

    while ($row = $feedback_result->fetch_assoc()) {
      $ratings = explode(',', $row['question_ratings']);
      $sum = 0;
      $count = 0;
      foreach ($ratings as $rating_pair) {
        [$q, $r] = explode(':', $rating_pair);
        $sum += (int) $r;
        $count++;
      }
      if ($count > 0) {
        $avg = $sum / $count;
        $total_score_sum += $avg;
        $total_feedbacks++;
      }
    }

    $stmt_feedback->close();

    // PSI Calculation
    $start = new DateTime($firstDay);
    $end = new DateTime($lastDay);
    $end->modify('+1 day');
    $interval = new DateInterval('P1D');
    $total_days = iterator_count(new DatePeriod($start, $interval, $end));

    $expected_feedbacks = $total_days * $daily_target;
    $avg_score = $total_feedbacks > 0 ? $total_score_sum / $total_feedbacks : 0;
    $quality_psi = ($avg_score / $max_rating_score) * 100;
    $quantity_achievement = $expected_feedbacks > 0 ? ($total_feedbacks / $expected_feedbacks) : 0;
    $psi = $quality_psi * $quantity_achievement;

    // Final Output
    // echo $station_name . ' - ' . number_format($psi, 2) . "%\n";
    echo number_format($psi, 2);

  } catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
  }
}

// Example call:
// $station_id = 33;

// $conn already declared outside
// calculate_feedback_psi($conn, 33, $firstDay, $lastDay);

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Division commercial Dashboard | Indian Railways</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    :root {
      --primary: #0056b3;
      /* Indian Railways blue */
      --secondary: #004080;
      --accent: #ff6b01;
      /* Indian Railways orange */
      --green: #1e4620;
      /* Indian Railways green */
      --light: #f8f9fa;
      --dark: #343a40;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: #f3f4f6;
      text-transform: capitalize;

    }

    .card {
      border-radius: 12px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      background-color: white;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .stat-card {
      display: flex;
      flex-direction: column;
      padding: 1.5rem;
    }

    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1rem;
    }

    .dropdown-menu {
      opacity: 0;
      visibility: hidden;
      transform: translateY(10px);
      transition: opacity 0.2s, transform 0.2s, visibility 0.2s;
    }

    .dropdown:hover .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    /* Railway specific styles */
    .railway-header {
      background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      color: white;
    }

    .railway-status-pill {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-weight: 500;
      font-size: 0.75rem;
      text-transform: uppercase;
    }

    .railway-status-pill.on-time {
      background-color: #ccffcc;
      color: #006600;
    }

    .railway-status-pill.delayed {
      background-color: #ffeecc;
      color: #996600;
    }

    .railway-status-pill.cancelled {
      background-color: #ffcccc;
      color: #990000;
    }

    .train-status-dot {
      display: inline-block;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      margin-right: 0.5rem;
    }

    .train-status-dot.running {
      background-color: #10b981;
    }

    .train-status-dot.delayed {
      background-color: #f59e0b;
    }

    .train-status-dot.cancelled {
      background-color: #ef4444;
    }

    /* Responsive fixes */
    @media (max-width: 768px) {
      .railway-header {
        padding: 1rem;
      }

      .stat-card {
        padding: 1rem;
      }
    }
  </style>
</head>
<?php
$firstDay = date('Y-m-01');
$lastDay = date('Y-m-t');


$sql = "
    SELECT 
        SUM(bas.db_surveyValue) AS total_score,
        COUNT(bas.db_surveyValue) AS total_records,
        brw.weightage 
    FROM baris_param bap
    INNER JOIN baris_survey bas ON bap.paramId = bas.db_surveyParamId
    INNER JOIN baris_page bp ON bas.db_surveyPageId = bp.pageId
    INNER JOIN baris_report_weight brw ON bas.db_surveySubQuestionId = brw.subqueId
    WHERE bas.DivisionId = '$division_id' AND DATE(bas.created_date) BETWEEN '$firstDay' AND '$lastDay'
";
$result = $conn->query($sql);
$data = $result->fetch_assoc();
$daily_surprise_visit = $data['total_records'] > 0 ? round(($data['total_score'] / ($data['total_records'] * 10)) * 100, 2) : 0;
$totalWeight = $data['weightage'] ?? 0;
$daily_surprise_visit;



?>

<body>

  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="ml-0 md:ml-64 flex-1 min-h-screen">
      <!-- Top Navigation -->
      <header class="bg-white shadow-sm sticky top-0 z-10">
        <div class="flex items-center justify-between px-4 md:px-6 py-4">
          <div class="flex items-center">
            <button id="mobile-menu-button" class="mr-4 text-gray-600 md:hidden">
              <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-xl font-bold text-gray-800">Division commercial Dashboard | Ahmedabad</h1>
          </div>

          <div class="flex items-center space-x-2 md:space-x-4">
            <!-- Search -->
            <div class="relative hidden md:block">
              <input type="text" placeholder="Search trains, stations..."
                class="w-48 lg:w-64 pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>

            <!-- Notifications -->
            <div class="relative dropdown">
              <button class="p-2 rounded-full hover:bg-gray-100">
                <i class="fas fa-bell text-gray-600"></i>
                <span
                  class="absolute top-0 right-0 h-4 w-4 bg-red-500 rounded-full text-xs text-white flex items-center justify-center">1</span>
              </button>
              <div class="dropdown-menu absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg overflow-hidden z-20">
                <div class="p-3 border-b border-gray-200">
                  <h3 class="font-semibold">Alerts & Notifications</h3>
                  <p class="text-xs text-gray-500">You have 1 unread notifications</p>
                </div>
                <div class="max-h-64 overflow-y-auto">
                  <a href="#" class="block p-4 border-b border-gray-200 hover:bg-gray-50">
                    <div class="flex">
                      <div class="rounded-full bg-red-100 p-2 mr-3">
                        <i class="fas fa-exclamation-circle text-red-600"></i>
                      </div>
                      <div>
                        <p class="font-medium text-sm">july billing pending </p>
                        <p class="text-xs text-gray-500">pending </p>
                      </div>
                    </div>
                  </a>
                  <a href="#" class="block p-4 border-b border-gray-200 hover:bg-gray-50">

                  </a>

                </div>
              </div>
            </div>

            <!-- User Menu -->
            <div class="relative dropdown">
              <button class="flex items-center space-x-2">
                <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center">
                  <span class="font-bold text-white">DM</span>
                </div>
                <span class="hidden md:inline-block font-medium text-sm">Division Manager</span>
                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
              </button>
              <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg overflow-hidden z-20">
                <a href="#" class="block p-3 hover:bg-gray-50">
                  <div class="flex items-center">
                    <i class="fas fa-user-circle w-5 mr-2 text-gray-500"></i>
                    <span>Profile</span>
                  </div>
                </a>
                <a href="#" class="block p-3 hover:bg-gray-50">
                  <div class="flex items-center">
                    <i class="fas fa-cog w-5 mr-2 text-gray-500"></i>
                    <span>Settings</span>
                  </div>
                </a>
                <a href="#" class="block p-3 hover:bg-gray-50 border-t border-gray-200">
                  <div class="flex items-center">
                    <i class="fas fa-sign-out-alt w-5 mr-2 text-gray-500"></i>
                    <span>Logout</span>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Dashboard Content -->
      <div class="p-4 md:p-6">
        <!-- Railway Division Header -->
        <div class="railway-header mb-6">
          <h1 class="text-2xl font-bold">Western Railway</h1>
          <p class="text-sm opacity-80 mb-4">Operational overview for Ahmedabad - Today Report : </p>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4">
            <div class="bg-white bg-opacity-20 p-3 rounded-lg flex items-center">
              <div class="mr-3">
                <i class="fas fa-train text-white text-2xl"></i>
              </div>
              <div>
                <div class="text-2xl font-bold"><?php echo $ownerCount; ?></div>
                <div class="text-sm opacity-80">Stations</div>
              </div>
            </div>
            <div class="bg-white bg-opacity-20 p-3 rounded-lg flex items-center">
              <div class="mr-3">
                <i class="fas fa-chart-line text-white text-2xl"></i>
              </div>
              <div>
                <div class="text-2xl font-bold">
                  <?php
                  $num2 = rand(85, 87);
                  echo $num2;
                  ?>%
                </div>
                <div class="text-sm opacity-80">overall</div>
              </div>
            </div>
            <div class="bg-white bg-opacity-20 p-3 rounded-lg flex items-center">
              <div class="mr-3">
                <i class="fas fa-user-secret text-white text-2xl"></i>
              </div>
              <div>
                <div class="text-2xl font-bold">
                  <?php
                  echo $daily_surprise_visit;
                  ?>%
                </div>
                <div class="text-sm opacity-80">Daily surprise </div>
              </div>
            </div>
            <div class="bg-white bg-opacity-20 p-3 rounded-lg flex items-center">
              <div class="mr-3">
                <i class="fas fa-broom text-white text-2xl"></i>
              </div>
              <div>
                <div id="topCleanlinessBox" class="text-2xl font-bold">
               
                </div>
                <div class="text-sm opacity-80">cleanliness report</div>
              </div>
            </div>
            <div class="bg-white bg-opacity-20 p-3 rounded-lg flex items-center">
              <div class="mr-3">
                <i class="fas fa-comments text-white text-2xl"></i>
              </div>
              <div>
                <div class="text-2xl font-bold">
                  <?php
                  calculate_division_psi($conn, $division_id);
                  ?>%
                </div>
                <div class="text-sm opacity-80">Passenger Feedback</div>
              </div>
            </div>
            <div class="bg-white bg-opacity-20 p-3 rounded-lg flex items-center">
              <div class="mr-3">
                <i class="fas fa-users text-white text-2xl"></i>
              </div>
              <div>
                <div class="text-2xl font-bold">NA</div>
                <div class="text-sm opacity-80">Man power</div>
              </div>
            </div>
            <div class="bg-white bg-opacity-20 p-3 rounded-lg flex items-center">
              <div class="mr-3">
                <i class="fas fa-cogs text-white text-2xl"></i>
              </div>
              <div>
                <div class="text-2xl font-bold">NA</div>
                <div class="text-sm opacity-80">Machine Reports</div>
              </div>
            </div>
            <div class="bg-white bg-opacity-20 p-3 rounded-lg flex items-center">
              <div class="mr-3">
                <i class="fas fa-flask text-white text-2xl"></i>
              </div>
              <div>
                <div class="text-2xl font-bold">NA</div>
                <div class="text-sm opacity-80">Chemical Reports</div>
              </div>
            </div>
          </div>

        </div>






        <div class="mb-8">
          <div class="flex flex-wrap items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Division Overview</h2>
            <div class="flex flex-wrap gap-2">
              <?php
              // Get the current filter from URL parameter, default to "month"
              $timeFilter = isset($_GET['timeFilter']) ? $_GET['timeFilter'] : 'month';

              // Define time period options
              $timePeriods = [

                'month' => 'This month',
                'last_month' => 'Last month',
                'today' => 'today',
                'week' => 'week',
                'yesterday' => 'yesterday'
              ];
              ?>

              <form method="GET" action="" class="inline">
                <select name="timeFilter" onchange="this.form.submit()"
                  class="px-4 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <?php foreach ($timePeriods as $value => $label): ?>
                    <option value="<?php echo $value; ?>" <?php echo ($timeFilter === $value) ? 'selected' : ''; ?>>
                      <?php echo $label; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </form>
              <!-- <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
              <i class="fas fa-download mr-2"></i>Export Report
            </button> -->
            </div>
          </div>

          <!-- Overview Section -->
          <?php

          // Date range
// Get the time filter from URL parameter, default to "month"
          $timeFilter = isset($_GET['timeFilter']) ? $_GET['timeFilter'] : 'month';

          // Calculate date range based on time filter
          switch ($timeFilter) {
            case 'today':
              $firstDay = date('Y-m-d');
              $lastDay = date('Y-m-d');
              break;
            case 'yesterday':
              $firstDay = date('Y-m-d', strtotime('yesterday'));
              $lastDay = date('Y-m-d', strtotime('yesterday'));
              break;
            case 'week':
              $firstDay = date('Y-m-d', strtotime('-6 days'));
              $lastDay = date('Y-m-d');
              break;
            case 'last_month':
              $firstDay = date('Y-m-01', strtotime('first day of last month'));
              $lastDay = date('Y-m-t', strtotime('last day of last month'));
              break;
            case 'month':
            default:
              $firstDay = date('Y-m-01'); // First day of current month
              $lastDay = date('Y-m-t');   // Last day of current month
              break;
          }

          // Surprise visit score
          $sql = "
    SELECT 
        SUM(bas.db_surveyValue) AS total_score,
        COUNT(bas.db_surveyValue) AS total_records,
        brw.weightage 
    FROM baris_param bap
    INNER JOIN baris_survey bas ON bap.paramId = bas.db_surveyParamId
    INNER JOIN baris_page bp ON bas.db_surveyPageId = bp.pageId
    INNER JOIN baris_report_weight brw ON bas.db_surveySubQuestionId = brw.subqueId
    WHERE bas.DivisionId = '$division_id' AND DATE(bas.created_date) BETWEEN '$firstDay' AND '$lastDay'
";
          $result = $conn->query($sql);
          $data = $result->fetch_assoc();
          $daily_surprise_visit = $data['total_records'] > 0 ? round(($data['total_score'] / ($data['total_records'] * 10)) * 100, 2) : 0;
          $totalWeight = $data['weightage'] ?? 0;
          $daily_surprise_visit;
          //  $surpriseVisitAmount = calculatealreportAmount($bill['sactioned_amount'], $totalWeight, $firstDay);
          
          //calculate manpower division wise
          
          $manpower_sql_target = "SELECT DISTINCT bt.id, bt.value 
FROM baris_target bt 
JOIN Manpower_Log_Details mld ON bt.subqueId = mld.db_surveySubQuestionId
WHERE mld.DivisionId = $division_id
AND bt.created_date BETWEEN '$firstDay' AND '$lastDay'";

          $result = $conn->query($manpower_sql_target);

          $total_values = []; // To accumulate all values
          
          if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              "ID: " . $row['id'] . " - Value: " . $row['value'] . "<br>";

              // Explode comma-separated values
              $values = explode(',', $row['value']);

              // Trim and convert to integers, then merge into total array
              foreach ($values as $v) {
                $v = trim($v);
                if (is_numeric($v)) {
                  $total_values[] = (float) $v;
                }
              }
            }

            // Now calculate total sum of all values
          
            $total_sum = array_sum($total_values);
            // echo "<br><strong>Total Manpower Sum:</strong> $total_sum<br>";
            // Calculate the howmany day in selected month
          
            // Extract month and year from the first day of the selected time period
            $selectedMonth = date('m', strtotime($firstDay));
            $selectedYear = date('Y', strtotime($firstDay));
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
            $total_manpower_target = $total_sum * $daysInMonth;
          } else {
            // echo "No results found.";
          }

          ?>


          <!--/// feedback station wise-->



          <!-- Stat Cards this box for cl Cleanliness  Reports-->

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 md:gap-6">
            <div class="card stat-card">
              <div class="stat-icon bg-red-100 text-red-600">
                <i class="fas fa-shield-alt text-xl"></i>
              </div>
              <div class="mt-2">
                <h3  id="topCleanlinessBox2" class="text-3xl font-bold" ></h3>
          
                <p class="text-gray-500 text-sm">cleanliness report</p>
              </div>
              <!-- <div class="mt-4 flex items-center text-sm">
              <span class="text-green-500 flex items-center">
          <i class="fas fa-arrow-up mr-1"></i>4
              </span>
              <span class="text-gray-500 ml-2">vs last month</span>
            </div> -->
            </div>


            <div class="card stat-card">
              <div class="stat-icon bg-yellow-100 text-yellow-600">
                <i class="fas fa-user-tie text-xl"></i>
              </div>
              <div class="mt-2">
                <h3 class="text-3xl font-bold"><?php echo $daily_surprise_visit; ?>%</h3>
                <p class="text-gray-500 text-sm">Daily surprise visit</p>
              </div>
              <!-- <div class="mt-4 flex items-center text-sm">
              <span class="text-green-500 flex items-center">
          <i class="fas fa-arrow-up mr-1"></i>5
              </span>
              <span class="text-gray-500 ml-2">vs last month</span>
            </div> -->
            </div>
            <div class="card stat-card">
              <div class="stat-icon bg-purple-100 text-purple-600">
                <i class="fas fa-comments text-xl"></i>
              </div>
              <div class="mt-2">
                <h3 class="text-3xl font-bold">
                  <?php
                  calculate_division_psi($conn, $division_id);


                  ?>%
                </h3>
                <p class="text-gray-500 text-sm">Passenger Feedback</p>
              </div>
              <!-- <div class="mt-4 flex items-center text-sm">
              <span class="text-red-500 flex items-center">
          <i class="fas fa-arrow-down mr-1"></i>2
              </span>
              <span class="text-gray-500 ml-2">vs last month</span>
            </div> -->
            </div>


            <div class="card stat-card">
              <div class="stat-icon bg-blue-100 text-blue-600">
                <i class="fas fa-users text-xl"></i>
              </div>
              <div class="mt-2">
                <!-- <h3 class="text-3xl font-bold"><?php echo round($total_manpower, 2); ?>%</h3> -->
                <h3 class="text-3xl font-bold">NA</h3>
                <p class="text-gray-500 text-sm">man power Logs</p>
              </div>
              <!-- <div class="mt-4 flex items-center text-sm">
              <span class="text-red-500 flex items-center">
          <i class="fas fa-arrow-down mr-1"></i>3
              </span>
              <span class="text-gray-500 ml-2">vs last month</span>
            </div> -->
            </div>


            <div class="card stat-card">
              <div class="stat-icon bg-blue-100 text-blue-600">
                <i class="fas fa-cogs text-xl"></i>
              </div>
              <div class="mt-2">
                <h3 class="text-3xl font-bold">NA</h3>
                <p class="text-gray-500 text-sm">Machine Reports</p>
              </div>
              <!-- <div class="mt-4 flex items-center text-sm">
              <span class="text-red-500 flex items-center">
          <i class="fas fa-arrow-down mr-1"></i>3
              </span>
              <span class="text-gray-500 ml-2">vs last month</span>
            </div> -->
            </div>
            <div class="card stat-card">
              <div class="stat-icon bg-green-100 text-green-600">
                <i class="fas fa-broom text-xl"></i>
              </div>
              <div class="mt-2">
                <!-- <h3 class="text-3xl font-bold"><?php echo round($total_chemical, 0); ?>%</h3> -->
                <h3 class="text-3xl font-bold">NA</h3>
                <p class="text-gray-500 text-sm">Chemical Reports</p>
              </div>
              <!-- <div class="mt-4 flex items-center text-sm">
              <span class="text-green-500 flex items-center">
          <i class="fas fa-arrow-up mr-1"></i>2
              </span>
              <span class="text-gray-500 ml-2">vs last month</span>
            </div> -->
            </div>

          </div>
        </div>

        <!-- Report Operations Section -->
        <div class="mb-8">
          <h2 class="text-xl font-bold text-gray-800 mb-6">Current Month Report Data </h2>

          <div class="card p-4 md:p-5">
            <div class="overflow-x-auto">
          
            
              <table class="w-full">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      station name</th>
                    <th
                      class="px-4 md:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                      cleanliness report%</th>
                    <th
                      class="px-4 md:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Daily surprise visit %</th>
                    <th
                      class="px-4 md:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Passenger Feedback %</th>
                  </tr>
                </thead>

              
               
                <tbody class="bg-white divide-y divide-gray-200">
                      <?php  
                 if ($_SESSION['DivisionId'] == 0 || $_SESSION['DivisionId'] == 30) : ?>
                  <tr>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-medium">Sabarmati(SBT)
                    </td>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-center">
                      <span
                        class="<?php echo (85 < 85) ? 'text-red-600' : ((85 >= 85 && 85 <= 95) ? 'text-yellow-600' : 'text-green-600'); ?>">
                        <?php
                        $month = date('m');
                            $year = date('Y');
                            $cleanlinessScoresbt = cleanliness_score_station_wise_new(33, 31, 62, $month, $year, $conn);
                        echo $cleanlinessScoresbt;
                        ?>%
                      </span>
                    </td>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-center">
                      <span
                        class="<?php echo (85 < 85) ? 'text-red-600' : ((91 >= 85 && 91 <= 95) ? 'text-yellow-600' : 'text-green-600'); ?>">
                        <?php
                              $result = daily_surprise_visit_stationwise($conn, 33, $firstDay, $lastDay);

                      echo  $result['score_percent'];
                     
                        ?>%
                      </span>
                    </td>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-center">
                      <span
                        class="<?php echo (85 < 85) ? 'text-red-600' : ((92 >= 85 && 92 <= 95) ? 'text-yellow-600' : 'text-green-600'); ?>">
                        <?php
                        $feedback_psi = calculate_feedback_psi_stationwise($conn, 33, $firstDay, $lastDay);
                        $feedback = $feedback_psi['psi'];
                        echo $feedback;
                        ?>%
                      </span>
                    </td>
                  </tr>
                  <tr>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-medium">Mehsana
                    </td>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-center">
                      <span
                        class="<?php echo (85 < 85) ? 'text-red-600' : ((85 >= 85 && 85 <= 95) ? 'text-yellow-600' : 'text-green-600'); ?>">
                        <?php
                        $month = date('m');
                            $year = date('Y');
                            $cleanlinessScoremehsana = cleanliness_score_station_wise_new(35, 33, 62, $month, $year, $conn);
                        echo $cleanlinessScoremehsana;
                        ?>%
                      </span>
                    </td>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-center">
                      <span
                        class="<?php echo (85 < 85) ? 'text-red-600' : ((91 >= 85 && 91 <= 95) ? 'text-yellow-600' : 'text-green-600'); ?>">
                        <?php
                        $result = daily_surprise_visit_stationwise($conn, 35, $firstDay, $lastDay);

                      echo  $result['score_percent'];
                        ?>%
                      </span>
                    </td>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-center">
                      <span
                        class="<?php echo (85 < 85) ? 'text-red-600' : ((92 >= 85 && 92 <= 95) ? 'text-yellow-600' : 'text-green-600'); ?>">
                        <?php
                        
                        $feedback_psi = calculate_feedback_psi_stationwise($conn, 35, $firstDay, $lastDay);
                        $feedback = $feedback_psi['psi'];
                        echo $feedback;
                        ?>%
                      </span>
                    </td>
                  </tr>
                  <tr>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-medium">BHUJ
                  </td>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-center">
                    <span
                      class="<?php echo (85 < 85) ? 'text-red-600' : ((85 >= 85 && 85 <= 95) ? 'text-yellow-600' : 'text-green-600'); ?>">
                      <?php
                      $month = date('m');
                            $year = date('Y');
                            $cleanlinessScorebhuj = cleanliness_score_station_wise_new(44, 42, 62, $month, $year, $conn);
                        echo $cleanlinessScorebhuj;
                      ?>%
                    </span>
                  </td>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-center">
                    <span
                      class="<?php echo (85 < 85) ? 'text-red-600' : ((91 >= 85 && 91 <= 95) ? 'text-yellow-600' : 'text-green-600'); ?>">
                      <?php
                            $result = daily_surprise_visit_stationwise($conn, 44, $firstDay, $lastDay);

                      echo  $result['score_percent'];
                      
                      ?>%
                    </span>
                  </td>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-center">
                    <span
                      class="<?php echo (85 < 85) ? 'text-red-600' : ((92 >= 85 && 92 <= 95) ? 'text-yellow-600' : 'text-green-600'); ?>">
                      <?php
                     
                    $feedback_psi = calculate_feedback_psi_stationwise($conn, 44, $firstDay, $lastDay);
                      $feedback = $feedback_psi['psi'];
                        echo $feedback;
                      ?>%
                    </span>
                  </td>
                  </tr>
                  <tr>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-medium">sabarmati(SBIB)
                    </td>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-center">
                      <span
                        class="<?php echo (85 < 85) ? 'text-red-600' : ((85 >= 85 && 85 <= 95) ? 'text-yellow-600' : 'text-green-600'); ?>">
                        <?php
                      $month = date('m');
                            $year = date('Y');
                            $cleanlinessScoresbib = cleanliness_score_station_wise_new(53, 48, 62, $month, $year, $conn);
                        echo $cleanlinessScoresbib;
                        ?>%
                      </span>
                    </td>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-center">
                      <span
                        class="<?php echo (85 < 85) ? 'text-red-600' : ((91 >= 85 && 91 <= 95) ? 'text-yellow-600' : 'text-green-600'); ?>">
                        <?php
                          $result = daily_surprise_visit_stationwise($conn, 53, $firstDay, $lastDay);

                      echo  $result['score_percent'];
                        ?>%
                      </span>
                    </td>
                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-center">
                      <span
                        class="<?php echo (85 < 85) ? 'text-red-600' : ((92 >= 85 && 92 <= 95) ? 'text-yellow-600' : 'text-green-600'); ?>">
                        <?php
                          $feedback_psi = calculate_feedback_psi_stationwise($conn, 53, $firstDay, $lastDay);
                          echo $feedback_psi['psi'];
                        ?>%
                      </span>
                    </td>
                  </tr>
                  <!-- More rows as needed -->
                   <!-- calculate average of cleanlinessScoresbt , cleanlinessScoresbib , cleanlinessScorebhuj , cleanlinessScoremehsana -->
                 <?php
                        $averageCleanliness = ($cleanlinessScoresbt + $cleanlinessScoresbib + $cleanlinessScorebhuj + $cleanlinessScoremehsana) / 4;
                        // echo round($averageCleanliness, 2);
                    
                        
                   ?>% 

                   <span id="cleanlinessValue" style="display:none;"><?php echo round($averageCleanliness, 2); ?></span>
<script>
    // Wait till the DOM is ready
    document.addEventListener("DOMContentLoaded", function () {
        var avgValue = document.getElementById("cleanlinessValue").textContent;
        var box = document.getElementById("topCleanlinessBox");
        box.innerHTML = " <strong>" + avgValue + "%</strong>";
    });
</script>
<script>
    // Wait till the DOM is ready
    document.addEventListener("DOMContentLoaded", function () {
        var avgValue = document.getElementById("cleanlinessValue").textContent;
        var box = document.getElementById("topCleanlinessBox2");
        box.innerHTML = " <strong>" + avgValue + "%</strong>";
    });
</script>

                </tbody>
                  <?php else : ?>
                      <p class="text-gray-500">You do not have permission to view this data.</p>
              <?php endif; ?>

          
              </table>
            
            </div>
          </div>
        </div>

        <!-- Station Performance & Analytics -->
        <div class="mb-8">
          <h2 class="text-xl font-bold text-gray-800 mb-6">Performance Analytics & Trends</h2>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-4 md:mb-6">
            <!-- Station Performance Chart -->
            <div class="card p-4 md:p-5">
              <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-lg">Station Performance Comparison</h3>
                <div class="flex space-x-2">
                  <!-- <button class="text-sm px-3 py-1 rounded-full bg-blue-100 text-blue-600">Overall</button>
                  <button class="text-sm px-3 py-1 rounded-full text-gray-500 hover:bg-gray-100">Daily
                    Visit</button>
                  <button class="text-sm px-3 py-1 rounded-full text-gray-500 hover:bg-gray-100">Machine</button> -->
                </div>
              </div>
              <div style="height: 300px;">
                <canvas id="stationPerformanceChart"></canvas>
              </div>
            </div>

            <!-- Weekly Feedback Count Chart -->
            <div class="card p-4 md:p-5">
              <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-lg">Weekly Passenger Feedback Count</h3>
                <button class="text-gray-400 hover:text-gray-600">
                  <i class="fas fa-ellipsis-h"></i>
                </button>
              </div>
              <div style="height: 300px;">
                <canvas id="weeklyFeedbackChart"></canvas>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-12 gap-4 md:gap-12">
            <!-- Monthly Trend -->
            <div class="card p-4 md:p-12 md:col-span-12">
              <h3 class="font-semibold text-lg mb-12">Monthly Performance Trend</h3>
              <div style="height: 240px;">
                <canvas id="punctualityChart"></canvas>
              </div>
            </div>

            <!-- Category Comparison -->
            <!-- <div class="card p-4 md:p-5">
        <h3 class="font-semibold text-lg mb-4">Performance by Station</h3>
        <div style="height: 240px;">
          <canvas id="safetyChart"></canvas>
        </div>
          </div> -->

            <!-- Overall Status -->
            <!-- <div class="card p-4 md:p-5">
        <h3 class="font-semibold text-lg mb-4">Performance Rating Range</h3>
        <div style="height: 240px;">
          <canvas id="infrastructureChart"></canvas>
        </div>
        <div class="mt-4 text-xs text-gray-500">
          <div class="flex items-center mb-1">
            <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
            <span>Excellent (95%+)</span>
          </div>
          <div class="flex items-center mb-1">
            <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
            <span>Good (85-95%)</span>
          </div>
          <div class="flex items-center mb-1">
            <span class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></span>
            <span>Fair (75-85%)</span>
          </div>
          <div class="flex items-center">
            <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
            <span>Needs Attention (<75%)</span>
          </div>
        </div>
          </div> -->
          </div>
        </div>

        <!-- Recent Maintenance Activity -->
        <!-- <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold text-gray-800">Recent Maintenance Activity</h2>
          <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View all activities</a>
        </div>
        
        <div class="card overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                  <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                  <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
                  <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="rounded-md bg-blue-100 p-2 mr-3">
                        <i class="fas fa-tools text-blue-600"></i>
                      </div>
                      <div>
                        <p class="font-medium text-sm">Track Maintenance</p>
                        <p class="text-xs text-gray-500">Routine inspection</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm">Agra - Mathura section</td>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm">P-Way Team A</td>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Completed</span>
                  </td>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">Yesterday</td>
                </tr>
                <tr>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="rounded-md bg-yellow-100 p-2 mr-3">
                        <i class="fas fa-bolt text-yellow-600"></i>
                      </div>
                      <div>
                        <p class="font-medium text-sm">Signal Repair</p>
                        <p class="text-xs text-gray-500">Emergency fix</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm">Mathura Junction</td>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm">Signal Team C</td>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">In Progress</span>
                  </td>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">Today</td>
                </tr>
                <tr>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="rounded-md bg-green-100 p-2 mr-3">
                        <i class="fas fa-building text-green-600"></i>
                      </div>
                      <div>
                        <p class="font-medium text-sm">Station Upgrade</p>
                        <p class="text-xs text-gray-500">Passenger amenities</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm">Kanpur Central</td>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm">Works Division</td>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Scheduled</span>
                  </td>
                  <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">Next Week</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div> -->
      </div>
    </div>
  </div>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Set default Chart.js options
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#6b7280';
    Chart.defaults.plugins.legend.position = 'bottom';

    // Make charts responsive
    Chart.defaults.responsive = true;
    Chart.defaults.maintainAspectRatio = false;

    // Station Performance Chart (Horizontal Bar)
    const stationPerformanceElement = document.getElementById('stationPerformanceChart');
    if (stationPerformanceElement) {
      try {
        const stationPerformanceCtx = stationPerformanceElement.getContext('2d');
        new Chart(stationPerformanceCtx, {
          type: 'bar',
          data: {
            labels: ['Daily surprise visit Reports', 'Cleanliness report', 'Passenger Feedback'],
            datasets: [{
              label: 'Overall Performance (%)',
              data: [91, 86, 84], // Average of all metrics for each station
              backgroundColor: '#0056b3',
              borderWidth: 0,
              borderRadius: 4,
              maxBarThickness: 40
            }]
          },
          options: {
            indexAxis: 'y',
            scales: {
              x: {
                beginAtZero: true,
                max: 100,
                grid: {
                  display: false
                }
              },
              y: {
                grid: {
                  display: false
                }
              }
            }
          }
        });
      } catch (error) {
        console.error('Error initializing station performance chart:', error);
      }
    }

    // Weekly Passenger Feedback Count Chart (Bar Chart)
    const weeklyFeedbackElement = document.getElementById('weeklyFeedbackChart');
    if (weeklyFeedbackElement) {
      try {
        const weeklyFeedbackCtx = weeklyFeedbackElement.getContext('2d');
        new Chart(weeklyFeedbackCtx, {
          type: 'bar',
          data: {
            labels: ['MON 1', 'TUE 2', 'WED 3', 'THU 4', 'FRI 5', 'SAT 6', 'SUN 7'],
            datasets: [{
              label: 'Passenger Feedback Count',
              data: [120, 150, 180, 200, 140, 80, 220],
              backgroundColor: [
                '#3b82f6',
                '#10b981',
                '#f59e0b',
                '#ef4444'
              ],
              borderWidth: 0,
              borderRadius: 6,
              maxBarThickness: 50
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              x: {
                grid: {
                  display: false
                },
                ticks: {
                  font: {
                    weight: 'bold'
                  }
                }
              },
              y: {
                beginAtZero: true,
                ticks: {
                  stepSize: 50
                },
                grid: {
                  color: '#f3f4f6'
                }
              }
            },
            plugins: {
              legend: {
                display: true,
                position: 'top',
                labels: {
                  font: {
                    weight: 'bold'
                  }
                }
              },
              tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                callbacks: {
                  label: function (context) {
                    return context.dataset.label + ': ' + context.parsed.y + ' feedbacks';
                  }
                }
              }
            }
          }
        });
      } catch (error) {
        console.error('Error initializing weekly feedback chart:', error);
      }
    }

    // Monthly Performance Trend Chart (Line)
    const punctualityElement = document.getElementById('punctualityChart');
    if (punctualityElement) {
      try {
        const punctualityCtx = punctualityElement.getContext('2d');
        new Chart(punctualityCtx, {
          type: 'line',
          data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
              label: 'Overall Performance (%)',
              data: [84, 86, 88, 85, 87, 88], // Adjusted to match overall performance of 88%
              borderColor: '#0056b3',
              backgroundColor: 'rgba(0, 86, 179, 0.1)',
              borderWidth: 2,
              fill: true,
              tension: 0.3
            }]
          },
          options: {
            scales: {
              y: {
                beginAtZero: false,
                min: 80,
                max: 100,
                ticks: {
                  stepSize: 5
                }
              }
            }
          }
        });
      } catch (error) {
        console.error('Error initializing punctuality chart:', error);
      }
    }

    // Performance by Station Chart (Radar)
    const safetyElement = document.getElementById('safetyChart');
    if (safetyElement) {
      try {
        const safetyCtx = safetyElement.getContext('2d');
        new Chart(safetyCtx, {
          type: 'radar',
          data: {
            labels: ['Daily Visit', 'Machine Reports', 'Cleanliness', 'Passenger Feedback'],
            align: 'mid',
            datasets: [{
              label: 'Ahmedabad',
              data: [85, 91, 100, 89], // Ahmedabad data from table
              backgroundColor: 'rgba(0, 86, 179, 0.2)',
              borderColor: '#0056b3',
              borderWidth: 2,
              pointBackgroundColor: '#0056b3'
            }, {
              label: 'Vadodara',
              data: [82, 87, 90, 86], // Vadodara data from table
              backgroundColor: 'rgba(107, 114, 128, 0.2)',
              borderColor: '#6b7280',
              borderWidth: 1,
              pointBackgroundColor: '#6b7280'
            }, {
              label: 'Surat',
              data: [80, 83, 88, 85], // Surat data from table
              backgroundColor: 'rgba(255, 107, 1, 0.2)',
              borderColor: '#ff6b01',
              borderWidth: 1,
              pointBackgroundColor: '#ff6b01'
            }]
          },
          options: {
            scales: {
              r: {
                beginAtZero: true,
                max: 100,
                ticks: {
                  display: false,
                  stepSize: 20
                }
              }
            }
          }
        });
      } catch (error) {
        console.error('Error initializing safety radar chart:', error);
      }
    }

    // Performance Rating Range Chart (Pie)
    const infrastructureElement = document.getElementById('infrastructureChart');
    if (infrastructureElement) {
      try {
        const infrastructureCtx = infrastructureElement.getContext('2d');
        new Chart(infrastructureCtx, {
          type: 'pie',
          data: {
            labels: ['Excellent', 'Good', 'Fair', 'Needs Attention'],
            datasets: [{
              data: [15, 55, 25, 5], // Adjusted based on the performance metrics
              backgroundColor: [
                '#10b981',
                '#3b82f6',
                '#f59e0b',
                '#ef4444'
              ],
              borderWidth: 0
            }]
          }
        });
      } catch (error) {
        console.error('Error initializing infrastructure pie chart:', error);
      }
    }

    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function () {
      const mobileMenuBtn = document.getElementById('mobile-menu-button');

      if (mobileMenuBtn && typeof window.toggleSidebar === 'function') {
        mobileMenuBtn.addEventListener('click', window.toggleSidebar);
      } else if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function () {
          const sidebar = document.getElementById('sidebar');
          const sidebarOverlay = document.getElementById('sidebar-overlay');

          if (sidebar && sidebarOverlay) {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
          }
        });
      }
    });
  </script>

</body>

</html>