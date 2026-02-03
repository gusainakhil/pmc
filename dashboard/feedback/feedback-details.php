<?php

session_start();
include 'connection.php';
// Get parameters
$date = $_GET['date'] ?? '';
$station_id = $_GET['station_id'] ?? $_SESSION['station_id'];

if (!$date || !$station_id) {
    echo "Invalid parameters";
    exit();
}

// Fetch station details
$sql_station = "SELECT name, feedback_target FROM feedback_stations WHERE id = ?";
$stmt_station = $conn->prepare($sql_station);
$stmt_station->bind_param("i", $station_id);
$stmt_station->execute();
$result_station = $stmt_station->get_result();
$station_data = $result_station->fetch_assoc();
$station_name = $station_data['name'] ?? 'Unknown Station';
$daily_target = (int) ($station_data['feedback_target'] ?? 0);
$stmt_station->close();

// Fetch rating parameters
$sql_rating_params = "SELECT value FROM rating_parameters WHERE station_id = ?";
$stmt_rating_params = $conn->prepare($sql_rating_params);
$stmt_rating_params->bind_param("i", $station_id);
$stmt_rating_params->execute();
$result_rating_params = $stmt_rating_params->get_result();
$rating_data = $result_rating_params->fetch_assoc();
$max_rating_score = (int) ($rating_data['value'] ?? 3);
$stmt_rating_params->close();

// Fetch questions
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

// Fetch feedback data for the specific date
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
    WHERE ff.station_id = ? AND DATE(ff.created_at) = ?
    GROUP BY ff.id
    ORDER BY ff.created_at ASC
";

$stmt_feedback = $conn->prepare($sql_feedback);
$stmt_feedback->bind_param("is", $station_id, $date);
$stmt_feedback->execute();
$feedback_data = $stmt_feedback->get_result();

// Calculate statistics
$total_feedbacks = 0;
$total_score_sum = 0;
$feedbacks = [];

