<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
session_start();
include "../../connection.php";
include "functions.php";
include "get_feedback_psI_function.php";

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);

    $sql = "SELECT * FROM baris_userlogin WHERE login_token = '$token' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $_SESSION['userId'] = $row['userId'];
        $_SESSION['stationId'] = $row['StationId'];
        $_SESSION['db_usertype'] = $row['db_usertype'];
        $_SESSION['OrgID'] = $row['OrgID'];
        $_SESSION['token'] = $token; // Store token in session for later use
    }
}
// Fetch station name if stationId is se
if (isset($_SESSION['stationId']) && !empty($_SESSION['stationId'])) {
  $stationId = mysqli_real_escape_string($conn, $_SESSION['stationId']);
  $stationQuery = "SELECT stationName FROM baris_station WHERE stationId = '$stationId'";
  $stationResult = mysqli_query($conn, $stationQuery);
  
  if ($stationResult && mysqli_num_rows($stationResult) > 0) {
    $stationRow = mysqli_fetch_assoc($stationResult);
    $_SESSION['stationName'] = $stationRow['stationName'];
  } else {
    $_SESSION['stationName'] = "Unknown Station";
  }
} else {
  $_SESSION['stationName'] = "No Station Selected";
}

if (!isset($_SESSION['userId']) || empty($_SESSION['userId'])) {
    session_unset();
    session_destroy();
    header("Location: https://pmc.beatleme.co.in/");
    exit();
}

// Calculate metrics for dashboard
$month = date('m');
$year = date('Y');

 $firstDay = date('Y-m-01');
 $lastDay = date('Y-m-t');
$squeld = $_SESSION['daily_performance'];

// Calculate Daily Surprise Visit score
$dailySurpriseVisit = calculate_daily_surprise_visit($conn, $_SESSION['stationId']);

// Calculate Cleanliness Score
$cleanlinessScore = cleanliness_score_station_wise($_SESSION['stationId'], $_SESSION['OrgID'], $squeld, $month, $year, $conn);

// Calculate Feedback score

$feedback_psi=calculate_feedback_psi_stationwise($conn, $_SESSION['stationId'], $firstDay, $lastDay);
$feedback= $feedback_psi['psi'];
?>

<!doctype html>
<html lang="en">
  <!--begin::Head-->
