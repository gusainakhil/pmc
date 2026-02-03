<?php
session_start();
include 'connection.php';
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}
// User data
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'];
$station_id = $_SESSION['station_id'];
?>
<!doctype html>
<html lang="en">
<!--begin::Head-->

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>OBHS - Date-wise Feedback Report</title>
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

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

    <style>
        /* Import Google Fonts for beautiful typography */
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif;
        }

        /* Fade In Animation for the entire table container */
        .animate-fade-in {
            animation: fadeIn 1s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom Table Styling with 3D Effects */
        .custom-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            margin: 30px 0;
            border: 1px solid #dee2e6;
            background: #fff;
            transform-style: preserve-3d;
            perspective: 1000px;
            animation: tableSpinIn 1s ease-out;
        }

        /* Crazy table spin-in animation */
        @keyframes tableSpinIn {
            0% {
                transform: rotateY(-90deg);
                opacity: 0;
            }
            50% {
                transform: rotateY(20deg);
                opacity: 0.5;
            }
            100% {
                transform: rotateY(0deg);
                opacity: 1;
            }
        }

        .custom-table thead th {
            background: linear-gradient(135deg, #48a9d4, #3e8ec7);
            color: #fff;
            border-bottom: 2px solid #dee2e6;
            padding: 13px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .custom-table tbody td {
            padding: 15px;
            font-size: 0.85rem;
            border-bottom: 1px solid #dee2e6;
            text-align: center;
            vertical-align: middle;
            background-color: #f8f9fa;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        /* 3D Hover Effect on Rows */
        .custom-table tbody tr {
            transition: transform 0.5s ease, box-shadow 0.5s ease;
        }

        .custom-table tbody tr:hover {
            transform: translateZ(20px) scale(1.02);
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.2);
        }

        /* Zebra stripes for enhanced readability */
        .custom-table tbody tr:nth-child(even) td {
            background-color: #f1f1f1;
        }

        .custom-table tbody tr:nth-child(odd) td {
            background-color: #ffffff;
        }

        /* Rounded Corners for first and last cells in each row */
        .custom-table tbody tr td:first-child {
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }

        .custom-table tbody tr td:last-child {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        /* Custom Badge Styling with Hover 3D Effect */
        .badge {
            font-size: 0.7rem;
            border-radius: 12px;
            box-shadow: 0px 3px 6px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: inline-block;
            background: #28a745;
            color: #fff;
        }

        .badge:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.3);
        }

        /* Enhanced Shadow for Table Container */
        .shadow-sm {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            border-radius: 12px;
        }

        /* Date Header Styling */
        .date-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            font-weight: bold;
            font-size: 1rem;
            padding: 12px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        /* Date Statistics Cards */
        .date-stats-card {
            background: linear-gradient(135deg, #6f42c1, #e83e8c);
            color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .expandable-row {
            cursor: pointer;
        }

        .details-row {
            display: none;
            background-color: #f8f9fa;
        }

        .details-row.show {
            display: table-row;
        }
        
        .table-padding {
            padding: 0px;
        }
        
        .dataTables_wrapper th, .dataTables_wrapper td {
            padding: 10px !important;
        }

        table.dataTable thead > tr > th.sorting,
        table.dataTable thead > tr > th.sorting_asc,
        table.dataTable thead > tr > th.sorting_desc,
        table.dataTable thead > tr > th.sorting_asc_disabled,
        table.dataTable thead > tr > th.sorting_desc_disabled,
        table.dataTable thead > tr > td.sorting,
        table.dataTable thead > tr > td.sorting_asc,
        table.dataTable thead > tr > td.sorting_desc,
        table.dataTable thead > tr > td.sorting_asc_disabled,
        table.dataTable thead > tr > td.sorting_desc_disabled {
            padding: 5px !important;
            text-align: center;
        }

        /* Missing days styling */
        .table-warning {
            background-color: #fff3cd !important;
        }

        .table-warning td {
            background-color: #fff3cd !important;
            border-color: #ffeaa7 !important;
        }

        .missing-day-indicator {
            color: #856404;
            font-style: italic;
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
        <!--begin::App Content-->
        <div class="app-content">
            <div class="container-fluid">
                <!-- Filter Section -->
                <div class="row mb-4 mt-3 justify-content-center">
                    <?php
                    if ($_SERVER["REQUEST_METHOD"] == "POST") {
                        $from = $_POST['from'] ?? '';
                        $to = $_POST['to'] ?? '';
                        $from_date = $from ? date('Y-m-d', strtotime($from)) : '';
                        $to_date = $to ? date('Y-m-d', strtotime($to)) : '';
                    }
                    ?>
                    <form method="POST" class="w-100">
                        <div class="col-auto text-center">
                            <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">
                                <div>
                                    <div class="d-flex gap-2">
                                        <input type="date" class="form-control form-control-lg shadow-sm" id="startDate"
                                            name="from" value="<?= htmlspecialchars($from_date) ?>">
                                        <input type="date" class="form-control form-control-lg shadow-sm" id="endDate"
                                            name="to" value="<?= htmlspecialchars($to_date) ?>">
                                    </div>
                                </div>
                                <div class="d-flex gap-3 mt-2 mt-md-0">
                                    <button class="btn btn-primary btn-lg shadow-sm px-4 animate-hover" type="submit">Go</button>
                                    <button class="btn btn-danger btn-lg shadow-sm px-4 animate-hover" type="reset"
                                        onclick="window.location.href=window.location.href">Reset</button>
                                    <!-- Print Button -->
                                    <button class="btn btn-info btn-lg shadow-sm px-4 animate-hover" type="button" onclick="window.print()">Print</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Table Section -->
                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    ?>
                    <div class="table-responsive animate-fade-in">
                        <?php
                        function getBadgeClass($rating)
                        {
                            switch ($rating) {
                                case 3:
                                    return "bg-success"; // Very Good
                                case 2:
                                    return "bg-warning"; // Satisfactory
                                case 1:
                                    return "bg-danger"; // Poor
                                case 0:
                                    return "bg-secondary"; // Not Applicable (NA)
                                default:
                                    return "bg-secondary"; // Fallback
                            }
                        }

                        // Fetch station details including feedback target and rating parameters
                        $sql_station = "SELECT name, feedback_target FROM feedback_stations WHERE id = ?";
                        $stmt_station = $conn->prepare($sql_station);
                        $stmt_station->bind_param("i", $station_id);
                        $stmt_station->execute();
                        $result_station = $stmt_station->get_result();
                        $station_data = $result_station->fetch_assoc();
                        $station_name = $station_data['name'] ?? 'Unknown Station';
                        $daily_target = (int) ($station_data['feedback_target'] ?? 0);
                        $stmt_station->close();

                        // Fetch rating parameters for dynamic maximum score
                        $sql_rating_params = "SELECT value FROM rating_parameters WHERE station_id = ?";
                        $stmt_rating_params = $conn->prepare($sql_rating_params);
                        $stmt_rating_params->bind_param("i", $station_id);
                        $stmt_rating_params->execute();
                        $result_rating_params = $stmt_rating_params->get_result();
                        $rating_data = $result_rating_params->fetch_assoc();
                        $max_rating_score = (int) ($rating_data['value'] ?? 3); // Default to 3 if not found
                        $stmt_rating_params->close();

                        // Fetch questions for the specified station
                        $sql_questions = "SELECT id, question_text FROM feedback_questions WHERE station_id = ?";
                        $stmt_questions = $conn->prepare($sql_questions);
                        $stmt_questions->bind_param("i", $station_id);
                        $stmt_questions->execute();
                        $result_questions = $stmt_questions->get_result();
                        $questions = [];
                        while ($row = $result_questions->fetch_assoc()) {
                            $questions[$row['id']] = $row['question_text'];
                        }
                        $stmt_questions->close();

                        // Fetch feedback data grouped by date
                        $sql_feedback = "
                            SELECT 
                                DATE(ff.created_at) as feedback_date,
                                COUNT(ff.id) as total_feedbacks,
                                ff.id AS form_id, 
                                ff.passenger_name, 
                                ff.passenger_mobile, 
                                ff.platform_no, 
                                ff.pnr_number, 
                                ff.created_at,
                                GROUP_CONCAT(CONCAT(fa.question_id, ':', fa.rating)) AS question_ratings
                            FROM feedback_form ff
                            LEFT JOIN feedback_answers fa ON ff.id = fa.feedback_form_id
                            WHERE ff.station_id = ?
                            AND DATE(ff.created_at) BETWEEN ? AND ?
                            GROUP BY ff.id, DATE(ff.created_at)
                            ORDER BY ff.created_at DESC
                        ";

                        $stmt_feedback = $conn->prepare($sql_feedback);
                        $stmt_feedback->bind_param("iss", $station_id, $from_date, $to_date);
                        $stmt_feedback->execute();
                        $feedback_data = $stmt_feedback->get_result();

                        // Group data by date for processing
                        $date_wise_data = [];
                        $total_feedbacks = 0;
                        $total_score_sum = 0;
                        $days_with_data = 0;
                        $target_achieved_days = 0;

                        while ($row = $feedback_data->fetch_assoc()) {
                            $date = $row['feedback_date'];
                            
                            if (!isset($date_wise_data[$date])) {
                                $date_wise_data[$date] = [
                                    'feedbacks' => [],
                                    'total_count' => 0,
                                    'total_score_sum' => 0
                                ];
                                $days_with_data++;
                            }
                            
                            $overall_score = 0;
                            $rating_count = 0;
                            
                            if (!empty($row['question_ratings'])) {
                                foreach (explode(',', $row['question_ratings']) as $rating_data) {
                                    [$question_id, $rating] = explode(':', $rating_data);
                                    $overall_score += (int) $rating;
                                    $rating_count++;
                                }
                            }
                            
                            $individual_score = $rating_count > 0 ? $overall_score / $rating_count : 0;
                            
                            $date_wise_data[$date]['feedbacks'][] = $row;
                            $date_wise_data[$date]['total_count']++;
                            $date_wise_data[$date]['total_score_sum'] += $individual_score;
                            
                            $total_feedbacks++;
                            $total_score_sum += $individual_score;
                        }

                        // Count days that achieved target
                        foreach ($date_wise_data as $data) {
                            if ($data['total_count'] >= $daily_target) {
                                $target_achieved_days++;
                            }
                        }

                        // Generate all dates in the range to show missing dates
                        $all_dates = [];
                        if ($from_date && $to_date) {
                            $start = new DateTime($from_date);
                            $end = new DateTime($to_date);
                            $end = $end->modify('+1 day'); // Include the end date
                            
                            $interval = new DateInterval('P1D');
                            $period = new DatePeriod($start, $interval, $end);
                            
                            foreach ($period as $date) {
                                $date_str = $date->format('Y-m-d');
                                $all_dates[$date_str] = [
                                    'feedbacks' => [],
                                    'total_count' => 0,
                                    'total_score_sum' => 0,
                                    'has_data' => false
                                ];
                                
                                // If we have actual data for this date, use it
                                if (isset($date_wise_data[$date_str])) {
                                    $all_dates[$date_str] = $date_wise_data[$date_str];
                                    $all_dates[$date_str]['has_data'] = true;
                                }
                            }
                            
                            // Update days_with_data to reflect actual dates with feedback
                            $days_with_data = count(array_filter($all_dates, function($data) {
                                return $data['has_data'];
                            }));
                        } else {
                            $all_dates = $date_wise_data;
                            foreach ($all_dates as &$data) {
                                $data['has_data'] = true;
                            }
                        }

                        $average_total_score = $total_feedbacks > 0 ? $total_score_sum / $total_feedbacks : 0;
                        $quality_psi = ($average_total_score / $max_rating_score) * 100;

                        // Calculate total expected feedbacks for the period (target × active days)
                        $total_expected_feedbacks = $days_with_data * $daily_target;
                        $quantity_achievement = $total_expected_feedbacks > 0 ? ($total_feedbacks / $total_expected_feedbacks) : 0;

                        // Adjusted PSI = Quality PSI × Quantity Achievement
                        $adjusted_psi_percentage = $quality_psi * $quantity_achievement;

                        $target_achievement_percentage = $days_with_data > 0 ? ($target_achieved_days / $days_with_data) * 100 : 0;
                        
                        // Calculate total days in range and missing days
                        $total_days_in_range = count($all_dates);
                        $missing_days = $total_days_in_range - $days_with_data;

                        // Function to get target achievement badge class
                        function getTargetBadgeClass($achieved, $total, $target) {
                            $percentage = $target > 0 ? ($total / $target) * 100 : 0;
                            if ($percentage >= 100) return "bg-success";
                            elseif ($percentage >= 75) return "bg-warning";
                            else return "bg-danger";
                        }
                        ?>

                        <!-- Overall Summary Header -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card shadow-sm" style="background: linear-gradient(135deg, #48a9d4, #3e8ec7); color: white;">
                                    <div class="card-body text-center">
                                        <h4 class="card-title mb-3">
                                            <i class="bi bi-calendar3"></i> Date-wise Feedback Summary Report - <?= strtoupper($station_name) ?>
                                        </h4>
                                        <div class="row">
                                            <div class="col-md-2">
                                                <h5 class="mb-1"><?= $total_feedbacks ?></h5>
                                                <small>Total Feedbacks</small>
                                            </div>
                                            <div class="col-md-2">
                                                <h5 class="mb-1"><?= $daily_target ?></h5>
                                                <small>Daily Target</small>
                                            </div>
                                            <div class="col-md-2">
                                                <h5 class="mb-1"><?= $total_days_in_range ?></h5>
                                                <small>Total Days</small>
                                            </div>
                                            <div class="col-md-2">
                                                <h5 class="mb-1"><?= $days_with_data ?></h5>
                                                <small>Active Days</small>
                                            </div>
                                            <div class="col-md-2">
                                                <h5 class="mb-1"><?= number_format($adjusted_psi_percentage, 2) ?>%</h5>
                                                <small>PSI</small>
                                            </div>
                                            <div class="col-md-2">
                                                <h5 class="mb-1"><?= number_format($quantity_achievement * 100, 1) ?>%</h5>
                                                <small>Achievement</small>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <small><strong>Expected Feedbacks:</strong> <?= $total_expected_feedbacks ?> | <strong>Actual:</strong> <?= $total_feedbacks ?> | <strong>Achievement:</strong> <?= number_format($quantity_achievement * 100, 1) ?>%</small>
                                            </div>
                                            <div class="col-md-6">
                                                <?php if ($from_date && $to_date): ?>
                                                <small><i class="bi bi-calendar-range"></i> Period: <?= date('d M Y', strtotime($from_date)) ?> to <?= date('d M Y', strtotime($to_date)) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date-wise Data Table -->
                        <table id="dataTable" class="table-padding table table-bordered table-hover shadow-sm custom-table">
                            <thead class="table-primary">
                                <tr>
                                    <th>Date</th>
                                    <th>Feedbacks</th>
                                    <th>Target</th>
                                    <th>Achievement</th>
                                    <th>PSI</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($all_dates as $date => $data) {
                                    $date_avg_score = $data['total_count'] > 0 ? $data['total_score_sum'] / $data['total_count'] : 0;
                                    $date_quality_psi = ($date_avg_score / $max_rating_score) * 100;
                                    
                                    // Calculate daily quantity achievement
                                    $daily_quantity_achievement = $daily_target > 0 ? ($data['total_count'] / $daily_target) : 0;
                                    
                                    // Calculate daily adjusted PSI
                                    $date_adjusted_psi = $date_quality_psi * $daily_quantity_achievement;
                                    
                                    $progress_percentage = $daily_target > 0 ? ($data['total_count'] / $daily_target) * 100 : 0;
                                    $target_achieved = $data['total_count'] >= $daily_target;
                                    $has_data = $data['has_data'];
                                    
                                    // Different styling for dates with no data
                                    $row_class = $has_data ? 'expandable-row' : 'expandable-row table-warning';
                                    
                                    echo "<tr class='{$row_class}' data-date='{$date}'>";
                                    echo "<td><strong>" . date('d M Y (D)', strtotime($date)) . "</strong></td>";
                                    
                                    if ($has_data) {
                                        // Feedbacks count with color coding
                                        $feedback_badge_class = getTargetBadgeClass($target_achieved, $data['total_count'], $daily_target);
                                        echo "<td><span class='badge {$feedback_badge_class}'>{$data['total_count']}</span></td>";
                                        
                                        echo "<td><span class='badge bg-info'>{$daily_target}</span></td>";
                                        
                                        // Achievement status
                                        $achievement_icon = $target_achieved ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>';
                                        $achievement_text = $target_achieved ? 'Achieved' : number_format($progress_percentage, 1) . '%';
                                        echo "<td>{$achievement_icon} <small>{$achievement_text}</small></td>";
                                        
                                        // PSI (adjusted PSI - quality × quantity achievement)
                                        $adjusted_badge_class = $date_adjusted_psi >= 80 ? "bg-success" : ($date_adjusted_psi >= 60 ? "bg-warning" : "bg-danger");
                                        echo "<td><span class='badge {$adjusted_badge_class}'>" . number_format($date_adjusted_psi, 1) . "%</span></td>";
                                        
                                        echo "<td><button class='btn btn-sm btn-outline-primary toggle-details' data-date='{$date}'>View Details</button></td>";
                                    } else {
                                        // No data available for this date
                                        echo "<td><span class='badge bg-secondary'>0</span></td>";
                                        echo "<td><span class='badge bg-info'>{$daily_target}</span></td>";
                                        echo "<td><i class='bi bi-exclamation-triangle-fill text-warning'></i> <small class='text-muted'>No Feedback</small></td>";
                                        echo "<td><span class='badge bg-secondary'>0%</span></td>";
                                        echo "<td><span class='text-muted'>No Details</span></td>";
                                    }
                                    echo "</tr>";
                                    
                                    // Details row (only for dates with data)
                                    if ($has_data && !empty($data['feedbacks'])) {
                                        echo "<tr class='details-row' id='details-{$date}'>";
                                        echo "<td colspan='6'>";
                                        echo "<div class='p-3'>";
                                        echo "<h6 class='mb-3'>Individual Feedbacks for " . date('d M Y', strtotime($date)) . "</h6>";
                                        
                                        // Enhanced target summary for this day
                                        echo "<div class='row mb-3'>";
                                        echo "<div class='col-md-12'>";
                                        echo "<div class='alert alert-" . ($target_achieved ? "success" : "warning") . "' role='alert'>";
                                        echo "<div class='row'>";
                                        echo "<div class='col-md-6'>";
                                        echo "<strong>Daily Target Status:</strong> {$data['total_count']} / {$daily_target} feedbacks (" . number_format($progress_percentage, 1) . "%)";
                                        if ($target_achieved) {
                                            echo " - <i class='bi bi-trophy-fill'></i> Target Achieved!";
                                        } else {
                                            $shortfall = $daily_target - $data['total_count'];
                                            echo " - Need {$shortfall} more feedback" . ($shortfall > 1 ? "s" : "") . " to reach target";
                                        }
                                        echo "</div>";
                                        echo "<div class='col-md-6'>";
                                        echo "<strong>PSI Analysis:</strong><br>";
                                        echo "Quality Score: " . number_format($date_quality_psi, 1) . "% | ";
                                        echo "Quantity Achievement: " . number_format($daily_quantity_achievement * 100, 1) . "%<br>";
                                        echo "<strong>Final PSI: " . number_format($date_adjusted_psi, 1) . "%</strong>";
                                        echo "</div>";
                                        echo "</div>";
                                        echo "</div>";
                                        echo "</div>";
                                        echo "</div>";
                                        
                                        echo "<div class='table-responsive'>";
                                        echo "<table class='table table-sm table-striped'>";
                                        echo "<thead><tr>";
                                        echo "<th>Time</th><th>Customer Name</th><th>Phone</th><th>Platform</th><th>PNR</th><th>Score</th>";
                                        echo "</tr></thead>";
                                        echo "<tbody>";
                                        
                                        foreach ($data['feedbacks'] as $feedback) {
                                            $overall_score = 0;
                                            $rating_count = 0;
                                            
                                            if (!empty($feedback['question_ratings'])) {
                                                foreach (explode(',', $feedback['question_ratings']) as $rating_data) {
                                                    [$question_id, $rating] = explode(':', $rating_data);
                                                    $overall_score += (int) $rating;
                                                    $rating_count++;
                                                }
                                            }
                                            
                                            $individual_score = $rating_count > 0 ? $overall_score / $rating_count : 0;
                                            
                                            echo "<tr>";
                                            echo "<td>" . date('H:i', strtotime($feedback['created_at'])) . "</td>";
                                            echo "<td><a href='score-card.php?form_id={$feedback['form_id']}' target='_blank' class='text-decoration-none'>{$feedback['passenger_name']}</a></td>";
                                            echo "<td>{$feedback['passenger_mobile']}</td>";
                                            echo "<td>PF {$feedback['platform_no']}</td>";
                                            echo "<td>{$feedback['pnr_number']}</td>";
                                            echo "<td><span class='badge " . getBadgeClass(round($individual_score)) . "'>" . number_format($individual_score, 2) . "</span></td>";
                                            echo "</tr>";
                                        }
                                        
                                        echo "</tbody></table>";
                                        echo "</div>";
                                        echo "</div>";
                                        echo "</td>";
                                        echo "</tr>";
                                    } elseif (!$has_data) {
                                        // Details row for dates with no data
                                        echo "<tr class='details-row' id='details-{$date}' style='display: none;'>";
                                        echo "<td colspan='6'>";
                                        echo "<div class='p-3 text-center'>";
                                        echo "<div class='alert alert-warning' role='alert'>";
                                        echo "<i class='bi bi-exclamation-triangle-fill'></i> ";
                                        echo "<strong>No Feedback Data Available</strong><br>";
                                        echo "No passenger feedback was collected on " . date('d M Y (l)', strtotime($date)) . ".";
                                        echo "<br><small>Target: {$daily_target} feedbacks | Actual: 0 feedbacks | PSI: 0%</small>";
                                        echo "</div>";
                                        echo "</div>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>

                        <?php
                        $stmt_feedback->close();
                        $conn->close();
                        ?>
                    </div>
                    <?php
                } else {
                    // Show search icon and text when the request is not a POST
                    echo '<div class="mt-5" style="text-align: center; font-size: 50px;">';
                    echo '<i class="bi bi-calendar-date"></i>';  // Bootstrap calendar icon
                    echo '<p>Search For Date-wise Feedback Report</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
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

    <!-- Add jQuery and DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function () {
            // Initialize DataTable
            $('#dataTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                language: {
                    search: "Filter Results:",
                },
                order: [[0, 'desc']] // Sort by date descending
            });

            // Toggle details functionality
            $('.toggle-details').click(function() {
                var date = $(this).data('date');
                var detailsRow = $('#details-' + date);
                var button = $(this);
                
                if (detailsRow.hasClass('show')) {
                    detailsRow.removeClass('show');
                    button.text('View Details');
                    button.removeClass('btn-outline-danger').addClass('btn-outline-primary');
                } else {
                    detailsRow.addClass('show');
                    button.text('Hide Details');
                    button.removeClass('btn-outline-primary').addClass('btn-outline-danger');
                }
            });
        });
    </script>
</body>
<!--end::Body-->

</html>
