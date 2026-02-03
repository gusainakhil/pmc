<?php
session_start();
if (!isset($_SESSION['stationId'])) {
    header("Location: login.php");
    exit();
}
include 'connection.php';

// User data
$user_id = $_SESSION['userId'];
// $user_name = $_SESSION['username'];
$station_id = $_SESSION['stationId'];
?>
<!doctype html>
<html lang="en">
<!--begin::Head-->

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>Feedback</title>
  <!--begin::Primary Meta Tags-->
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="title" content="AdminLTE v4 | Dashboard" />
  <meta name="author" content="ColorlibHQ" />
  <meta name="description"
    content="AdminLTE is a Free Bootstrap 5 Admin Dashboard, 30 example pages using Vanilla JS." />
  <meta name="keywords"
    content="bootstrap 5, bootstrap, bootstrap 5 admin dashboard, bootstrap 5 dashboard, bootstrap 5 charts, bootstrap 5 calendar, bootstrap 5 datepicker, bootstrap 5 tables, bootstrap 5 datatable, vanilla js datatable, colorlibhq, colorlibhq dashboard, colorlibhq admin dashboard" />
  <!--end::Primary Meta Tags-->
  <!--begin::Fonts-->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
    integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q=" crossorigin="anonymous" />
  <!--end::Fonts-->
  <!--begin::Third Party Plugin(OverlayScrollbars)-->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css"
    integrity="sha256-tZHrRjVqNSRyWg2wbppGnT833E/Ys0DHWGwT04GiqQg=" crossorigin="anonymous" />
  <!--end::Third Party Plugin(OverlayScrollbars)-->
  <!--begin::Third Party Plugin(Bootstrap Icons)-->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    integrity="sha256-9kPW/n5nn53j4WMRYAxe9c1rCY96Oogo/MKSVdKzPmI=" crossorigin="anonymous" />
  <!--end::Third Party Plugin(Bootstrap Icons)-->
  <!--begin::Required Plugin(AdminLTE)-->
  <link rel="stylesheet" href="./css/adminlte.css" />

  <!--end::Required Plugin(AdminLTE)-->
  <!-- apexcharts -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
    integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0=" crossorigin="anonymous" />
  <!-- jsvectormap -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css"
    integrity="sha256-+uGLJmmTKOqBr+2E6KDYs/NRsHxSkONXFHUL0fy2O/4=" crossorigin="anonymous" />
  <style>
    #clock {
      font-size: 3rem;
      /* Large font size */
      color: white;
      /* White font color */
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
      /* Text shadow for better visibility */
    }

    #weeklyPerformanceChart {
      max-width: 100%;
      max-height: 100%;
    }
  </style>


</head>
<!--end::Head-->
<!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse bg-body-tertiary">
    
    
  <!--begin::App Wrapper-->
  <div class="app-wrapper">
    <!--begin::Header-->
    <?php
    include 'header.php';
    ?>
    <!--end::Header-->
    <!--begin::Sidebar-->
    <?php
    include 'sidebar.php';
    ?>
    <!--end::Sidebar-->
    <!--begin::App Main-->
    <main class="app-main">
      <!--begin::App Content Header-->
      <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Row-->
          <div class="row">
            <div class="col-sm-6">
              <h4 class="mb-0">Dashboard</h4>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-end">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
              </ol>
            </div>
          </div>
          <!--end::Row-->
        </div>
        <!--end::Container-->
      </div>
      <!--end::App Content Header-->
      <!--begin::App Content-->
      <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Row-->
          <div class="row mb-1">
            <!-- Left Column -->
            <div class="col-12 col-md-9">
              <div class="row">
                <!-- Box 1 -->
              <?php
// Calculate score for today
$today = date('Y-m-d');
$sql_today_feedback = "
    SELECT 
        GROUP_CONCAT(CONCAT(fa.question_id, ':', fa.rating)) AS question_ratings
    FROM feedback_form ff
    LEFT JOIN feedback_answers fa ON ff.id = fa.feedback_form_id
    WHERE ff.station_id = ?
    AND DATE(ff.created_at) = ?
