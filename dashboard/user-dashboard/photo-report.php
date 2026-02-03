<?php 
session_start(); 
include "../../connection.php"; 
?>
<!doctype html>
<html lang="en">
<?php
include "head.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get filter values
$startDate = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$endDate = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-t');
$station_id = $_SESSION['stationId'];

$period = new DatePeriod(
    new DateTime($startDate),
    new DateInterval('P1D'),
    (new DateTime($endDate))->modify('+1 day')
);
?>
<head>
    <title>Before/After Photo Report</title>
    <style>
        .railway-frame {
            height: 90vh;
            overflow-y: auto;
            font-size: 12px;
            font-weight: 400;
            box-sizing: border-box;
            padding: 0 10px;
        }
        .railway-container {
            width: 100%;
            max-width: 1200px;
            margin: auto;
            page-break-after: always;
            margin-bottom: 20px;
        }
        .railway-section-title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin: 20px 0 10px;
            text-transform: uppercase;
            padding: 10px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
        }
        
        /* Mobile adjustments for container */
        @media screen and (max-width: 768px) {
            .railway-frame {
                padding: 0 5px;
                height: auto;
                overflow-y: visible;
            }
            .railway-container {
                width: 100%;
                margin-bottom: 15px;
            }
            .railway-section-title {
                font-size: 14px;
                padding: 8px;
                margin: 10px 0 5px;
            }
        }
        .photo-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            display: table;
        }
        .photo-table th,
        .photo-table td {
            border: 1px solid #000;
            vertical-align: top;
            padding: 15px;
            text-align: center;
            word-wrap: break-word;
            width: 50%;
            min-height: 350px;
            height: auto;
        }
        .photo-table th {
            background-color: #f2f2f2;
            font-size: 14px;
            font-weight: bold;
            padding: 12px;
            height: 50px;
        }
        .photo-img {
            width: 280px;
            height: 200px;
            object-fit: cover;
            margin-bottom: 5px;
            border: 1px solid #ccc;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .photo-info {
            font-size: 12px;
            text-align: left;
            margin-top: 5px;
            padding: 5px;
            background-color: #f9f9f9;
            border-radius: 3px;
        }
        
        /* Mobile Responsive */
        @media screen and (max-width: 768px) {
            .photo-table {
                display: block;
                width: 100%;
                overflow-x: auto;
                white-space: nowrap;
            }
            .photo-table thead {
                display: none;
            }
            .photo-table tbody {
                display: block;
                width: 100%;
            }
            .photo-table tr {
                display: block;
                margin-bottom: 20px;
                border: 1px solid #000;
                background-color: #fff;
                min-height: 300px;
            }
            .photo-table td {
                display: block;
                width: 100%;
                text-align: left;
                border: none;
                border-bottom: 1px solid #ddd;
                padding: 15px;
                position: relative;
                padding-left: 50%;
                min-height: 280px;
                box-sizing: border-box;
            }
            .photo-table td:before {
                content: attr(data-label);
                position: absolute;
                left: 6px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: bold;
                color: #333;
            }
            .photo-img {
                width: 200px;
                height: 150px;
                object-fit: cover;
            }
            .photo-info {
                font-size: 11px;
                margin-top: 10px;
            }
        }
        
        /* Tablet Responsive */
        @media screen and (min-width: 769px) and (max-width: 1024px) {
            .photo-table td {
                padding: 12px;
                min-height: 320px;
            }
            .photo-table th {
                padding: 10px;
            }
            .photo-img {
                width: 220px;
                height: 160px;
                object-fit: cover;
            }
            .photo-info {
                font-size: 11px;
            }
        }
        
        /* Large Screen */
        @media screen and (min-width: 1025px) {
            .photo-table td {
                padding: 20px;
                min-height: 380px;
            }
            .photo-table th {
                padding: 15px;
            }
            .photo-img {
                width: 300px;
                height: 220px;
                object-fit: cover;
            }
        }
        .railway-filter-form {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin: 15px auto;
            width: fit-content;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .railway-filter-form label {
            font-weight: 500;
            margin-right: 5px;
        }
        .railway-filter-form input[type="date"],
        .railway-filter-form button {
            padding: 8px 12px;
            font-size: 14px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .railway-filter-form button {
            background-color: green;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .railway-filter-form button:hover {
            background-color: darkgreen;
        }
        
        /* Mobile filter form */
        @media screen and (max-width: 768px) {
            .railway-filter-form {
                flex-direction: column;
                align-items: center;
                gap: 10px;
                margin: 10px;
                padding: 10px;
            }
            .railway-filter-form input[type="date"],
            .railway-filter-form button {
                width: 100%;
                max-width: 250px;
                padding: 10px;
                font-size: 16px;
            }
            .railway-filter-form label {
                text-align: center;
                display: block;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <?php include "header.php"; ?>
    <main class="app-main">
        <form class="railway-filter-form" method="GET">
            <label for="from_date">From:</label>
            <input type="date" name="from_date" id="from_date" value="<?= $startDate; ?>">
            <label for="to_date">To:</label>
            <input type="date" name="to_date" id="to_date" value="<?= $endDate; ?>">
            <input type="hidden" name="station_id" value="<?= htmlspecialchars($station_id); ?>">
            <button type="submit">Go</button>
        </form>

        <div style="text-align:center;"><strong>Station:</strong> <?= $_SESSION['stationName']; ?></div>
        <br>

        <div class="railway-frame">
            <?php
            foreach ($period as $date) {
                $currentDate = $date->format("Y-m-d");

                // Fetch before photos
                $beforeQuery = "SELECT * FROM baris_pictures 
                                WHERE DATE(created_date) = '$currentDate' 
                                AND db_surveyStationId = '$station_id' 
                                AND photo_type = 'before' 
                                ORDER BY created_date";
                $beforeResult = $conn->query($beforeQuery);

                // Fetch after photos
                $afterQuery = "SELECT * FROM baris_pictures 
                               WHERE DATE(created_date) = '$currentDate' 
                               AND db_surveyStationId = '$station_id' 
                               AND photo_type = 'after' 
                               ORDER BY created_date";
                $afterResult = $conn->query($afterQuery);

                if (($beforeResult && $beforeResult->num_rows > 0) || ($afterResult && $afterResult->num_rows > 0)) {
                    echo '<div class="railway-container">';
                    echo "<div class='railway-section-title'>Before / After Photo Report - " . date('d-m-Y', strtotime($currentDate)) . "</div>";
                    echo '<table class="photo-table">
                            <tr>
                                <th>Before Photo</th>
                                <th>After Photo</th>
                            </tr>';

                    $maxRows = max($beforeResult->num_rows, $afterResult->num_rows);

                    for ($i = 0; $i < $maxRows; $i++) {
                        $beforeRow = $beforeResult->fetch_assoc();
                        $afterRow = $afterResult->fetch_assoc();

                        echo "<tr>";

                        // Before Photo
                        echo "<td data-label='Before Photo'>";
                        if ($beforeRow) {
                            $beforeImg = !empty($beforeRow['imagename']) ? "../../uploads/photos/" . $beforeRow['imagename'] : "../../uploads/photos/no-image.jpg";
                            echo "<img src='$beforeImg' class='photo-img' alt='Before Photo'>";
                            echo "<div class='photo-info'>";
                            echo "<strong>User ID:</strong> {$beforeRow['db_surveyUserid']}<br>";
                            echo "<strong>Process:</strong> {$beforeRow['db_process_type']}<br>";
                            echo "<strong>Remark:</strong> {$beforeRow['remarks']}<br>";
                            echo "<strong>Time:</strong> " . date('H:i', strtotime($beforeRow['created_date']));
                            echo "</div>";
                        } else {
                            echo "No data";
                        }
                        echo "</td>";

                        // After Photo
                        echo "<td data-label='After Photo'>";
                        if ($afterRow) {
                            $afterImg = !empty($afterRow['imagename']) ? "../../uploads/photos/" . $afterRow['imagename'] : "../../uploads/photos/no-image.jpg";
                            echo "<img src='$afterImg' class='photo-img' alt='After Photo'>";
                            echo "<div class='photo-info'>";
                            echo "<strong>User ID:</strong> {$afterRow['db_surveyUserid']}<br>";
                            echo "<strong>Process:</strong> {$afterRow['db_process_type']}<br>";
                            echo "<strong>Remark:</strong> {$afterRow['remarks']}<br>";
                            echo "<strong>Time:</strong> " . date('H:i', strtotime($afterRow['created_date']));
                            echo "</div>";
                        } else {
                            echo "No data";
                        }
                        echo "</td>";

                        echo "</tr>";
                    }

                    echo "</table>";
                    echo "</div>";
                }
            }
            ?>
        </div>
    </main>
    <footer class="app-footer">
        
        <strong>&copy; 2025</strong> All rights reserved.
    </footer>
</div>
</body>
</html>