<?php include"head.php" ?>
  <head>
    <!-- Add Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    .body{
        text-transform: capitalize;
        
    }
      #datetime {
        text-align: center;
      }
      #date {
        font-size: 1.5rem;
      }
      #time {
        font-size: 1rem;
        font-weight: bold;
      }
      .chart-container {
        position: relative;
        height: 300px;
        margin-bottom: 30px;
      }
      .dashboard-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 20px;
      }
      .dashboard-card h3 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        font-size: 18px;
      }
      .metric-card {
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 15px;
        text-align: center;
      }
      .metric-value {
        font-size: 24px;
        font-weight: bold;
        margin: 10px 0;
      }
      .metric-label {
        color: #6c757d;
        font-size: 14px;
      }
    </style>
  </head>
  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
      <?php include"header.php" ?>
      <main class="app-main">
        <div class="app-content-header">
          <div class="container-fluid">
            <div class="row">
              <div class="col-12">
                <h6 class="mb-3"> <?php echo " " .$_SESSION['stationName']  ;
                echo $_SESSION['stationId']; ?> </h6>
              </div>
            </div>
          </div>
        </div>
        <div class="app-content">
          <div class="container-fluid">
                          <div class="row">
              <div class="col-md-3">
                <div class="dashboard-card">
                  <div class="metric-card">
                    <div class="metric-label">Overall Score</div>
                    <div class="metric-value"><?php 
                    // Calculate overall score as average of all three metrics
                    $overallScore = ($dailySurpriseVisit + $cleanlinessScore + $feedback) / 3;
                    echo number_format($overallScore, 1);
                    ?>%</div>
                    <div>
                      <span class="badge bg-success">+2.3%</span> vs last month
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="dashboard-card">
                  <div class="metric-card">
                    <div class="metric-label">Daily Surprise Visit</div>
                    <div class="metric-value"><?php echo number_format($dailySurpriseVisit, 1); ?>%</div>
                    <div>
                      <span class="badge bg-danger">-1.5%</span> vs last month
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="dashboard-card">
                  <div class="metric-card">
                    <div class="metric-label">Cleanliness Score</div>
                    <div class="metric-value"><?php echo number_format($cleanlinessScore, 1); ?>%</div>
                    <div>
                      <span class="badge bg-success">+4.2%</span> vs last month
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="dashboard-card">
                  <div class="metric-card">
                    <div class="metric-label">Feedback</div>
                    <?php
                    if (is_numeric($feedback)) {
                      echo '<div class="metric-value">' . number_format($feedback, 1) . '%</div>';
                    } else {
                      echo '<div class="metric-value text-danger">N/A</div>';
                    }
                    ?>
   
        
                    <div>
                      <span class="badge bg-success">+1.7%</span> vs last month
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!--begin::Row-->
            <!--<div class="row">-->
              <!--begin::Col-->
            <!--  <div class="col-lg-2 col-6">-->
                <!--begin::Small Box Widget 1-->
            <!--    <div class="small-box text-bg-primary">-->
            <!--      <div class="inner">-->
            <!--        <h3>1</h3>-->
            <!--        <p> station</p>-->
            <!--      </div>-->
            <!--      <svg-->
            <!--      class="small-box-icon"-->
            <!--      fill="currentColor"-->
            <!--      viewBox="0 0 24 24"-->
            <!--      xmlns="http://www.w3.org/2000/svg"-->
            <!--      aria-hidden="true">-->
            <!--      <path d="M7.5 19.5l-1.5 1.5m10.5-1.5l1.5 1.5M6 3a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V3zm2 0v11h8V3H8zm2 15h4m-5-3h6a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H8a3 3 0 0 0-3 3v11a3 3 0 0 0 3 3zm-1-8h6m-6 3h6" />-->
            <!--  </svg>-->
            <!--    </div>-->
            <!--  </div>-->
            <!--  <div class="col-lg-2 col-6">-->
            <!--    <div class="small-box text-bg-success">-->
            <!--      <div class="inner">-->
            <!--        <h3>53<sup class="fs-5">%</sup></h3>-->
            <!--        <p>Auditors</p>-->
            <!--      </div>-->
            <!--      <svg-->
            <!--        class="small-box-icon"-->
            <!--        fill="currentColor"-->
            <!--        viewBox="0 0 24 24"-->
            <!--        xmlns="http://www.w3.org/2000/svg"-->
            <!--        aria-hidden="true">-->
            <!--        <path-->
            <!--          d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z"></path>-->
            <!--      </svg>-->
            <!--    </div>-->
            <!--  </div>-->
            <!--  <div class="col-lg-2 col-6">-->
            <!--    <div class="small-box text-bg-warning">-->
            <!--      <div class="inner">-->
            <!--        <h3>44</h3>-->
            <!--        <p>Audit</p>-->
            <!--      </div>-->
            <!--      <svg-->
            <!--        class="small-box-icon"-->
            <!--        fill="currentColor"-->
            <!--        viewBox="0 0 24 24"-->
            <!--        xmlns="http://www.w3.org/2000/svg"-->
            <!--        aria-hidden="true">-->
            <!--        <path-->
            <!--          d="M6.25 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM3.25 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM19.75 7.5a.75.75 0 00-1.5 0v2.25H16a.75.75 0 000 1.5h2.25v2.25a.75.75 0 001.5 0v-2.25H22a.75.75 0 000-1.5h-2.25V7.5z"></path>-->
            <!--      </svg>-->
            <!--    </div>-->
            <!--  </div>-->
            <!--  <div class="col-lg-2 col-6">-->
            <!--    <div class="small-box text-bg-danger">-->
            <!--      <div class="inner">-->
            <!--        <h3>65</h3>-->
            <!--        <p>Trains</p>-->
            <!--      </div>-->
            <!--      <svg-->
            <!--        class="small-box-icon"-->
            <!--        fill="currentColor"-->
            <!--        viewBox="0 0 24 24"-->
            <!--        xmlns="http://www.w3.org/2000/svg"-->
            <!--        aria-hidden="true">-->
            <!--        <path-->
            <!--          clip-rule="evenodd"-->
            <!--          fill-rule="evenodd"-->
            <!--          d="M2.25 13.5a8.25 8.25 0 018.25-8.25.75.75 0 01.75.75v6.75H18a.75.75 0 01.75.75 8.25 8.25 0 01-16.5 0z"></path>-->
            <!--        <path-->
            <!--          clip-rule="evenodd"-->
            <!--          fill-rule="evenodd"-->
            <!--          d="M12.75 3a.75.75 0 01.75-.75 8.25 8.25 0 018.25 8.25.75.75 0 01-.75.75h-7.5a.75.75 0 01-.75-.75V3z"></path>-->
            <!--      </svg>-->
            <!--    </div>-->
            <!--  </div>-->
            <!--  <div class="col-lg-2 col-6">-->
            <!--    <div class="small-box text-bg-Light">-->
            <!--      <div class="inner">-->
            <!--        <h3>1</h3>-->
            <!--        <p>Coaches </p>-->
            <!--      </div>-->
            <!--      <svg-->
            <!--      class="small-box-icon"-->
            <!--      fill="currentColor"-->
            <!--      viewBox="0 0 24 24"-->
            <!--      xmlns="http://www.w3.org/2000/svg"-->
            <!--      aria-hidden="true">-->
            <!--      <path d="M7.5 19.5l-1.5 1.5m10.5-1.5l1.5 1.5M6 3a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V3zm2 0v11h8V3H8zm2 15h4m-5-3h6a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H8a3 3 0 0 0-3 3v11a3 3 0 0 0 3 3zm-1-8h6m-6 3h6" />-->
            <!--  </svg>-->
            <!--    </div>-->
            <!--  </div>-->
            <!--  <div class="col-lg-2 col-6">-->
            <!--    <div class="small-box text-bg-dark">-->
            <!--      <div class="inner"> -->
            <!--        <h3>1</h3>-->
            <!--        <p>Platform</p>-->
            <!--      </div>-->
            <!--      <svg-->
            <!--      class="small-box-icon"-->
            <!--      fill="currentColor"-->
            <!--      viewBox="0 0 24 24"-->
            <!--      xmlns="http://www.w3.org/2000/svg"-->
            <!--      aria-hidden="true">-->
            <!--      <path d="M7.5 19.5l-1.5 1.5m10.5-1.5l1.5 1.5M6 3a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V3zm2 0v11h8V3H8zm2 15h4m-5-3h6a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3H8a3 3 0 0 0-3 3v11a3 3 0 0 0 3 3zm-1-8h6m-6 3h6" />-->
            <!--  </svg>-->
            <!--    </div>-->
            <!--  </div>-->
            <!--</div>-->
            
            <!-- Date and Time Display -->
            <div class="row mb-4">
              <div class="col-12">
                <div class="card">
                  <div class="card-body text-center">
                    <div id="datetime">
                      <div id="date"></div>
                      <div id="time"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Charts Row -->
            <div class="row">
              <!-- Monthly Performance Chart -->
              <div class="col-md-6">
                <div class="dashboard-card">
                  <h3>Monthly Performance (2025)</h3>
                  <div class="chart-container">
                    <canvas id="monthlyPerformanceChart"></canvas>
                  </div>
                </div>
              </div>
              
              <!-- Daily Performance Chart -->
              <div class="col-md-6">
                <div class="dashboard-card">
                  <h3>Overall (Last 30 Days)</h3>
                  <div class="chart-container">
                    <canvas id="dailyPerformanceChart"></canvas>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Second Row of Charts -->

            
            <!-- Third Row - Metrics Cards -->

            
            <!-- Additional Chart Row -->
            
          </div>
        </div>
      </main>

      <?php include "footer.php" ?>

    </div>

    <script>
      // Date and Time Update
      function updateDateTime() {
        const now = new Date();
        const dateOptions = {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        };
        const formattedDate = now.toLocaleDateString('en-US', dateOptions);
        const formattedTime = now.toLocaleTimeString('en-US');
        
        document.getElementById('date').textContent = formattedDate;
        document.getElementById('time').textContent = formattedTime;
      }
      
      // Initial call
      updateDateTime();
      // Update every second
      setInterval(updateDateTime, 1000);
      
      // Chart initializations
      document.addEventListener('DOMContentLoaded', function() {
        // Monthly Performance Chart
        const monthlyCtx = document.getElementById('monthlyPerformanceChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
          type: 'bar',
          data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
              label: 'Performance Score (%)',
              data: [75, 82, 79, 84, 88, 86, 91, 89, 84, 87, 83, 85],
              backgroundColor: '#3c8dbc',
              borderColor: '#3c8dbc',
              borderWidth: 1,
             
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                max: 100,
                title: {
                  display: true,
                  text: 'Score (%)'
                }
              },
              x: {
                title: {
                  display: true,
                  text: 'Month'
                }
              }
            }
          }
        });
        
        // Daily Performance Chart
        const dailyCtx = document.getElementById('dailyPerformanceChart').getContext('2d');
        const dailyChart = new Chart(dailyCtx, {
          type: 'line',
          data: {
            labels: ['1 Jun', '2 Jun', '3 Jun', '4 Jun', '5 Jun', '6 Jun', '7 Jun', '8 Jun', '9 Jun', '10 Jun', 
                     '11 Jun', '12 Jun', '13 Jun', '14 Jun', '15 Jun', '16 Jun', '17 Jun', '18 Jun', '19 Jun', '20 Jun',
                     '21 Jun', '22 Jun', '23 Jun', '24 Jun', '25 Jun', '26 Jun', '27 Jun', '28 Jun', '29 Jun', '30 Jun'],
            datasets: [{
              label: 'Daily Score (%)',
              data: [76, 78, 80, 79, 83, 85, 82, 81, 84, 86, 88, 87, 85, 84, 83, 86, 88, 90, 89, 87, 85, 84, 86, 89, 91, 90, 88, 87, 86, 89],
              fill: false,
              borderColor: 'rgba(255, 99, 132, 1)',
              tension: 0.1,
              pointBackgroundColor: 'rgba(255, 99, 132, 1)'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                max: 100,
                title: {
                  display: true,
                  text: 'Score (%)'
                }
              },
              x: {
                title: {
                  display: true,
                  text: 'Date'
                }
              }
            }
          }
        });
        
        // Platform Performance Chart
        const platformCtx = document.getElementById('platformPerformanceChart').getContext('2d');
        const platformChart = new Chart(platformCtx, {
          type: 'radar',
          data: {
            labels: ['Platform 1', 'Platform 2', 'Platform 3', 'Platform 4', 'Platform 5', 'Platform 6'],
            datasets: [{
              label: 'Platform Score (%)',
              data: [85, 78, 92, 84, 76, 88],
              backgroundColor: 'rgba(75, 192, 192, 0.2)',
              borderColor: 'rgba(75, 192, 192, 1)',
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              r: {
                angleLines: {
                  display: true
                },
                suggestedMin: 0,
                suggestedMax: 100
              }
            }
          }
        });
        
        // Categories Breakdown Chart
        const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
        const categoriesChart = new Chart(categoriesCtx, {
          type: 'doughnut',
          data: {
            labels: ['Cleanliness', 'Functionality', 'Safety', 'Staff Performance', 'Passenger Services'],
            datasets: [{
              label: 'Category Score',
              data: [88, 82, 94, 76, 85],
              backgroundColor: [
                'rgba(255, 99, 132, 0.6)',
                'rgba(54, 162, 235, 0.6)',
                'rgba(255, 206, 86, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(153, 102, 255, 0.6)'
              ],
              borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)'
              ],
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'right',
              }
            }
          }
        });
        
        // Train Comparison Chart
        const trainCtx = document.getElementById('trainComparisonChart').getContext('2d');
        const trainChart = new Chart(trainCtx, {
          type: 'bar',
          data: {
            labels: ['Express 101', 'Local 202', 'Express 303', 'Rapid 404', 'Local 505', 'Express 606', 'Rapid 707', 'Local 808'],
            datasets: [{
              label: 'Cleanliness',
              data: [92, 78, 86, 91, 84, 88, 93, 80],
              backgroundColor: 'rgba(255, 99, 132, 0.6)'
            }, {
              label: 'Punctuality',
              data: [88, 85, 79, 94, 81, 87, 90, 82],
              backgroundColor: 'rgba(54, 162, 235, 0.6)'
            }, {
              label: 'Passenger Services',
              data: [82, 75, 88, 86, 79, 84, 85, 77],
              backgroundColor: 'rgba(255, 206, 86, 0.6)'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                max: 100,
                title: {
                  display: true,
                  text: 'Score (%)'
                }
              },
              x: {
                title: {
                  display: true,
                  text: 'Train'
                }
              }
            }
          }
        });
      });
    </script>
  </body>
</html>