";
$stmt_today_feedback = $conn->prepare($sql_today_feedback);
$stmt_today_feedback->bind_param("is", $station_id, $today);
$stmt_today_feedback->execute();
$result_today = $stmt_today_feedback->get_result();
$today_score = 0;
$today_rating_count = 0;
while ($row = $result_today->fetch_assoc()) {
    if (!empty($row['question_ratings'])) {
        foreach (explode(',', $row['question_ratings']) as $rating_data) {
            [$question_id, $rating] = explode(':', $rating_data);
            $today_score += (int) $rating; // Include 0 in the calculation
            $today_rating_count++;
        }
    }
}
$today_score = $today_rating_count > 0 ? $today_score / $today_rating_count : 0;

// Calculate score for the current month
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');
$sql_month_feedback = "
    SELECT 
        GROUP_CONCAT(CONCAT(fa.question_id, ':', fa.rating)) AS question_ratings
    FROM feedback_form ff
    LEFT JOIN feedback_answers fa ON ff.id = fa.feedback_form_id
    WHERE ff.station_id = ?
    AND DATE(ff.created_at) BETWEEN ? AND ?
";
$stmt_month_feedback = $conn->prepare($sql_month_feedback);
$stmt_month_feedback->bind_param("iss", $station_id, $month_start, $month_end);
$stmt_month_feedback->execute();
$result_month = $stmt_month_feedback->get_result();
$month_score = 0;
$month_rating_count = 0;
while ($row = $result_month->fetch_assoc()) {
    if (!empty($row['question_ratings'])) {
        foreach (explode(',', $row['question_ratings']) as $rating_data) {
            [$question_id, $rating] = explode(':', $rating_data);
            $month_score += (int) $rating; // Include 0 in the calculation
            $month_rating_count++;
        }
    }
}
$month_score = $month_rating_count > 0 ? $month_score / $month_rating_count : 0;
?>



<!--paynet warning model by bheem-->
<!-- Modal -->
  <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title fw-bold" id="paymentModalLabel">Payment Required</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center p-4">
          <p class="mb-3 fs-5">
            To continue using our services without interruption,<br>
            please make your payment or part payment.
          </p>
          <p class="text-muted">Contact your administration for further details.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK</button>
        </div>
      </div>
    </div>
  </div>
   <?php if ($station_id == 51): ?>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      var myModal = new bootstrap.Modal(document.getElementById('paymentModal'));
      myModal.show();
    });
  </script>
  <?php endif; ?>

<!-- Displaying the scores in the info-box -->
<div class="col-12 col-sm-6 col-md-4">
    <div class="info-box shadow-sm p-2 d-flex align-items-center rounded bg-light border">
        <div class="info-box-icon me-2 d-flex justify-content-center align-items-center rounded bg-primary text-white" style="width: 60px; height: 60px;">
            <i class="bi bi-award fs-5"></i>
        </div>
        <div class="info-box-content w-100">
            <div class="text-center mb-1">
                <h6 class="mb-0 text-uppercase fw-bold text-secondary">Score</h6>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-center flex-fill">
                    <p class="mb-1 text-muted fw-medium small">Today</p>
                    <h6 class="mb-0 fw-bold text-dark"><?= number_format($today_score, 2) ?></h6>
                </div>
                <div class="vr mx-2"></div>
                <div class="text-center flex-fill">
                    <p class="mb-1 text-muted fw-medium small">Month</p>
                    <h6 class="mb-0 fw-bold text-dark"><?= number_format($month_score, 2) ?></h6>
                </div>
            </div>
        </div>
    </div>
</div>


                <!-- Box 2 -->
                <?php
// Query for today's feedback count
$today = date('Y-m-d');
$queryToday = "SELECT COUNT(*) AS today_count 
               FROM feedback_form 
               WHERE DATE(created_at) = ? AND station_id = ?";
$stmtToday = $conn->prepare($queryToday);
$stmtToday->bind_param('si', $today, $station_id);
$stmtToday->execute();
$resultToday = $stmtToday->get_result()->fetch_assoc();
$todayCount = $resultToday['today_count'] ?? 0;

// Query for current month's feedback count
$currentMonth = date('Y-m');
$queryMonth = "SELECT COUNT(*) AS month_count 
               FROM feedback_form 
               WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND station_id = ?";
