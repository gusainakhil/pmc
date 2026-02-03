<?php
session_start();
include 'connection.php';

// User data
$user_id = $_SESSION['userId'];
// $user_name = $_SESSION['username'];
$station_id = $_SESSION['stationId'];

?>
<?php

// Prepare query to fetch station name
$sql = "SELECT name FROM feedback_stations WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $station_name = $row['name'];
} else {
    $station_name = 'user';
}
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
            /* Reduced header text size */
            font-size: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .custom-table tbody td {
            padding: 15px;
            font-size: 0.70rem;
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
            /*padding: 0.5em 0.8em;*/
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
        
        /* Legend Badge Styling */
        .legend-badge {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 8px;
            margin-left: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            cursor: default;
            font-weight: 500;
        }
        
        /* Ensure the legend is hidden during print */
        @media print {
            .legend-badge {
                display: none !important;
            }
        }
        
        /* Enhanced Shadow for Table Container */
        .shadow-sm {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            border-radius: 12px;
        }
        
        /* Print table styles for better display */
        @media screen {
            .print-table {
                min-width: 100%;
            }
        }
        
        /* Print-specific styles */
        @media print {
            /* Hide all elements except header and table */
            body * {
                visibility: hidden;
                background-color: white !important;
                color: black !important;
                box-shadow: none !important;
            }
            
            .main-header, .main-sidebar, .main-footer, .btn, 
            .filter-form, .dataTables_length, .dataTables_filter, 
            .dataTables_info, .dataTables_paginate, .legend-badge {
                display: none !important;
            }
            
            /* Show only the header and table */
            .print-only, .print-only * {
                visibility: visible;
                color: black !important;
            }
            
            /* Remove all background colors and shadows */
            .card, .card-body, .table thead th, .table tbody td {
                background: none !important;
                color: black !important;
                box-shadow: none !important;
                border-color: #000 !important;
            }
            
            /* Improve header for printing */
            .card-title {
                font-size: 18pt !important;
                font-weight: bold !important;
                margin-bottom: 15px !important;
            }
            
            .card-body h5 {
                font-size: 14pt !important;
                font-weight: bold !important;
            }
            
            .card-body small {
                font-size: 12pt !important;
            }
            
            /* Reset badge colors for printing */
            .badge, .print-score {
                background-color: white !important;
                color: black !important;
                border: none !important;
                box-shadow: none !important;
                font-weight: bold !important;
                text-shadow: none !important;
                border-radius: 0 !important;
                padding: 2px 5px !important;
                font-size: 13pt !important;
            }
            
            /* Add text descriptions for scores in print view */
            .print-score[class*="bg-success"]::after {
                font-size: 10pt;
                font-style: italic;
                opacity: 0.8;
            }
            
            .print-score[class*="bg-info"]::after {
                font-size: 10pt;
                font-style: italic;
                opacity: 0.8;
            }
            
            .print-score[class*="bg-warning"]::after {
                font-size: 10pt;
                font-style: italic;
                opacity: 0.8;
            }
            
            .print-score[class*="bg-danger"]::after {
                font-size: 10pt;
                font-style: italic;
                opacity: 0.8;
            }
            
            .print-score[class*="bg-dark"]::after {
                font-size: 10pt;
                font-style: italic;
                opacity: 0.8;
            }
            
            /* Hide scrollbars */
            .table-responsive {
                overflow: visible !important;
                max-width: none !important;
                width: 100% !important;
            }
            
            /* Ensure table is properly formatted */
            .table {
                width: 100% !important;
                border-collapse: collapse !important;
                table-layout: fixed !important;
                max-width: 100% !important;
                margin: 0 !important;
            }
            
            .table th, .table td {
                border: 1px solid #000 !important;
                padding: 8px !important;
                text-align: center !important;
                background: none !important;
                font-size: 12pt !important;
                font-weight: 500 !important;
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
            }
            
            /* Make headers bolder */
            .table th {
                font-weight: bold !important;
                font-size: 13pt !important;
            }
            
            /* Hide all animations and transforms */
            * {
                animation: none !important;
                transform: none !important;
                transition: none !important;
            }
            
            /* Adjust container width for printing */
            .container-fluid, .container {
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: none !important;
            }
            
            /* Ensure page breaks appropriately */
            .card {
                page-break-inside: avoid;
            }
            
            tr {
                page-break-inside: avoid;
            }
            
            thead {
                display: table-header-group;
            }
            
            /* Set landscape orientation for better fitting */
            @page {
                size: landscape;
                margin: 0.5cm;
            }
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
    // Check if data comes from POST (from datewise report) or GET parameters
    $from_date = '';
    $to_date = '';
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $from = $_POST['from'] ?? '';
        $to = $_POST['to'] ?? '';
        $from_date = $from ? date('Y-m-d', strtotime($from)) : '';
        $to_date = $to ? date('Y-m-d', strtotime($to)) : '';
    } elseif (isset($_GET['from']) && isset($_GET['to'])) {
        // Handle GET parameters (if needed)
        $from_date = $_GET['from'];
        $to_date = $_GET['to'];
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
                    <button class="btn btn-info btn-lg shadow-sm px-4 animate-hover" type="button" onclick="printReport()">
                        <i class="bi bi-printer"></i> Print Report
                    </button>
                </div>
            </div>
        </div>
    </form>
    
    <!-- Score Legend -->
    <div class="row mt-3 justify-content-center">
    <div class="col-12 text-center">
        <div class="d-inline-flex flex-wrap gap-2 align-items-center justify-content-center">
            <span class="badge bg-success legend-badge">5 - Excellent</span>
            <span class="badge bg-info legend-badge">4 - Good</span>
            <span class="badge bg-warning legend-badge">3 - Average</span>
            <span class="badge bg-danger legend-badge">2 - Poor</span>
            <span class="badge bg-dark legend-badge">1 - Very Poor</span>
        </div>
    </div>
</div>

</div>

                <!-- Table Section -->
                <?php
                // Show results if POST request is made OR if we have valid date parameters
                if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($from_date && $to_date)) {
                    ?>
                    <div class="table-responsive animate-fade-in print-only">
                        <?php
                        function getBadgeClass($rating)
                        {
                            switch ($rating) {
                                case 5: return "bg-success";     // Excellent - Green
    case 4: return "bg-info";        // Good - Blue
    case 3: return "bg-warning";     // Average - Yellow
    case 2: return "bg-danger";      // Poor - Red
    case 1: return "bg-dark";        // Very Poor - Dark Gray
    default: return "bg-secondary";  // Not rated or invalid
                            }
                        }

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

                        // Fetch feedback data for the specified station
                        $sql_feedback = "
    SELECT 
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
    GROUP BY ff.id
    ORDER BY ff.created_at DESC
";

        $stmt_feedback = $conn->prepare($sql_feedback);
        $stmt_feedback->bind_param("iss", $station_id, $from_date, $to_date);
        $stmt_feedback->execute();
        $feedback_data = $stmt_feedback->get_result();

        // Calculate total feedback statistics for the header
        $total_feedbacks = 0;
        $total_score_sum = 0;
        $temp_data = [];
        
        while ($row = $feedback_data->fetch_assoc()) {
            $temp_data[] = $row; // Store data for later use
            $total_feedbacks++;
            
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
            $total_score_sum += $individual_score;
        }
        
        $average_total_score = $total_feedbacks > 0 ? $total_score_sum / $total_feedbacks : 0;
        $psi_percentage = ($average_total_score / $max_rating_score) * 100; // Now dynamic
        ?>

        <!-- Summary Header -->
        <div class="row mb-4 print-only">
            <div class="col-12">
                <div class="card shadow-sm" style="background: linear-gradient(135deg, #48a9d4, #3e8ec7); color: white;">
                    <div class="card-body text-center">
                        <h4 class="card-title mb-3">
                            <i class="bi bi-train-front"></i> <?= !empty($station_name) ? htmlspecialchars($station_name) : 'Station' ?> - Feedback Report
                        </h4>
                        <div class="row">
                            <div class="col-md-4">
                                <h5 class="mb-1"><?= $total_feedbacks ?></h5>
                                <small>Total Feedbacks</small>
                            </div>
                            <div class="col-md-4">
                                <h5 class="mb-1"><?= number_format($average_total_score, 2) ?>/<?= number_format($max_rating_score, 2) ?></h5>
                                <small>Average Score</small>
                            </div>
                            <div class="col-md-4">
                                <h5 class="mb-1"><?= number_format($psi_percentage, 2) ?>%</h5>
                                <small>Passenger Satisfaction Index (PSI)</small>
                            </div>
                        </div>
                        <?php if ($from_date && $to_date): ?>
                        <div class="mt-2">
                            <small><i class="bi bi-calendar-range"></i> Period: <?= date('d M Y', strtotime($from_date)) ?> to <?= date('d M Y', strtotime($to_date)) ?></small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

                        <table id="dataTable" class="print-only table-padding table table-bordered table-hover shadow-sm custom-table print-table">
                            <thead class="table-primary">
                                <tr>
                                    <th>Sr.No.</th>
                                    <th>Date</th>
                                    <th>Customer Name</th>
                                    <th>Customer Phone No</th>
                                    <th>Platform No.</th>
                                    <th>PNR No.</th>
                                    <th>Overall Score</th>
                                    <?php foreach ($questions as $question_text): ?>
                                        <th><?= $question_text ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sr_no = 1;
                                foreach ($temp_data as $row) {
                                    $ratings = [];
                                    $overall_score = 0;
                                    $rating_count = 0;

                                    if (!empty($row['question_ratings'])) {
                                        foreach (explode(',', $row['question_ratings']) as $rating_data) {
                                            [$question_id, $rating] = explode(':', $rating_data);
                                            $ratings[$question_id] = (int) $rating;
                                            $overall_score += (int) $rating; // Include 0 in the calculation
                                            $rating_count++;
                                        }
                                    }

                                    // Calculate overall score, including 0 values
                                    $overall_score = $rating_count > 0 ? $overall_score / $rating_count : 0;

                                    echo "<tr>";
                                    echo "<td>{$sr_no}</td>";
                                    echo "<td>" . date('d/m/Y', strtotime($row['created_at'])) . "</td>";
                                //   echo "<td><a href='score-card.php?form_id={$row['form_id']}' class='clickable-name' target='_blank'>{$row['passenger_name']}</a></td>";
                                  
                                  echo "<td>{$row['passenger_name']}</td>";


                                    echo "<td>{$row['passenger_mobile']}</td>";
                                    echo "<td>PF No. {$row['platform_no']}</td>";
                                    echo "<td>PNR No. {$row['pnr_number']}</td>";

                                    // Display overall score (including 0 in calculation)
                                    echo "<td><span class='badge " . getBadgeClass(round($overall_score)) . " shadow-sm print-score'>" . number_format($overall_score, 2) . "</span></td>";

                                    // Display individual question ratings
                                    foreach ($questions as $question_id => $question_text) {
                                        if (isset($ratings[$question_id])) {
                                            $rating = $ratings[$question_id] === 0 ? "NA" : $ratings[$question_id];
                                            $badgeClass = getBadgeClass($ratings[$question_id]);
                                        } else {
                                            $rating = "NA";
                                            $badgeClass = "bg-secondary"; // Use Bootstrap's secondary color
                                        }

                                        echo "<td><span class='badge {$badgeClass} shadow-sm print-score'>{$rating}</span></td>";
                                    }

                                    echo "</tr>";
                                    $sr_no++;
                                }
                                ?>
                            </tbody>
                        </table>


                        </tbody>
                        </table>

                        <?php
                        $stmt_feedback->close();
                        $conn->close();
                        ?>



                    </div>
                    <?php
                } else {
                    // Show search icon and text when the request is not a GET
                    echo '<div class="mt-5" style="text-align: center; font-size: 50px;">';
                    echo '<i class="bi bi-search"></i>';  // Bootstrap search icon
                    echo '<p>Search For Feedback</p>';
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
            $('#dataTable').DataTable({
                responsive: true,
                pageLength: 5,
                lengthMenu: [[5, 10, 25, 50, 100, -1], [5, 10, 25, 50, 100, "All"]],
                language: {
                    search: "Filter Results:",
                },
            });
        });
        
        // Custom print function with optimized settings
        function printReport() {
            // Apply any additional print-specific logic
            window.print();
        }
    </script>
</body>
<!--end::Body-->

</html>