while ($row = $feedback_data->fetch_assoc()) {
    $feedbacks[] = $row;
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

$average_score = $total_feedbacks > 0 ? $total_score_sum / $total_feedbacks : 0;
$quality_psi = ($average_score / $max_rating_score) * 100;
$daily_quantity_achievement = $daily_target > 0 ? ($total_feedbacks / $daily_target) : 0;
$date_adjusted_psi = $quality_psi * $daily_quantity_achievement;
$target_achieved = $total_feedbacks >= $daily_target;

function getBadgeClass($rating) {
    switch ($rating) {
         case 5: return "bg-success";     // Excellent - Green
    case 4: return "bg-info";        // Very Good - Blue
    case 3: return "bg-primary";     // Good - Blue
    case 2: return "bg-warning";     // Average - Yellow
    case 1: return "bg-danger";      // Poor - Red
    default: return "bg-secondary";  // Not rated or invalid
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Feedback Details - <?= date('d M Y', strtotime($date)) ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
        integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q=" crossorigin="anonymous" />
    <!-- DataTables CSS -->
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

        .custom-table thead th, .table thead th {
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

        .custom-table tbody td, .table tbody td {
            padding: 15px;
            font-size: 0.70rem;
            border-bottom: 1px solid #dee2e6;
            text-align: center;
            vertical-align: middle;
            background-color: #f8f9fa;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        /* 3D Hover Effect on Rows */
        .custom-table tbody tr, .table tbody tr {
            transition: transform 0.5s ease, box-shadow 0.5s ease;
        }

        .custom-table tbody tr:hover, .table tbody tr:hover {
            transform: translateZ(20px) scale(1.02);
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.2);
        }

        /* Zebra stripes for enhanced readability */
        .custom-table tbody tr:nth-child(even) td, .table tbody tr:nth-child(even) td {
            background-color: #f1f1f1;
        }

        .custom-table tbody tr:nth-child(odd) td, .table tbody tr:nth-child(odd) td {
            background-color: #ffffff;
        }

        /* Rounded Corners for first and last cells in each row */
        .custom-table tbody tr td:first-child, .table tbody tr td:first-child {
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }

        .custom-table tbody tr td:last-child, .table tbody tr td:last-child {
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
        
        .summary-card { 
            background: linear-gradient(135deg, #48a9d4, #3e8ec7); 
            color: white;
            border-radius: 10px;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 10px;
        }
        
        .table-responsive { 
            border-radius: 10px; 
        }
        
        /* Print table styles for better display */
        @media screen {
            .print-table {
                min-width: 100%;
            }
            
            /* Hide print-only elements when not printing */
            .print-header-text {
                display: none;
            }
        }
        
        /* Additional table styling for better visibility */
        .table th {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        /* Additional print layout adjustments */
        .card-header {
            background: linear-gradient(135deg, #48a9d4, #3e8ec7);
            color: white;
            padding: 10px 15px;
        }
        
        /* Better table layout */
        .print-table th, .print-table td {
            white-space: normal;
            word-break: break-word;
        }
        
        /* Print-specific styles */
        @media print {
            /* Reset the entire page for print */
            @page {
                size: landscape;
                margin: 0.3cm; /* Reduce margins to allow more content */
                scale: 90%; /* Scale down the content */
            }
            
            html, body {
                width: 100% !important;
                height: auto !important;
                overflow: visible !important;
                font-size: 9pt !important; /* Reduce base font size */
                background-color: white !important;
            }
            
            /* Hide all elements except header and table */
            body * {
                visibility: hidden;
                background-color: white !important;
                color: black !important;
                box-shadow: none !important;
                text-shadow: none !important;
            }
            
            /* Hide specific elements that shouldn't appear in print */
            .btn, 
            .dataTables_length, 
            .dataTables_filter, 
            .dataTables_info, 
            .dataTables_paginate, 
            .legend-badge,
            .score-legend {
                display: none !important;
            }
            
            /* Show only the header and table */
            .print-only, 
            .print-only * {
                visibility: visible !important;
                color: black !important;
            }
            
            /* Clean all cards for print */
            .card, 
            .card-body, 
            .card-header,
            .table thead th, 
            .table tbody td,
            .alert {
                background: none !important;
                color: black !important;
                box-shadow: none !important;
                border-color: #000 !important;
                border-radius: 0 !important;
            }
            
            /* Improve typography for printing */
            h2, h3, h4, h5, .card-title {
                color: black !important;
                font-weight: bold !important;
                margin-bottom: 5px !important; /* Reduce margin */
                font-family: serif !important;
            }
            
            h2 {
                font-size: 14pt !important; /* Smaller size */
            }
            
            h3 {
                font-size: 12pt !important; /* Smaller size */
            }
            
            .card-title {
                font-size: 12pt !important; /* Smaller size */
            }
            
            .card-body h5 {
                font-size: 11pt !important; /* Smaller size */
            }
            
            .card-body small, small {
                font-size: 8pt !important; /* Smaller size */
            }
            
            /* Make summary cards more compact */
            .summary-card, .stats-card {
                background: none !important;
                border: 1px solid #000 !important;
                margin-bottom: 5px !important; /* Reduce bottom margin */
            }
            
            .card-body {
                padding: 5px !important; /* Reduce padding */
            }
            
            /* Reset badge colors for printing */
            .badge, .print-score {
                background-color: white !important;
                color: black !important;
                border: 1px solid #888 !important;
                box-shadow: none !important;
                font-weight: bold !important;
                text-shadow: none !important;
                border-radius: 0 !important;
                padding: 1px 3px !important; /* Smaller padding */
                font-size: 8pt !important; /* Smaller font size */
                display: inline-block !important;
                min-width: 20px !important; /* Minimum width */
            }
            

            
            /* Make table more readable for print */
            .table-responsive {
                overflow: visible !important;
                max-width: none !important;
                width: 100% !important;
                border: none !important;
            }
            
            /* Format table for clean printing */
            .table, 
            table#feedbackTable {
                width: 100% !important;
                border-collapse: collapse !important;
                table-layout: fixed !important; /* Force fixed table layout */
                border-spacing: 0 !important;
                margin: 0 !important;
                font-size: 8pt !important; /* Reduce font size further */
                page-break-inside: auto !important;
                transform: scale(0.95) !important; /* Slightly scale down the table */
                transform-origin: top left !important;
            }
            
            /* Table cell formatting */
            .table th, 
            .table td {
                border: 1px solid #000 !important;
                padding: 3px !important; /* Reduce padding */
                text-align: center !important;
                background: none !important;
                vertical-align: middle !important;
                font-size: 8pt !important; /* Smaller font size */
                font-weight: normal !important;
                word-wrap: break-word !important;
                overflow-wrap: break-word !important;
                max-width: 100px !important; /* Limit cell width */
                white-space: normal !important;
            }
            
            /* Make table headers more compact and wrap text */
            .table th {
                font-weight: bold !important;
                font-size: 8pt !important; /* Smaller than TD content */
                background-color: #f8f8f8 !important;
                color: black !important;
                height: auto !important;
                vertical-align: middle !important;
                padding: 2px !important;
                line-height: 1.1 !important;
                word-break: break-word !important;
                white-space: normal !important;
            }
            
            /* Force long header text to wrap */
            .table th, .table td {
                overflow-wrap: break-word !important;
                word-wrap: break-word !important;
                word-break: break-word !important;
                -ms-hyphens: auto !important;
                -webkit-hyphens: auto !important;
                hyphens: auto !important;
            }
            
            /* Disable all animations and visual effects */
            * {
                animation: none !important;
                transform: none !important;
                transition: none !important;
                text-shadow: none !important;
            }
            
            /* Make links print normally */
            a, a:visited {
                text-decoration: none !important;
                color: black !important;
            }
            
            /* Container adjustments */
            .container-fluid, 
            .container {
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: none !important;
            }
            
            /* Page break controls */
            .card {
                page-break-inside: avoid !important;
            }
            
            tr {
                page-break-inside: avoid !important;
            }
            
            thead {
                display: table-header-group !important;
            }
            
            tfoot {
                display: table-row-group !important;
            }
            
            /* Show the print-specific header */
            .print-header-text {
                display: block !important;
                visibility: visible !important;
                margin-top: 5px !important; /* Reduced margin */
                margin-bottom: 10px !important; /* Reduced margin */
            }
            
            /* Compact header for print */
            .print-header-text h2 {
                font-size: 12pt !important; /* Smaller header */
                margin-bottom: 2px !important;
            }
            
            .print-header-text h3 {
                font-size: 10pt !important; /* Smaller header */
                margin-bottom: 2px !important;
            }
            
            /* Make sure alert status is visible somehow */
            .alert-success {
                border: 2px solid #000 !important;
            }
            
            .alert-warning {
                border: 2px dashed #000 !important;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4 print-only">
        <!-- Header -->
        <div class="row mb-4 print-only">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="bi bi-calendar-date"></i> Feedback Details - <?= date('d M Y (l)', strtotime($date)) ?></h2>
                    <div>
                        <?php if ($station_id != 51): ?>
                        <button class="btn btn-info me-2" onclick="printReport()">
                            <i class="bi bi-printer"></i> Print Report
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-secondary" onclick="window.close()">
                            <i class="bi bi-x-circle"></i> Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4 print-only">
            <div class="col-md-3">
                <div class="card summary-card">
                    <div class="card-body text-center">
                        <h3><?= $total_feedbacks ?></h3>
                        <small>Total Feedbacks</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card">
                    <div class="card-body text-center">
                        <h3><?= $daily_target ?></h3>
                        <small>Daily Target</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h3><?= number_format($average_score, 2) ?></h3>
                        <small>Average Score</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <h3><?= number_format($date_adjusted_psi, 1) ?>%</h3>
                        <small>PSI</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Target Status Alert -->
        <div class="row mb-4 print-only">
            <div class="col-12">
                <div class="alert alert-<?= $target_achieved ? 'success' : 'warning' ?>" role="alert">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Target Status:</strong> <?= $total_feedbacks ?> / <?= $daily_target ?> feedbacks
                            <?php if ($target_achieved): ?>
                                <i class="bi bi-trophy-fill"></i> Target Achieved!
                            <?php else: ?>
                                - Need <?= $daily_target - $total_feedbacks ?> more to reach target
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Station:</strong> <?= strtoupper($station_name) ?> |
                            <strong>Date:</strong> <?= date('d M Y', strtotime($date)) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Score Legend -->
        <div class="row mb-3 score-legend">
            <div class="col-12 text-center">
                <div class="card shadow-sm p-2">
                    <div class="d-inline-flex flex-wrap gap-2 align-items-center justify-content-center">
                        <span class="me-2 fw-bold">Score Guide:</span>
                        <span class="badge bg-success legend-badge">5 - Excellent</span>
                        <span class="badge bg-info legend-badge">4 - Very Good</span>
                        <span class="badge bg-primary legend-badge">3 - Good</span>
                        <span class="badge bg-warning legend-badge">2 - Average</span>
                        <span class="badge bg-danger legend-badge">1 - Poor</span>
                        <span class="badge bg-secondary legend-badge">NA - Not Rated</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feedback Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="bi bi-list-ul"></i> Individual Feedback Details</h5>
                    </div>
                    <!-- Print-only header text that will appear on printed pages -->
                    <div class="print-header-text print-only">
                        <h2 class="text-center mb-3">Feedback Details - <?= strtoupper($station_name) ?></h2>
                        <h3 class="text-center mb-2"><?= date('d M Y (l)', strtotime($date)) ?></h3>
                        <div class="text-center mb-2" style="font-size:8pt;">
                            <strong>Score Guide:</strong> 
                            5=Excellent, 4=Very Good, 3=Good, 2=Average, 1=Poor, NA=Not Rated
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($total_feedbacks > 0): ?>
                            <div class="table-responsive print-only">
                                <table id="feedbackTable" class="table table-striped table-hover custom-table animate-fade-in print-only print-table">
                                    <thead class="print-header">
                                        <tr>
                                            <th>Sr.No.</th>
                                            <?php
                                            if($station_id != '51'){
                                                ?>
                                            <th>Time</th>
                                            <?php
                                            }
                                            ?>
                                            
                                            <th>Customer Name</th>
                                            <th>Phone</th>
                                            <th>Platform</th>
                                            <th>PNR</th>
                                            <th>Overall Score</th>
                                            <?php foreach ($questions as $question_text): ?>
                                                <th><?= htmlspecialchars($question_text) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sr_no = 1;
                                        foreach ($feedbacks as $index => $feedback):
                                            $ratings = [];
                                            $overall_score = 0;
                                            $rating_count = 0;

                                            if (!empty($feedback['question_ratings'])) {
                                                foreach (explode(',', $feedback['question_ratings']) as $rating_data) {
                                                    [$question_id, $rating] = explode(':', $rating_data);
                                                    $ratings[$question_id] = (int) $rating;
                                                    $overall_score += (int) $rating;
                                                    $rating_count++;
                                                }
                                            }

                                            $individual_score = $rating_count > 0 ? $overall_score / $rating_count : 0;
                                        ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <?php
                                            if($station_id != '51'){
                                                ?>
                                            <td><?= date('H:i', strtotime($feedback['created_at'])) ?></td>
                                            <?php
                                            }
                                            ?>
                                            <!--<td>-->
                                            <!--    <a href="score-card.php?form_id=<?= $feedback['form_id'] ?>" -->
                                            <!--       target="_blank" class="text-decoration-none">-->
                                            <!--        <?= htmlspecialchars($feedback['passenger_name']) ?>-->
                                            <!--    </a>-->
                                            <!--</td>-->
                                            <td><?= htmlspecialchars($feedback['passenger_name']) ?></td>
                                            <td><?= htmlspecialchars($feedback['passenger_mobile']) ?></td>
                                            <td>PF <?= htmlspecialchars($feedback['platform_no']) ?></td>
                                            <td><?= htmlspecialchars($feedback['pnr_number']) ?></td>
                                            <td>
                                                <span class="badge print-score <?= getBadgeClass(round($individual_score)) ?>">
                                                    <?= number_format($individual_score, 2) ?>
                                                </span>
                                            </td>
                                            <?php foreach ($questions as $question_id => $question_text): ?>
                                                <td>
                                                    <?php if (isset($ratings[$question_id])): ?>
                                                        <span class="badge print-score <?= getBadgeClass($ratings[$question_id]) ?>">
                                                            <?= $ratings[$question_id] === 0 ? 'NA' : $ratings[$question_id] ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge print-score bg-secondary">NA</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php 
                                        $sr_no++;
                                        endforeach; 
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-exclamation-triangle display-1 text-warning"></i>
                                <h4 class="mt-3">No Feedback Data</h4>
                                <p class="text-muted">No feedback was collected on this date.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#feedbackTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                language: {
                    search: "Search Feedback:",
                    lengthMenu: "Show _MENU_ entries",
                    zeroRecords: "No matching records found",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        });
        
        // Custom print function with optimized settings
        function printReport() {
            // Set up print environment
            var originalTitle = document.title;
            document.title = "Feedback Details - <?= date('d M Y', strtotime($date)) ?> - <?= strtoupper($station_name) ?>";
            
            // Adjust table columns for printing
            var table = $('#feedbackTable').DataTable();
            
            // Try to auto-adjust column widths
            table.columns.adjust();
            
            // Print with a slight delay to ensure styles are applied
            setTimeout(function() {
                window.print();
                document.title = originalTitle;
            }, 500); // Increased delay to allow rendering
        }
    </script>
</body>
</html>

<?php
$stmt_feedback->close();
$conn->close();
?>