$stmtMonth = $conn->prepare($queryMonth);
$stmtMonth->bind_param('si', $currentMonth, $station_id);
$stmtMonth->execute();
$resultMonth = $stmtMonth->get_result()->fetch_assoc();
$monthCount = $resultMonth['month_count'] ?? 0;

$stmtToday->close();
$stmtMonth->close();

?>

<!-- HTML Output -->
<div class="col-12 col-sm-6 col-md-4">
  <div class="info-box shadow-sm p-2 d-flex align-items-center rounded bg-light border">
    <div
      class="info-box-icon me-2 d-flex justify-content-center align-items-center rounded bg-secondary text-white"
      style="width: 60px; height: 60px;">
      <i class="bi bi-chat-square-dots fs-5"></i>
    </div>
    <div class="info-box-content w-100">
      <div class="text-center mb-1">
        <h6 class="mb-0 text-uppercase fw-bold text-secondary">Feedback</h6>
      </div>
      <div class="d-flex justify-content-between align-items-center">
        <div class="text-center flex-fill">
          <p class="mb-1 text-muted fw-medium small">Today</p>
          <h6 class="mb-0 fw-bold text-dark"><?= $todayCount ?></h6>
        </div>
        <div class="vr mx-2"></div>
        <div class="text-center flex-fill">
          <p class="mb-1 text-muted fw-medium small">Month</p>
          <h6 class="mb-0 fw-bold text-dark"><?= $monthCount ?></h6>
        </div>
      </div>
    </div>
  </div>
</div>


                <!-- Box 3 -->
                <?php

$monthStart = date('Y-m-01');

// Query to count today's low ratings filtered by station
$queryToday = "
   SELECT COUNT(*) AS low_rating_count
FROM (
    SELECT 
        ff.id AS feedback_form_id,
        AVG(fa.rating) AS overall_rating
    FROM feedback_answers fa
    JOIN feedback_form ff ON fa.feedback_form_id = ff.id
    WHERE ff.station_id = '$station_id'
      AND DATE(ff.created_at) = '$today'
    GROUP BY ff.id
    HAVING overall_rating < 2
) subquery
";

$resultToday = $conn->query($queryToday);
$todayCount = 0;
if ($resultToday) {
    $row = $resultToday->fetch_assoc();
    $todayCount = $row['low_rating_count'];
}

// Query to count this month's low ratings filtered by station
$queryMonth = "
    SELECT COUNT(*) AS low_rating_count
FROM (
    SELECT 
        ff.id AS feedback_form_id,
        AVG(fa.rating) AS overall_rating
    FROM feedback_answers fa
    JOIN feedback_form ff ON fa.feedback_form_id = ff.id
    WHERE ff.station_id = '$station_id'
      AND DATE(ff.created_at) BETWEEN '$monthStart' AND '$today'
    GROUP BY ff.id
    HAVING overall_rating < 2
) subquery
";

$resultMonth = $conn->query($queryMonth);
$monthCount = 0;
if ($resultMonth) {
    $row = $resultMonth->fetch_assoc();
    $monthCount = $row['low_rating_count'];
}

