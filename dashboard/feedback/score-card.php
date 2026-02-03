<?php
session_start();
include 'connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// User data
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'];
$station_id = $_SESSION['station_id'];

// Fetch station name from the database
$query = "SELECT name FROM feedback_stations WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $station_name = $row['name'];
} else {
    echo "Station not found";
}

?>

<?php

// Get form_id from URL
$form_id = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;

if ($form_id <= 0) {
    echo "Invalid form ID.";
    exit;
}

// Fetch passenger details
$sql_passenger = "
    SELECT passenger_name, passenger_mobile, platform_no, pnr_number, created_at 
    FROM feedback_form 
    WHERE id = ?";
$stmt_passenger = $conn->prepare($sql_passenger);
$stmt_passenger->bind_param("i", $form_id);
$stmt_passenger->execute();
$result_passenger = $stmt_passenger->get_result();
$passenger = $result_passenger->fetch_assoc();
$stmt_passenger->close();

if (!$passenger) {
    echo "No feedback found.";
    exit;
}

// Fetch questions dynamically filtered by station_id
$sql_questions = "SELECT id, question_text FROM feedback_questions WHERE station_id = ?";
$stmt_questions = $conn->prepare($sql_questions);
$stmt_questions->bind_param("i", $station_id);  // Bind the station_id parameter
$stmt_questions->execute();
$result_questions = $stmt_questions->get_result();

$questions = [];
while ($row = $result_questions->fetch_assoc()) {
    $questions[$row['id']] = $row['question_text'];
}
$stmt_questions->close();


// Fetch passenger feedback ratings
$sql_feedback = "
    SELECT question_id, rating 
    FROM feedback_answers 
    WHERE feedback_form_id = ?";
$stmt_feedback = $conn->prepare($sql_feedback);
$stmt_feedback->bind_param("i", $form_id);
$stmt_feedback->execute();
$result_feedback = $stmt_feedback->get_result();

$ratings = [];
$overall_score = 0;
$rating_count = 0;

while ($row = $result_feedback->fetch_assoc()) {
    $ratings[$row['question_id']] = (int) $row['rating'];
    $overall_score += (int) $row['rating'];
    $rating_count++;
}
$stmt_feedback->close();

// Calculate overall score
$overall_score = ($rating_count > 0) ? $overall_score / $rating_count : 0;

// Function to get badge class
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Feedback Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            margin: 20px;
            font-size: 13px;
        }

        .container {
            max-width: 900px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table-bordered {
            box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.1);
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid black !important;
            text-align: center;
            padding: 8px;
        }

        .header-text {
            font-weight: bold;
            text-decoration: underline;
        }

        h3,
        h4,
        h5 {
            margin-bottom: 10px;
        }

        .signature-table td {
            padding: 10px;
        }

        @media print {
            body {
                margin: 10px;
            }

            .container {
                box-shadow: none;
                background: none;
            }
             .hide-on-print {
            display: none;
        }
        }
    </style>
</head>

<body>
    <div class="container mt-4">
      <!-- Print Button -->
<button class="btn btn-info btn-sm shadow-sm px-4 animate-hover hide-on-print" type="button" onclick="window.print()">Print</button>


        <h3 class="text-center header-text">FEEDBACK FORM FOR HOUSEKEEPING SERVICES</h3>
        <p>Dear passenger,</p>
        <p>Our endeavor is to provide you with the most hygiene on cleaning activities and garbage disposal at YADGIR
            Railway station and Railway colony on an outcome basis.</p>
        <p><strong>Feedback:</strong> Passengers are requested to give feedback regarding services provided by cleaning
            staff, in the forms available with cleaning staff. Based on your feedback, payment to the contractor will be
            made & it will help us to serve you better. Kindly spare minutes and rate the area as given at S No 1 to 5
            in the table below.</p>

        <h5 class="text-center">Location : <?php echo strtoupper($station_name); ?></h5>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>S No</th>
                    <th>Areas of Cleaning/Services</th>
                    <th>Very Good (3)</th>
                    <th>Satisfactory (2)</th>
                    <th>Poor (1)</th>
                    <th>Not Attended (0)</th>
                </tr>
            </thead>
            <tbody>
               <?php
$sr_no = 1;
foreach ($questions as $question_id => $question_text) {
    $rating = $ratings[$question_id] ?? 0; // Get rating or default to 0
    echo "<tr>";
    echo "<td>{$sr_no}</td>";
    echo "<td>{$question_text}</td>";

    // Check the rating and place a checkmark or cross in the appropriate column
    for ($i = 3; $i >= 0; $i--) {
        $symbol = ($rating == $i) ? "✔️" : "❌";
        echo "<td>{$symbol}</td>";
    }

    echo "</tr>";
    $sr_no++;
}
?>

            </tbody>
        </table>
<h4>
    Calculation of Passenger Satisfaction Index (PSI):  <?php echo round(($overall_score / 3) * 100, 2) . '%'; ?>
</h4>
        <p><strong>Very good – 3 | Satisfactory – 2 | Poor – 1 | Not attended – 0</strong></p>

        <table class="table table-bordered signature-table">
            <tr>
                <td><strong>Passenger name:</strong> <?= htmlspecialchars($passenger['passenger_name']) ?></td>
                <td><strong>Date of journey:</strong> <?= date('d/m/Y', strtotime($passenger['created_at'])) ?></td>
            </tr>
            <tr>
                <td><strong>PNR No./UTS Tk. No:</strong> <?= htmlspecialchars($passenger['pnr_number']) ?></td>
                <td><strong>Mobile/Telephone No.:</strong> <?= htmlspecialchars($passenger['passenger_mobile']) ?></td>
            </tr>
            <tr>
                <td><strong>Sign of passenger</strong></td>
                <td><strong>Sign of Contractor’s representative</strong></td>
            </tr>
        </table>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>