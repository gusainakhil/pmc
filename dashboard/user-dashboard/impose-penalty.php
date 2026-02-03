<?php
ob_start();
include "../../connection.php";
session_start();

if (!isset($_SESSION['OrgID'], $_SESSION['stationId'])) {
    die("Session data missing.");
}
$org_id = $_SESSION['OrgID'];
$station_id = $_SESSION['stationId']; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Impose Penalty | Railway Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            padding: 10px;
        }

        .main-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin: 0 auto;
            max-width: 1200px;
        }

        .page-header {
            background: #3c8dbc;
            color: white;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }

        .page-title {
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }

        .page-subtitle {
            font-size: 1rem;
            margin: 5px 0 0 0;
            opacity: 0.9;
        }

        .accordion-item {
            border: 1px solid #dee2e6;
            margin-bottom: 15px;
            border-radius: 8px;
            overflow: hidden;
        }

        .penalty-header {
            font-weight: 600;
            background: #3c8dbc;
            color: white;
            padding: 15px 20px;
            border: none;
            font-size: 1rem;
            width: 100%;
            text-align: left;
        }

        .penalty-header:focus {
            box-shadow: none;
            border: none;
            color: white;
        }

        .penalty-header:hover {
            background: #3c8dbc;
            color: white;
        }

        .accordion-body {
            padding: 25px;
            background: #f8f9fa;
        }

        .penalty-table th,
        .penalty-table td {
            vertical-align: middle;
            text-align: center;
            padding: 6px;
            border: 1px solid #dee2e6;
        }

        .penalty-table th {
            background: #3c8dbc;
            color: white;
            font-weight: 600;
        }

        .penalty-table tbody tr {
            background: white;
        }

        .penalty-table tbody tr:hover {
            background: #f8f9fa;
        }

        .form-control {
            border-radius: 5px;
            border: 1px solid #ced4da;
            padding: 10px 12px;
        }

        .form-control:focus {
            border-color: #3c8dbc;
            box-shadow: 0 0 0 0.2rem rgba(60, 141, 188, 0.25);
        }

        .btn-submit {
            background: #3c8dbc;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            font-weight: 600;
            color: white;
            cursor: pointer;
        }

        .btn-submit:hover {
            background: #0056b3;
            color: white;
        }

        .total-penalty {
            background: #dc3545;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1.1rem;
            margin-top: 15px;
            display: inline-block;
        }

        .penalty-icon {
            margin-right: 8px;
        }

        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border: 1px solid #dee2e6;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 20px;
                margin: 10px;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .penalty-header {
                padding: 12px 15px;
                font-size: 0.9rem;
            }

            .accordion-body {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-gavel penalty-icon"></i>
                IMPOSE PENALTY
            </h1>
            <p class="page-subtitle">Manage and track penalty impositions for railway cleaning violations</p>
        </div>



        <div class="accordion" id="penaltyAccordion">

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button collapsed penalty-header" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                        <i class="fas fa-trash-alt penalty-icon"></i>
                        SpotPenalty
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne"
                    data-bs-parent="#penaltyAccordion">
                    <div class="accordion-body">
                        <!-- Form for SpotPenalty -->
                        <form method="POST" action="" id="spotPenaltyForm" class="needs-validation" novalidate autocomplete="off">
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold" for="spot_penalty_date">
                                        <i class="fas fa-calendar me-2"></i>Penalty Date
                                    </label>
                                    <input type="date" class="form-control" name="spot_penalty_date" id="spot_penalty_date" required max="<?php echo date('Y-m-d'); ?>">
                                    <div class="invalid-feedback">Please select a valid date.</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold" for="spot_penalty_amount">
                                        <i class="fas fa-rupee-sign me-2"></i>Penalty Amount
                                    </label>
                                    <input type="number" class="form-control" name="spot_penalty_amount" id="spot_penalty_amount" min="1" required>
                                    <div class="invalid-feedback">Please enter a valid amount.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="spot_penalty_review">
                                        <i class="fas fa-comment me-2"></i>Penalty Review
                                    </label>
                                    <textarea class="form-control" name="spot_penalty_review" id="spot_penalty_review" rows="2" required maxlength="500"></textarea>
                                    <div class="invalid-feedback">Please provide a review (max 500 chars).</div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-submit mb-4 px-4 py-2">
                                <i class="fas fa-plus me-2"></i>Submit Penalty
                            </button>
                        </form>
                        <?php
                        // Backend for SpotPenalty submission
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['spot_penalty_date'], $_POST['spot_penalty_amount'], $_POST['spot_penalty_review'])) {
                            $date = mysqli_real_escape_string($conn, $_POST['spot_penalty_date']);
                            $amount = (int) $_POST['spot_penalty_amount'];
                            $review = mysqli_real_escape_string($conn, $_POST['spot_penalty_review']);

                            if ($date && $amount > 0 && $review) {
                                $insert = "INSERT INTO `baris_penalty` (`OrgID`, `penalty_type`, `penalty_date`, `penalty_amount`, `penalty_review`, `created_date`) VALUES ('$org_id', 'SpotPenalty', '$date', '$amount', '$review', NOW())";
                                if (mysqli_query($conn, $insert)) {
                                    header("Location: " . $_SERVER['REQUEST_URI'] . "?spot_success=1");
                                    exit();
                                } else {
                                    echo '<div class="alert alert-danger mt-2">Error adding penalty. Please try again.</div>';
                                }
                            } else {
                                echo '<div class="alert alert-warning mt-2">Please fill all fields correctly.</div>';
                            }
                        }
                        // Show success message if redirected
                        if (isset($_GET['spot_success']) && $_GET['spot_success'] == 1) {
                            echo '<div class="alert alert-success mt-2">Spot penalty added successfully.</div>';
                        }
                        ?>

                        <?php
                        $query = "SELECT * FROM `baris_penalty` WHERE `OrgID` = $org_id AND penalty_type = 'SpotPenalty' ORDER BY penalty_date DESC";
                        $result = mysqli_query($conn, $query);

                        if (mysqli_num_rows($result) > 0) {
                            ?>
                            <div class="table-responsive mt-4">
                                <table class="table penalty-table">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-calendar me-2"></i>Penalty Date</th>
                                            <th><i class="fas fa-rupee-sign me-2"></i>Amount</th>
                                            <th><i class="fas fa-comment me-2"></i>Review</th>
                                            <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $total_penalty = 0;
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $penalty_date = date('Y-m-d', strtotime($row['created_date']));
                                            $penalty_amount = $row['penalty_amount'];
                                            $penalty_review = $row['penalty_review'];
                                            $total_penalty += $penalty_amount;
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-semibold"><?php echo $penalty_date; ?></span>
                                                        <small class="text-muted">
                                                            <?php
                                                            $date1 = new DateTime($penalty_date);
                                                            $date2 = new DateTime();
                                                            $interval = $date1->diff($date2);
                                                            if ($interval->y > 0) {
                                                                echo $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                                                            } elseif ($interval->m > 0) {
                                                                echo $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                                                            } elseif ($interval->d > 0) {
                                                                echo $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                                                            } else {
                                                                echo 'Today';
                                                            }
                                                            ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-danger fs-6">₹<?php echo number_format($penalty_amount); ?></span>
                                                </td>
                                                <td>
                                                    <div class="text-start">
                                                        <p class="mb-0"><?php echo htmlspecialchars($penalty_review); ?></p>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                <div class="text-end">
                                    <span class="total-penalty">
                                        <i class="fas fa-calculator me-2"></i>Total Penalty:
                                        ₹<?php echo number_format($total_penalty); ?>
                                    </span>
                                </div>
                            </div>
                            <?php
                        } else {
                            ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-list"></i>
                                <h5>No Penalties Recorded</h5>
                                <p>No spot penalties have been imposed yet.</p>
                                <small class="text-muted">Add the first penalty using the form above.</small>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
            <!-- Additional penalty types can be added here OPEN BURNING OF WASTE IN RAILWAYS PREMISES -->
            <!-- Additional penalty types can be added here OPEN BURNING OF WASTE IN RAILWAYS PREMISES -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOpenBurning">
                    <button class="accordion-button collapsed penalty-header" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseOpenBurning" aria-expanded="false" aria-controls="collapseOpenBurning">
                        <i class="fas fa-fire-alt penalty-icon"></i>
                        OPEN BURNING OF WASTE IN RAILWAYS PREMISES
                    </button>
                </h2>
                <div id="collapseOpenBurning" class="accordion-collapse collapse" aria-labelledby="headingOpenBurning"
                    data-bs-parent="#penaltyAccordion">
                    <div class="accordion-body">
                        <!-- Form for OpenBurningPenalty -->
                        <form method="POST" action="" id="openBurningPenaltyForm" class="needs-validation" novalidate autocomplete="off">
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold" for="open_burning_penalty_date">
                                        <i class="fas fa-calendar me-2"></i>Penalty Date
                                    </label>
                                    <input type="date" class="form-control" name="open_burning_penalty_date" id="open_burning_penalty_date" required max="<?php echo date('Y-m-d'); ?>">
                                    <div class="invalid-feedback">
                                        Please select a valid date.
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold" for="open_burning_penalty_amount">
                                        <i class="fas fa-rupee-sign me-2"></i>Penalty Amount
                                    </label>
                                    <input type="number" class="form-control" name="open_burning_penalty_amount" id="open_burning_penalty_amount" min="1" required>
                                    <div class="invalid-feedback">
                                        Please enter a valid amount.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="open_burning_penalty_review">
                                        <i class="fas fa-comment me-2"></i>Penalty Review
                                    </label>
                                    <textarea class="form-control" name="open_burning_penalty_review" id="open_burning_penalty_review" rows="2" required maxlength="500"></textarea>
                                    <div class="invalid-feedback">
                                        Please provide a review (max 500 chars).
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-submit mb-4 px-4 py-2">
                                <i class="fas fa-plus me-2"></i>Submit Penalty
                            </button>
                        </form>
                        <?php
                        // Backend for OpenBurningPenalty submission
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['open_burning_penalty_date'], $_POST['open_burning_penalty_amount'], $_POST['open_burning_penalty_review'])) {
                            $date = mysqli_real_escape_string($conn, $_POST['open_burning_penalty_date']);
                            $amount = (int) $_POST['open_burning_penalty_amount'];
                            $review = mysqli_real_escape_string($conn, $_POST['open_burning_penalty_review']);

                            if ($date && $amount > 0 && $review) {
                                $insert = "INSERT INTO `baris_penalty` (`OrgID`, `penalty_type`, `penalty_date`, `penalty_amount`, `penalty_review`, `created_date`) VALUES ('$org_id', 'OpenBurningPenalty', '$date', '$amount', '$review', NOW())";
                                if (mysqli_query($conn, $insert)) {
                                    header("Location: " . $_SERVER['REQUEST_URI'] . "?openburning_success=1");
                                    exit();
                                } else {
                                    echo '<div class="alert alert-danger mt-2">Error adding penalty. Please try again.</div>';
                                }
                            } else {
                                echo '<div class="alert alert-warning mt-2">Please fill all fields correctly.</div>';
                            }
                        }
                        // Show success message if redirected
                        if (isset($_GET['openburning_success']) && $_GET['openburning_success'] == 1) {
                            echo '<div class="alert alert-success mt-2">Open burning penalty added successfully.</div>';
                        }
                        ?>

                        <?php
                        $query = "SELECT * FROM `baris_penalty` WHERE `OrgID` = $org_id AND penalty_type = 'OpenBurningPenalty' ORDER BY penalty_date DESC";
                        $result = mysqli_query($conn, $query);

                        if (mysqli_num_rows($result) > 0) {
                            ?>
                            <div class="table-responsive mt-4">
                                <table class="table penalty-table">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-calendar me-2"></i>Penalty Date</th>
                                            <th><i class="fas fa-rupee-sign me-2"></i>Amount</th>
                                            <th><i class="fas fa-comment me-2"></i>Review</th>
                                            <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $total_penalty = 0;
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $penalty_date = date('Y-m-d', strtotime($row['created_date']));
                                            $penalty_amount = $row['penalty_amount'];
                                            $penalty_review = $row['penalty_review'];
                                            $total_penalty += $penalty_amount;
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-semibold"><?php echo $penalty_date; ?></span>
                                                        <small class="text-muted">
                                                            <?php
                                                            $date1 = new DateTime($penalty_date);
                                                            $date2 = new DateTime();
                                                            $interval = $date1->diff($date2);
                                                            if ($interval->y > 0) {
                                                                echo $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                                                            } elseif ($interval->m > 0) {
                                                                echo $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                                                            } elseif ($interval->d > 0) {
                                                                echo $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                                                            } else {
                                                                echo 'Today';
                                                            }
                                                            ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger fs-6">₹<?php echo number_format($penalty_amount); ?></span>
                                                </td>
                                                <td>
                                                    <div class="text-start">
                                                        <p class="mb-0"><?php echo htmlspecialchars($penalty_review); ?></p>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                <div class="text-end">
                                    <span class="total-penalty">
                                        <i class="fas fa-calculator me-2"></i>Total Penalty:
                                        ₹<?php echo number_format($total_penalty); ?>
                                    </span>
                                </div>
                            </div>
                            <?php
                        } else {
                            ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-list"></i>
                                <h5>No Penalties Recorded</h5>
                                <p>No open burning penalties have been imposed yet.</p>
                                <small class="text-muted">Add the first penalty using the form above.</small>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Enhanced second accordion item -->
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed penalty-header" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        <i class="fas fa-fire penalty-icon"></i>
                        NON REMOVAL OF GARBAGE FROM DUSTBINS
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo"
                    data-bs-parent="#penaltyAccordion">
                    <div class="accordion-body">
                        <!-- Form for Non Removal of Garbage -->
                        <form method="POST" action="" id="garbagePenaltyForm">
                            <div class="row mb-4">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-calendar me-2"></i>Penalty Date
                                    </label>
                                    <input type="date" class="form-control" name="garbage_penalty_date" required>
                                    <div class="invalid-feedback">
                                        Please select a valid date.
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-rupee-sign me-2"></i>Penalty Amount
                                    </label>
                                    <input type="number" class="form-control" name="garbage_penalty_amount" min="1"
                                        required>
                                    <div class="invalid-feedback">
                                        Please enter a valid amount.
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3"></div>
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-comment me-2"></i>Penalty Review
                                </label>
                                <textarea class="form-control" name="garbage_penalty_review" rows="2"
                                    required></textarea>
                                <div class="invalid-feedback">
                                    Please provide a review.
                                </div>
                            </div>
                    </div>
                    <button type="submit" class="btn btn-submit mb-4">
                        <i class="fas fa-plus me-2"></i>Submit Penalty
                    </button>
                    </form>
                    <?php
                    // Backend for Garbage Penalty submission
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['garbage_penalty_date'], $_POST['garbage_penalty_amount'], $_POST['garbage_penalty_review'])) {
                        $date = mysqli_real_escape_string($conn, $_POST['garbage_penalty_date']);
                        $amount = (int) $_POST['garbage_penalty_amount'];
                        $review = mysqli_real_escape_string($conn, $_POST['garbage_penalty_review']);

                        if ($date && $amount > 0 && $review) {
                            $insert = "INSERT INTO `baris_penalty` (`OrgID`, `penalty_type`, `penalty_date`, `penalty_amount`, `penalty_review`, `created_date`) VALUES ('$org_id', 'NonremovalGarbagePenalty', '$date', '$amount', '$review', NOW())";
                            if (mysqli_query($conn, $insert)) {
                                header("Location: " . $_SERVER['REQUEST_URI'] . "?garbage_success=1");
                                exit();
                            } else {
                                echo '<div class="alert alert-danger mt-2">Error adding penalty. Please try again.</div>';
                            }
                        } else {
                            echo '<div class="alert alert-warning mt-2">Please fill all fields correctly.</div>';
                        }
                    }
                    // Show success message if redirected
                    if (isset($_GET['garbage_success']) && $_GET['garbage_success'] == 1) {
                        echo '<div class="alert alert-success mt-2">Garbage penalty added successfully.</div>';
                    }
                    ?>

                    <?php
                    $query = "SELECT * FROM `baris_penalty` WHERE `OrgID` = $org_id AND penalty_type = 'NonremovalGarbagePenalty' ORDER BY penalty_date DESC";
                    $result = mysqli_query($conn, $query);

                    if (mysqli_num_rows($result) > 0) {
                        ?>
                        <div class="table-responsive mt-4">
                            <table class="table penalty-table">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-calendar me-2"></i>Penalty Date</th>
                                        <th><i class="fas fa-rupee-sign me-2"></i>Amount</th>
                                        <th><i class="fas fa-comment me-2"></i>Review</th>
                                        <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_penalty = 0;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $penalty_date = date('Y-m-d', strtotime($row['created_date']));
                                        $penalty_amount = $row['penalty_amount'];
                                        $penalty_review = $row['penalty_review'];
                                        $total_penalty += $penalty_amount;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-semibold"><?php echo $penalty_date; ?></span>
                                                    <small class="text-muted">
                                                        <?php
                                                        $date1 = new DateTime($penalty_date);
                                                        $date2 = new DateTime();
                                                        $interval = $date1->diff($date2);
                                                        if ($interval->y > 0) {
                                                            echo $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                                                        } elseif ($interval->m > 0) {
                                                            echo $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                                                        } elseif ($interval->d > 0) {
                                                            echo $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                                                        } else {
                                                            echo 'Today';
                                                        }
                                                        ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-danger fs-6">₹<?php echo number_format($penalty_amount); ?></span>
                                            </td>
                                            <td>
                                                <div class="text-start">
                                                    <p class="mb-0"><?php echo htmlspecialchars($penalty_review); ?></p>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <div class="text-end">
                                <span class="total-penalty">
                                    <i class="fas fa-calculator me-2"></i>Total Penalty:
                                    ₹<?php echo number_format($total_penalty); ?>
                                </span>
                            </div>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h5>No Penalties Recorded</h5>
                            <p>No penalties have been imposed for non removal of garbage from dustbins yet.</p>
                            <small class="text-muted">Add the first penalty using the form above.</small>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Additional penalty types -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed penalty-header" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                    <i class="fas fa-broom penalty-icon"></i>
                    ROOF OF PLATFORM SHELTERS
                </button>
            </h2>
            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree"
                data-bs-parent="#penaltyAccordion">
                <div class="accordion-body">
                    <!-- Form for Roof of Platform Shelters Penalty -->
                    <form method="POST" action="" id="roofPenaltyForm">
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-calendar me-2"></i>Penalty Date
                                </label>
                                <input type="date" class="form-control" name="roof_penalty_date" required>
                                <div class="invalid-feedback">
                                    Please select a valid date.
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-rupee-sign me-2"></i>Penalty Amount
                                </label>
                                <input type="number" class="form-control" name="roof_penalty_amount" min="1" required>
                                <div class="invalid-feedback">
                                    Please enter a valid amount.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-comment me-2"></i>Penalty Review
                                </label>
                                <textarea class="form-control" name="roof_penalty_review" rows="2" required></textarea>
                                <div class="invalid-feedback">
                                    Please provide a review.
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-submit mb-4">
                            <i class="fas fa-plus me-2"></i>Submit Penalty
                        </button>
                    </form>
                    <?php
                    // Backend for Roof Penalty submission
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['roof_penalty_date'], $_POST['roof_penalty_amount'], $_POST['roof_penalty_review'])) {
                        $date = mysqli_real_escape_string($conn, $_POST['roof_penalty_date']);
                        $amount = (int) $_POST['roof_penalty_amount'];
                        $review = mysqli_real_escape_string($conn, $_POST['roof_penalty_review']);

                        if ($date && $amount > 0 && $review) {
                            $insert = "INSERT INTO `baris_penalty` (`OrgID`, `penalty_type`, `penalty_date`, `penalty_amount`, `penalty_review`, `created_date`) VALUES ('$org_id', 'RodentWorkPenalty', '$date', '$amount', '$review', NOW())";
                            if (mysqli_query($conn, $insert)) {
                                header("Location: " . $_SERVER['REQUEST_URI'] . "?roof_success=1");
                                exit();
                            } else {
                                echo '<div class="alert alert-danger mt-2">Error adding penalty. Please try again.</div>';
                            }
                        } else {
                            echo '<div class="alert alert-warning mt-2">Please fill all fields correctly.</div>';
                        }
                    }
                    // Show success message if redirected
                    if (isset($_GET['roof_success']) && $_GET['roof_success'] == 1) {
                        echo '<div class="alert alert-success mt-2">Roof penalty added successfully.</div>';
                    }
                    ?>

                    <?php
                    $query = "SELECT * FROM `baris_penalty` WHERE `OrgID` = $org_id AND penalty_type = 'RodentWorkPenalty' ORDER BY penalty_date DESC";
                    $result = mysqli_query($conn, $query);

                    if (mysqli_num_rows($result) > 0) {
                        ?>
                        <div class="table-responsive mt-4">
                            <table class="table penalty-table">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-calendar me-2"></i>Penalty Date</th>
                                        <th><i class="fas fa-rupee-sign me-2"></i>Amount</th>
                                        <th><i class="fas fa-comment me-2"></i>Review</th>
                                        <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_penalty = 0;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $penalty_date = date('Y-m-d', strtotime($row['created_date']));
                                        $penalty_amount = $row['penalty_amount'];
                                        $penalty_review = $row['penalty_review'];
                                        $total_penalty += $penalty_amount;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-semibold"><?php echo $penalty_date; ?></span>
                                                    <small class="text-muted">
                                                        <?php
                                                        $date1 = new DateTime($penalty_date);
                                                        $date2 = new DateTime();
                                                        $interval = $date1->diff($date2);
                                                        if ($interval->y > 0) {
                                                            echo $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                                                        } elseif ($interval->m > 0) {
                                                            echo $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                                                        } elseif ($interval->d > 0) {
                                                            echo $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                                                        } else {
                                                            echo 'Today';
                                                        }
                                                        ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-danger fs-6">₹<?php echo number_format($penalty_amount); ?></span>
                                            </td>
                                            <td>
                                                <div class="text-start">
                                                    <p class="mb-0"><?php echo htmlspecialchars($penalty_review); ?></p>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <div class="text-end">
                                <span class="total-penalty">
                                    <i class="fas fa-calculator me-2"></i>Total Penalty:
                                    ₹<?php echo number_format($total_penalty); ?>
                                </span>
                            </div>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h5>No Penalties Recorded</h5>
                            <p>No penalties have been imposed for roof of platform shelters yet.</p>
                            <small class="text-muted">Add the first penalty using the form above.</small>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Fourth penalty type -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingFour">
                <button class="accordion-button collapsed penalty-header" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                    <i class="fas fa-toilet penalty-icon"></i>
                    PENALTY IMPOSED BY NGT
                </button>
            </h2>
            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour"
                data-bs-parent="#penaltyAccordion">
                <div class="accordion-body">
                    <!-- Form for NGT Penalty -->
                    <form method="POST" action="" id="ngtPenaltyForm">
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-calendar me-2"></i>Penalty Date
                                </label>
                                <input type="date" class="form-control" name="ngt_penalty_date" required>
                                <div class="invalid-feedback">
                                    Please select a valid date.
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-rupee-sign me-2"></i>Penalty Amount
                                </label>
                                <input type="number" class="form-control" name="ngt_penalty_amount" min="1" required>
                                <div class="invalid-feedback">
                                    Please enter a valid amount.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-comment me-2"></i>Penalty Review
                                </label>
                                <textarea class="form-control" name="ngt_penalty_review" rows="2" required></textarea>
                                <div class="invalid-feedback">
                                    Please provide a review.
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-submit mb-4">
                            <i class="fas fa-plus me-2"></i>Submit Penalty
                        </button>
                    </form>
                    <?php
                    // Backend for NGT Penalty submission
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ngt_penalty_date'], $_POST['ngt_penalty_amount'], $_POST['ngt_penalty_review'])) {
                        $date = mysqli_real_escape_string($conn, $_POST['ngt_penalty_date']);
                        $amount = (int) $_POST['ngt_penalty_amount'];
                        $review = mysqli_real_escape_string($conn, $_POST['ngt_penalty_review']);

                        if ($date && $amount > 0 && $review) {
                            $insert = "INSERT INTO `baris_penalty` (`OrgID`, `penalty_type`, `penalty_date`, `penalty_amount`, `penalty_review`, `created_date`) VALUES ('$org_id', 'NGTPenalty', '$date', '$amount', '$review', NOW())";
                            if (mysqli_query($conn, $insert)) {
                                header("Location: " . $_SERVER['REQUEST_URI'] . "?ngt_success=1");
                                exit();
                            } else {
                                echo '<div class="alert alert-danger mt-2">Error adding penalty. Please try again.</div>';
                            }
                        } else {
                            echo '<div class="alert alert-warning mt-2">Please fill all fields correctly.</div>';
                        }
                    }
                    // Show success message if redirected
                    if (isset($_GET['ngt_success']) && $_GET['ngt_success'] == 1) {
                        echo '<div class="alert alert-success mt-2">NGT penalty added successfully.</div>';
                    }
                    ?>

                    <?php
                    $query = "SELECT * FROM `baris_penalty` WHERE `OrgID` = $org_id AND penalty_type = 'NGTPenalty' ORDER BY penalty_date DESC";
                    $result = mysqli_query($conn, $query);

                    if (mysqli_num_rows($result) > 0) {
                        ?>
                        <div class="table-responsive mt-4">
                            <table class="table penalty-table">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-calendar me-2"></i>Penalty Date</th>
                                        <th><i class="fas fa-rupee-sign me-2"></i>Amount</th>
                                        <th><i class="fas fa-comment me-2"></i>Review</th>
                                        <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_penalty = 0;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $penalty_date = date('Y-m-d', strtotime($row['created_date']));
                                        $penalty_amount = $row['penalty_amount'];
                                        $penalty_review = $row['penalty_review'];
                                        $total_penalty += $penalty_amount;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-semibold"><?php echo $penalty_date; ?></span>
                                                    <small class="text-muted">
                                                        <?php
                                                        $date1 = new DateTime($penalty_date);
                                                        $date2 = new DateTime();
                                                        $interval = $date1->diff($date2);
                                                        if ($interval->y > 0) {
                                                            echo $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                                                        } elseif ($interval->m > 0) {
                                                            echo $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                                                        } elseif ($interval->d > 0) {
                                                            echo $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                                                        } else {
                                                            echo 'Today';
                                                        }
                                                        ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-danger fs-6">₹<?php echo number_format($penalty_amount); ?></span>
                                            </td>
                                            <td>
                                                <div class="text-start">
                                                    <p class="mb-0"><?php echo htmlspecialchars($penalty_review); ?></p>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <div class="text-end">
                                <span class="total-penalty">
                                    <i class="fas fa-calculator me-2"></i>Total Penalty:
                                    ₹<?php echo number_format($total_penalty); ?>
                                </span>
                            </div>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h5>No Penalties Recorded</h5>
                            <p>No penalties have been imposed by NGT yet.</p>
                            <small class="text-muted">Add the first penalty using the form above.</small>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- penalty MISCELLANEOUS -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingMiscellaneous">
                <button class="accordion-button collapsed penalty-header" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseMiscellaneous" aria-expanded="false" aria-controls="collapseMiscellaneous">
                    <i class="fas fa-ellipsis-h penalty-icon"></i>
                    MISCELLANEOUS PENALTIES
                </button>
            </h2>
            <div id="collapseMiscellaneous" class="accordion-collapse collapse" aria-labelledby="headingMiscellaneous"
                data-bs-parent="#penaltyAccordion">
                <div class="accordion-body">
                    <!-- Form for Miscellaneous Penalty -->
                    <form method="POST" action="" id="OtherPenaltyForm">
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-calendar me-2"></i>Penalty Date
                                </label>
                                <input type="date" class="form-control" name="misc_penalty_date" required>
                                <div class="invalid-feedback">Please select a valid date.</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-rupee-sign me-2"></i>Penalty Amount
                                </label>
                                <input type="number" class="form-control" name="misc_penalty_amount" min="1" required>
                                <div class="invalid-feedback">Please enter a valid amount.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-comment-dots me-2"></i>Penalty Review
                                </label>
                                <textarea class="form-control" name="misc_penalty_review" rows="3" required></textarea>
                                <div class="invalid-feedback">Please provide a review.</div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-submit mb-4">
                            <i class="fas fa-plus me-2"></i>Submit Penalty
                        </button>
                    </form>
                    <?php
                    // Backend for Miscellaneous Penalty submission
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['misc_penalty_date'], $_POST['misc_penalty_amount'], $_POST['misc_penalty_review'])) {
                        $date = mysqli_real_escape_string($conn, $_POST['misc_penalty_date']);
                        $amount = (int) $_POST['misc_penalty_amount'];
                        $review = mysqli_real_escape_string($conn, $_POST['misc_penalty_review']);

                        if ($date && $amount > 0 && $review) {
                            $insert = "INSERT INTO `baris_penalty` (`OrgID`, `penalty_type`, `penalty_date`, `penalty_amount`, `penalty_review`, `created_date`) VALUES ('$org_id', 'OtherPenalty', '$date', '$amount', '$review', NOW())";
                            if (mysqli_query($conn, $insert)) {
                                header("Location: " . $_SERVER['REQUEST_URI'] . "?misc_success=1");
                                exit();
                            } else {
                                echo '<div class="alert alert-danger mt-2">Error adding penalty. Please try again.</div>';
                            }
                        } else {
                            echo '<div class="alert alert-warning mt-2">Please fill all fields correctly.</div>';
                        }
                    }
                    // Show success message if redirected
                    if (isset($_GET['misc_success']) && $_GET['misc_success'] == 1) {
                        echo '<div class="alert alert-success mt-2">Miscellaneous penalty added successfully.</div>';
                    }
                    ?>

                    <?php
                    $query = "SELECT * FROM `baris_penalty` WHERE `OrgID` = $org_id AND penalty_type = 'OtherPenalty' ORDER BY penalty_date DESC";
                    $result = mysqli_query($conn, $query);

                    if (mysqli_num_rows($result) > 0) {
                        ?>
                        <div class="table-responsive mt-4">
                            <table class="table penalty-table">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-calendar me-2"></i>Penalty Date</th>
                                        <th><i class="fas fa-rupee-sign me-2"></i>Amount</th>
                                        <th><i class="fas fa-comment-dots me-2"></i>Review</th>
                                        <th><i class="fas fa-cogs me-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total_penalty = 0;
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $penalty_date = date('Y-m-d', strtotime($row['created_date']));
                                        $penalty_amount = $row['penalty_amount'];
                                        $penalty_review = $row['penalty_review'];
                                        $total_penalty += $penalty_amount;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-semibold"><?php echo $penalty_date; ?></span>
                                                    <small class="text-muted">
                                                        <?php
                                                        $date1 = new DateTime($penalty_date);
                                                        $date2 = new DateTime();
                                                        $interval = $date1->diff($date2);
                                                        if ($interval->y > 0) {
                                                            echo $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                                                        } elseif ($interval->m > 0) {
                                                            echo $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                                                        } elseif ($interval->d > 0) {
                                                            echo $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                                                        } else {
                                                            echo 'Today';
                                                        }
                                                        ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger fs-6">₹<?php echo number_format($penalty_amount); ?></span>
                                            </td>
                                            <td>
                                                <div class="text-start">
                                                    <p class="mb-0"><?php echo htmlspecialchars($penalty_review); ?></p>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <div class="text-end">
                                <span class="total-penalty">
                                    <i class="fas fa-calculator me-2"></i>Total Penalty:
                                    ₹<?php echo number_format($total_penalty); ?>
                                </span>
                            </div>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h5>No Miscellaneous Penalties Recorded</h5>
                            <p>No miscellaneous penalties have been imposed yet.</p>
                            <small class="text-muted">Add the first penalty using the form above.</small>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Form validation and submission
                const submitButtons = document.querySelectorAll('.btn-submit');
                submitButtons.forEach(button => submitButtons.forEach(button => {
                    button.addEventListener('click', function (e) {
                        const form = this.closest('form');
                        if (!form) return; // no form found, skip

                        const dateInput = form.querySelector('input[name="penalty_date"]');
                        const amountInput = form.querySelector('input[name="penalty_amount"]');
                        const reviewInput = form.querySelector('textarea[name="penalty_review"]');

                        // Validate fields
                        if (!dateInput.value || !amountInput.value || !reviewInput.value) {
                            e.preventDefault(); // stop submission
                            showToast('Please fill in all fields', 'error');
                            return;
                        }

                        // Show spinner
                        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
                        this.disabled = true;

                        // Allow the form to submit naturally
                        // Do NOT call e.preventDefault() here if validation passed
                    });
                });

                // Add click handlers for action buttons
                document.querySelectorAll('.btn-outline-primary').forEach(btn => {
                    btn.addEventListener('click', function () {
                        showToast('Edit functionality coming soon!', 'info');
                    });
                });

                document.querySelectorAll('.btn-outline-danger').forEach(btn => {
                    btn.addEventListener('click', function () {
                        if (confirm('Are you sure you want to delete this penalty record?')) {
                            const row = this.closest('tr');
                            row.style.opacity = '0';

                            setTimeout(() => {
                                row.remove();
                                showToast('Penalty record deleted', 'success');
                            }, 300);
                        }
                    });
                });

                // Auto-set today's date for date inputs
                const dateInputs = document.querySelectorAll('input[type="date"]');
                const today = new Date().toISOString().split('T')[0];
                dateInputs.forEach(input => {
                    input.setAttribute('max', today);
                });
            });

            // Simple toast notification function
            function showToast(message, type = 'info') {
                // Remove existing toast if any
                const existingToast = document.querySelector('.custom-toast');
                if (existingToast) {
                    existingToast.remove();
                }

                const toast = document.createElement('div');
                toast.className = 'custom-toast';
                toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: 200;
        z-index: 9999;
        max-width: 300px;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;

                // Set background color based on type
                const colors = {
                    'success': '#28a745',
                    'error': '#dc3545',
                    'warning': '#ffc107',
                    'info': '#17a2b8'
                };
                toast.style.background = colors[type] || colors.info;

                // Set icon based on type
                const icons = {
                    'success': 'fa-check-circle',
                    'error': 'fa-exclamation-circle',
                    'warning': 'fa-exclamation-triangle',
                    'info': 'fa-info-circle'
                };
                const icon = icons[type] || icons.info;

                toast.innerHTML = `<i class="fas ${icon} me-2"></i>${message}`;

                document.body.appendChild(toast);

                // Show toast
                setTimeout(() => {
                    toast.style.opacity = '1';
                }, 100);

                // Hide and remove toast after 3 seconds
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.remove();
                        }
                    }, 300);
                }, 3000);
            }
        </script>
</body>

</html>