?>
                <div class="col-12 col-sm-6 col-md-4">
                  <div class="info-box shadow-sm p-2 d-flex align-items-center rounded bg-light border">
                    <div
                      class="info-box-icon me-2 d-flex justify-content-center align-items-center rounded bg-warning text-white"
                      style="width: 60px; height: 60px;">
                      <i class="bi bi-graph-down fs-5"></i>
                    </div>
                    <div class="info-box-content w-100">
                      <div class="text-center mb-1">
                        <h6 class="mb-0 text-uppercase fw-bold text-secondary">Low Rating</h6>
                      </div>
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="text-center flex-fill">
                            <p class="mb-1 text-muted fw-medium small">Today</p>
            <h6 class="mb-0 fw-bold text-dark"><?php echo $todayCount; ?></h6>
                        </div>
                        <div class="vr mx-2"></div>
                        <div class="text-center flex-fill">
                          <p class="mb-1 text-muted fw-medium small">Month</p>
            <h6 class="mb-0 fw-bold text-dark"><?php echo $monthCount; ?></h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Box 4 (Half in up row and half in down row) -->
                <div class="col-12 col-md-4">
                  <div class="info-box shadow-sm p-2 d-flex align-items-center rounded bg-light border">
                    <div
                      class="info-box-icon me-2 d-flex justify-content-center align-items-center rounded bg-success text-white"
                      style="width: 60px; height: 60px;">
                      <i class="bi bi-people fs-5"></i>
                    </div>
                    <div class="info-box-content w-100">
                      <div class="text-center mb-1">
                        <h6 class="mb-0 text-uppercase fw-bold text-secondary">Customers</h6>
                      </div>
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="text-center flex-fill">
                          <p class="mb-1 text-muted fw-medium small">New</p>
                          <h6 class="mb-0 fw-bold text-dark">NA</h6>
                        </div>
                        <div class="vr mx-2"></div>
                        <div class="text-center flex-fill">
                          <p class="mb-1 text-muted fw-medium small">Total</p>
                          <h6 class="mb-0 fw-bold text-dark">NA</h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Box 5 -->
                <div class="col-12 col-md-4">
                  <div class="info-box shadow-sm p-2 d-flex align-items-center rounded bg-light border">
                    <div
                      class="info-box-icon me-2 d-flex justify-content-center align-items-center rounded bg-danger text-white"
                      style="width: 60px; height: 60px;">
                      <i class="bi bi-exclamation-circle fs-5"></i>
                    </div>
                    <div class="info-box-content w-100">
                      <div class="text-center mb-1">
                        <h6 class="mb-0 text-uppercase fw-bold text-secondary">Complaint</h6>
                      </div>
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="text-center flex-fill">
                          <p class="mb-1 text-muted fw-medium small">Today</p>
                          <h6 class="mb-0 fw-bold text-dark">NA</h6>
                        </div>
                        <div class="vr mx-2"></div>
                        <div class="text-center flex-fill">
                          <p class="mb-1 text-muted fw-medium small">Total</p>
                          <h6 class="mb-0 fw-bold text-dark">NA</h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- BOX-6 -->
                <div class="col-12 col-md-4">
                  <div class="info-box shadow-sm p-2 d-flex align-items-center rounded bg-light border">
                    <div
                      class="info-box-icon me-2 d-flex justify-content-center align-items-center rounded bg-info text-white"
                      style="width: 60px; height: 60px;">
                      <i class="bi bi-people fs-5"></i>
                    </div>
                    <div class="info-box-content w-100">
                      <div class="text-center mb-1">
                        <h6 class="mb-0 text-uppercase fw-bold text-secondary">Users</h6>
                      </div>
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="text-center flex-fill">
                          <p class="mb-1 text-muted fw-medium small">Today</p>
                          <h6 class="mb-0 fw-bold text-dark">NA</h6>
                        </div>
                        <div class="vr mx-2"></div>
                        <div class="text-center flex-fill">
                          <p class="mb-1 text-muted fw-medium small">Total</p>
                          <h6 class="mb-0 fw-bold text-dark">NA</h6>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <!-- BIG BOX -->
            <div class="col-12 col-md-3" style="height:200px;">
              <div class="info-box shadow-sm d-flex flex-column align-items-center rounded bg-light border p-0"
                style="height:200px; overflow: hidden;">
                <div id="time-image" class="position-relative w-100 h-100">
                  <!-- Dynamic image will be loaded here -->
                  <img id="time-based-image" src="" alt="Time-based image"
                    class="w-100 h-100 object-fit-cover" style="display: block;">
                  <div id="clock"
                    class="fw-bold position-absolute top-50 start-50 translate-middle text-white text-center">
                  </div>
                </div>
              </div>
            </div>


          </div>
          <!--end::Row-->

          <!--begin::Row-->
          <div class="row">
            <div class="col-lg-4">
              <div class="card h-100 mb-4">
                <div class="card-header" style="background: #2ea4e9; color: white;">
                  <h3 class="card-title">Monthly Performance Report</h3>
                </div>
                <div class="card-body">
                  <!-- Skills Bar -->
                  <div class="mb-3">
                    <label>High Ratings</label>
                    <div class="progress">
                      <div class="progress-bar bg-primary" role="progressbar" style="width: 80%;" aria-valuenow="80"
                        aria-valuemin="0" aria-valuemax="100">80%</div>
                    </div>
                  </div>
                  <div class="mb-3">
                    <label>Performance</label>
                    <div class="progress">
                      <div class="progress-bar bg-success" role="progressbar" style="width: 70%;" aria-valuenow="70"
                        aria-valuemin="0" aria-valuemax="100">70%</div>
                    </div>
                  </div>

                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="card h-100 mb-4">
                <div class="card-header" style="background: #2ea4e9; color: white;">
                  <h3 class="card-title">Latest Comments</h3>
                </div>
                <div class="card-body p-0" style="height: calc(100% - 47px);">
                  <div class="comments-marquee">
                    <div class="comments-list">
                      <div class="comment-item">
                        <div class="comment-header">
                          <span>🎤 <strong>Guest Name:</strong> Guest</span>
                          | <span class="score">Score: 4.00</span>
                          <span class="date">2025-02-05</span>
                        </div>
                        <p class="comment-text">Remark: This platform is great!</p>
                      </div>
                      <div class="comment-item">
                        <div class="comment-header">
                          <span>📝 <strong>Guest Name:</strong> Guest</span>
                          | <span class="score">Score: 3.50</span>
                          <span class="date">2025-02-04</span>
                        </div>
                        <p class="comment-text">Remark: Very user-friendly system.</p>
                      </div>
                      <div class="comment-item">
                        <div class="comment-header">
                          <span>🔊 <strong>Guest Name:</strong> Guest</span>
                          | <span class="score">Score: 4.50</span>
                          <span class="date">2025-02-03</span>
                        </div>
                        <p class="comment-text">Remark: Excellent service provided!</p>
                      </div>
                      <div class="comment-item">
                        <div class="comment-header">
                          <span>💬 <strong>Guest Name:</strong> Guest</span>
                          | <span class="score">Score: 3.75</span>
                          <span class="date">2025-02-02</span>
                        </div>
                        <p class="comment-text">Remark: Staff was very helpful and friendly.</p>
                      </div>
                      <!-- Duplicate comments for continuous scrolling -->
                      <div class="comment-item">
                        <div class="comment-header">
                          <span>🎤 <strong>Guest Name:</strong> Guest</span>
                          | <span class="score">Score: 4.00</span>
                          <span class="date">2025-02-05</span>
                        </div>
                        <p class="comment-text">Remark: This platform is great!</p>
                      </div>
                      <div class="comment-item">
                        <div class="comment-header">
                          <span>📝 <strong>Guest Name:</strong> Guest</span>
                          | <span class="score">Score: 3.50</span>
                          <span class="date">2025-02-04</span>
                        </div>
                        <p class="comment-text">Remark: Very user-friendly system.</p>
                      </div>
                      <div class="comment-item">
                        <div class="comment-header">
                          <span>🔊 <strong>Guest Name:</strong> Guest</span>
                          | <span class="score">Score: 4.50</span>
                          <span class="date">2025-02-03</span>
                        </div>
                        <p class="comment-text">Remark: Excellent service provided!</p>
                      </div>
                      <div class="comment-item">
                        <div class="comment-header">
                          <span>💬 <strong>Guest Name:</strong> Guest</span>
                          | <span class="score">Score: 3.75</span>
                          <span class="date">2025-02-02</span>
                        </div>
                        <p class="comment-text">Remark: Staff was very helpful and friendly.</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

         <style>
  .comments-marquee {
    height: 100%;
    overflow: hidden;
    position: relative;
    background: #f1f8ff;
    border-radius: 0 0 5px 5px;
  }

  .comments-list {
    position: absolute;
    width: 100%;
    animation: scrollUp 25s linear infinite;
    animation-delay: 0s;
    animation-fill-mode: both;
  }

  .comment-item {
    padding: 12px 16px;
    margin: 6px 10px;
    border-radius: 6px;
    background-color: #ffffff;
    box-shadow: 0 2px 4px rgba(0, 123, 255, 0.1);
    transition: transform 0.2s ease-in-out;
  }

  .comment-item:hover {
    transform: translateY(-2px);
    background-color: #f9fbff;
  }

  .comment-header {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    font-weight: 600;
    margin-bottom: 4px;
    color: #333;
  }

  .comment-header span {
    flex: 1 1 auto;
    padding: 2px 4px;
  }

  .comment-header span:first-child {
    color: #007bff; /* Soft blue for name */
  }

  .score {
    color: #ff9800;
    font-weight: bold;
    text-align: center;
  }

  .date {
    color: #6c757d;
    text-align: right;
    font-size: 0.9em;
  }

  .comment-text {
    margin: 0;
    color: #444;
    font-style: italic;
  }

  @keyframes scrollUp {
    0% {
      transform: translateY(0%);
    }
    100% {
      transform: translateY(-100%);
    }
  }

  .comments-marquee:hover .comments-list {
    animation-play-state: paused;
  }
</style>




          




            <div class="col-lg-4">
              <div class="card h-100 mb-4">
                <div class="card-header" style="background: #2ea4e9; color: white;">
                  <h3 class="card-title">Notice Board</h3>
                </div>
                <div class="card-body" style="padding: 0; overflow: hidden;">
                  <img src="https://baris.beatleanalytics.com/theme/black/dist/img/snt_launch.gif" alt=""
                    style="width: 100%; height: 100%; object-fit: cover; display: block;">
                </div>
              </div>
            </div>


          </div>
          <!--end::Row-->

          <!--begin::Row-->
          <div class="row mt-3">

            <!-- Weekly Performance Chart -->
            <div class="col-lg-4">
              <div class="card h-100 mb-4">
                <div class="card-header" style="background: #2ea4e9; color: white;">
                  <h3 class="card-title">Weekly Performance Chart</h3>
                </div>
                <div class="card-body" style="position: relative; height: 200px;">
                  <canvas id="weeklyPerformanceChart"></canvas>
                </div>
              </div>
            </div>

            <!-- Weekly Feedback Count -->
            <div class="col-lg-4">
              <div class="card h-100 mb-4">
                <div class="card-header" style="background: #2ea4e9; color: white;">
                  <h3 class="card-title">Weekly Feedback Count</h3>
                </div>
                <div class="card-body" style="position: relative; height: 200px;">
                  <canvas id="feedbackChart"></canvas>
                </div>
              </div>
            </div>

            <div class="col-lg-4">
              <div class="card h-100 mb-4">
                <div class="card-header" style="background: #2ea4e9; color: white;">
                  <h3 class="card-title">Subscription Status</h3>
                </div>
                <div class="card-body p-5">
                  <div class="subscription-chart-container">
                    <div class="subscription-progress">
                      <div class="d-flex align-items-center justify-content-between">
                        <div class="progress-ring-container">
                          <svg class="progress-ring" width="120" height="120">
                            <circle class="progress-ring-circle-bg" cx="60" cy="60" r="54"></circle>
                            <circle class="progress-ring-circle" cx="60" cy="60" r="54" stroke-dasharray="339.3" stroke-dashoffset="84.8"></circle>
                          </svg>
                          <div class="progress-content">
                            <div class="percentage">75%</div>
                            <div class="small-text">Complete</div>
                          </div>
                        </div>
                        <div class="subscription-info ms-3">
                          <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value"><span class="status-badge">Active</span></div>
                          </div>
                          <div class="info-item">
                            <div class="info-label">Valid Until</div>
                            <div class="info-value">May 4, 2026</div>
                          </div>
                          <div class="info-item">
                            <div class="info-label">Days Left</div>
                            <div class="info-value">300</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <style>
              /* Modern Subscription Chart Styling */
              .subscription-chart-container {
                font-family: 'Source Sans 3', sans-serif;
                width: 100%;
              }
              
              .subscription-progress {
                width: 100%;
              }
              
              .progress-ring-container {
                position: relative;
                width: 120px;
                height: 120px;
                flex-shrink: 0;
              }
              
              .progress-ring {
                transform: rotate(-90deg);
                overflow: visible;
              }
              
              .progress-ring-circle-bg {
                fill: none;
                stroke: rgba(230, 230, 230, 0.6);
                stroke-width: 6;
              }
              
              .progress-ring-circle {
                fill: none;
                stroke: #2ea4e9;
                stroke-width: 6;
                stroke-linecap: round;
                filter: drop-shadow(0px 2px 4px rgba(46, 164, 233, 0.3));
                transition: stroke-dashoffset 1s ease-in-out;
                animation: ring-progress 2s ease-out forwards;
              }
              
              @keyframes ring-progress {
                0% {
                  stroke-dashoffset: 339.3;
                }
                100% {
                  stroke-dashoffset: 84.8;
                }
              }
              
              .progress-content {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
              }
              
              .percentage {
                font-size: 24px;
                font-weight: 700;
                color: #2ea4e9;
                opacity: 0;
                animation: fade-in 0.5s ease-out 1.5s forwards;
              }
              
              .small-text {
                font-size: 12px;
                color: #666;
                margin-top: -3px;
                opacity: 0;
                animation: fade-in 0.5s ease-out 1.7s forwards;
              }
              
              @keyframes fade-in {
                0% {
                  opacity: 0;
                  transform: translateY(10px);
                }
                100% {
                  opacity: 1;
                  transform: translateY(0);
                }
              }
              
              .subscription-info {
                flex: 1;
                background: #f8f9fa;
                border-radius: 8px;
                padding: 10px 12px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
              }
              
              .info-item {
                display: flex;
                justify-content: space-between;
                margin-bottom: 6px;
                padding-bottom: 6px;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
              }
              
              .info-item:last-child {
                margin-bottom: 0;
                padding-bottom: 0;
                border-bottom: none;
              }
              
              .info-label {
                font-size: 12px;
                color: #666;
                font-weight: 500;
              }
              
              .info-value {
                font-size: 12px;
                color: #333;
                font-weight: 600;
              }
              
              .status-badge {
                background-color: #28a745;
                color: white;
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 500;
              }
            </style>





          </div>

          <!--end::Row-->

        </div>
        <!--end::Container-->

      </div>
      <!--end::App Content-->
    </main>
    <!--end::App Main-->
    <!--begin::Footer-->
    <?php
    include 'footer.php';
    ?>
    <!--end::Footer-->
  </div>
  <!--end::App Wrapper-->
  <!--begin::Script-->
  <!--begin::Third Party Plugin(OverlayScrollbars)-->
  <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"
    integrity="sha256-dghWARbRe2eLlIJ56wNB+b760ywulqK3DzZYEpsg2fQ=" crossorigin="anonymous"></script>
  <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
    integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
    crossorigin="anonymous"></script>
  <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
    integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy"
    crossorigin="anonymous"></script>
  <!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
  <script src="./js/adminlte.js"></script>
  <!--end::Required Plugin(AdminLTE)--><!--begin::OverlayScrollbars Configure-->

  <!-- Include Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script>
    // JavaScript to update the clock and image
    function updateClockAndImage() {
      const clockElement = document.getElementById("clock");
      const imageElement = document.getElementById("time-based-image");

      // Get the current time
      const now = new Date();
      const hours = now.getHours();
      const minutes = now.getMinutes().toString().padStart(2, "0");
      const seconds = now.getSeconds().toString().padStart(2, "0");

      // Update the clock display
      clockElement.textContent = `${hours}:${minutes}:${seconds}`;

      // Determine the appropriate image based on the time
      let imageUrl = "";
      if (hours >= 6 && hours < 12) {
        // Morning (6 AM to 12 PM)
        imageUrl = "assets/img/morning.webp";
      } else if (hours >= 12 && hours < 18) {
        // Afternoon (12 PM to 6 PM)
        imageUrl = "assets/img/everning.webp";
      } else if (hours >= 18 && hours < 21) {
        // Evening (6 PM to 9 PM)
        imageUrl = "assets/img/sunset.webp";
      } else {
        // Night (9 PM to 6 AM)
        imageUrl = "assets/img/night.webp";
      }

      // Update the image source
      imageElement.src = imageUrl;
    }

    // Update the clock and image every second
    setInterval(updateClockAndImage, 1000);

    // Initial call to set the image and clock immediately
    updateClockAndImage();
  </script>


  <!-- Include Chart.js -->
  <script>
    // Set global chart defaults for a more modern look
    Chart.defaults.font.family = "'Source Sans 3', sans-serif";
    Chart.defaults.color = "#6c757d";
    Chart.defaults.borderColor = "rgba(0,0,0,0.05)";
    Chart.defaults.plugins.tooltip.backgroundColor = "rgba(0,0,0,0.7)";
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 6;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    
    // Weekly Performance Chart - Gradient Bar Chart
    const performanceCtx = document.getElementById('weeklyPerformanceChart').getContext('2d');
    
    // Create gradient for bars
    const performanceGradient = performanceCtx.createLinearGradient(0, 0, 0, 200);
    performanceGradient.addColorStop(0, 'rgba(32, 156, 238, 0.9)');
    performanceGradient.addColorStop(1, 'rgba(32, 156, 238, 0.4)');
    
    const weeklyPerformanceChart = new Chart(performanceCtx, {
      type: 'bar',
      data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [{
          label: 'Performance Score',
          data: [75, 80, 70, 85, 90, 88, 95],
          backgroundColor: performanceGradient,
          borderColor: 'rgba(32, 156, 238, 1)',
          borderWidth: 1,
          borderRadius: 6,
          barPercentage: 0.7,
          categoryPercentage: 0.8,
          hoverBackgroundColor: 'rgba(32, 156, 238, 1)',
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
          duration: 2000,
          easing: 'easeOutQuart'
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            grid: {
              display: true,
              drawBorder: false,
              color: 'rgba(200, 200, 200, 0.2)'
            },
            ticks: {
              stepSize: 20,
              padding: 10,
              font: {
                size: 11
              }
            }
          },
          x: {
            grid: {
              display: false,
              drawBorder: false
            },
            ticks: {
              padding: 10,
              font: {
                size: 11
              }
            }
          }
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            displayColors: false,
            callbacks: {
              label: function(context) {
                return `Score: ${context.parsed.y}%`;
              }
            }
          }
        }
      }
    });

    // Weekly Feedback Count Chart - Modern Doughnut
    const feedbackCtx = document.getElementById('feedbackChart').getContext('2d');
    const feedbackChart = new Chart(feedbackCtx, {
      type: 'doughnut',
      data: {
        labels: ['Positive', 'Neutral', 'Negative'],
        datasets: [{
          data: [45, 20, 10],
          backgroundColor: [
            'rgba(76, 175, 80, 0.8)',   // Green for Positive
            'rgba(255, 193, 7, 0.8)',   // Yellow for Neutral
            'rgba(244, 67, 54, 0.8)'    // Red for Negative
          ],
          borderColor: '#ffffff',
          borderWidth: 2,
          hoverBackgroundColor: [
            'rgba(76, 175, 80, 1)',
            'rgba(255, 193, 7, 1)',
            'rgba(244, 67, 54, 1)'
          ],
          hoverBorderColor: '#ffffff',
          hoverBorderWidth: 0,
          cutout: '70%'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
          animateScale: true,
          animateRotate: true,
          duration: 2000,
          easing: 'easeOutQuart'
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 15,
              font: {
                size: 12,
                weight: '500'
              },
              generateLabels: function(chart) {
                const data = chart.data;
                if (data.labels.length && data.datasets.length) {
                  const {labels: {pointStyle}} = chart.legend.options;
                  return data.labels.map((label, i) => {
                    const dataset = data.datasets[0];
                    const value = dataset.data[i];
                    const total = dataset.data.reduce((acc, val) => acc + val, 0);
                    const percentage = Math.round((value / total) * 100) + '%';
                    
                    return {
                      text: `${label}: ${percentage}`,
                      fillStyle: dataset.backgroundColor[i],
                      strokeStyle: dataset.borderColor,
                      lineWidth: dataset.borderWidth,
                      pointStyle: 'circle',
                      hidden: false,
                      index: i
                    };
                  });
                }
                return [];
              }
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const label = context.label || '';
                const value = context.parsed || 0;
                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                const percentage = Math.round((value / total) * 100);
                return `${label}: ${value} (${percentage}%)`;
              }
            }
          }
        }
      }
    });
  </script>
</body>
<!--end::Body-->

</html>