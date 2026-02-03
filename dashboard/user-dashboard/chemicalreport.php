<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include "../../connection.php";

// Get the selected dates
$fromDate = isset($_GET['fromDate']) ? $_GET['fromDate'] : '';
$toDate = isset($_GET['toDate']) ? $_GET['toDate'] : '';
$station_id = $_SESSION['stationId'];
// Default date range (last 7 days)
if (empty($fromDate) || empty($toDate)) {
    $fromDate = date('Y-m-d', strtotime('-7 days'));
    $toDate = date('Y-m-d');
}

// Initialize data
$data = [];
$station = '';
$division = '';
$contractor = '';

// Query to fetch data
$query = "SELECT 
                bap.paramName AS task, 
                bp.db_pagename AS parameters, 
                bas.db_surveyValue AS Quality_of_done_work,
                bas.auditorname AS auditor_name,
                bs.stationName AS station_name,
                bo.db_Orgname AS organisation_name,
                bd.DivisionName AS division_name,
                bp.db_pageChoice2 AS rank1,
                bas.created_date AS report_date
            FROM 
                baris_param bap
                INNER JOIN baris_chemical_report bas ON bap.paramId = bas.db_surveyParamId
                INNER JOIN baris_page bp ON bas.db_surveyPageId = bp.pageId
                INNER JOIN baris_station bs ON bas.db_surveyStationId = bs.stationId
                INNER JOIN baris_organization bo ON bas.OrgID = bo.OrgID
                INNER JOIN baris_division bd ON bas.DivisionId = bd.DivisionId
            WHERE 
                bas.db_surveyStationId = '$station_id' 
                AND DATE(bas.created_date) BETWEEN '$fromDate' AND '$toDate'
            ORDER BY bas.created_date ASC";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $param = $row['parameters'];
        $task = $row['task'];
        $unit = $row['rank1'];
        $quantity = (int)$row['Quality_of_done_work'];
        $auditor = $row['auditor_name'];
        $station = $row['station_name'];
        $contractor = $row['organisation_name'];
        $division = $row['division_name'];
        $date = date('Y-m-d', strtotime($row['report_date'])); 

        // Initialize date if not set
        if (!isset($data[$date])) {
            $data[$date] = [];
        }

        // Initialize parameter if not set
        if (!isset($data[$date][$param])) {
            $data[$date][$param] = [
                'unit' => $unit,
                'shift1' => 0,
                'shift2' => 0,
                'shift3' => 0,
                'auditor1' => '',
                'auditor2' => '',
                'auditor3' => ''
            ];
        }

        // Identify shift and assign quantity and auditor name
        if (strpos(strtolower($task), 'shift 1') !== false) {
            $data[$date][$param]['shift1'] += $quantity;
            $data[$date][$param]['auditor1'] = $auditor;
        } elseif (strpos(strtolower($task), 'shift 2') !== false) {
            $data[$date][$param]['shift2'] += $quantity;
            $data[$date][$param]['auditor2'] = $auditor;
        } elseif (strpos(strtolower($task), 'shift 3') !== false) {
            $data[$date][$param]['shift3'] += $quantity;
            $data[$date][$param]['auditor3'] = $auditor;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Daily Consumables reports <?php echo $station .$fromDate ."To".$toDate  ?> </title>
    <style>
        body {
            width: 90%;
            margin: auto;
            font-weight: 800;
            font-size: 12px;
            font-family: 'Roboto';
        }
        h2, h3 {
            text-align: center;
            margin: 0;
        }
        table {
            
            border-collapse: collapse;
           
        }
        table, th, td {
            border: 0.3px solid black;
        }
        th, td {
            padding: 2px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .date-filter {
            text-align: center;
            margin: 20px 0;
        }
        
        
        @media print {
    body {
        margin: 0;
        padding: 0;
        width: 100%;
         position: relative;
    }
    .date-filter {
        display: none;
    }
    table {
        page-break-inside: auto;
        width: 100%;
        margin: 0;
    }
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    h2 {
        page-break-before: always; /* Start a new page before each h3 */
    }
    h2:first-of-type {
        page-break-before: auto; /* Prevent page break before the first h3 */
    }
    .watermark {
        position: fixed;
        top: 50%;
        left: 70%;
        transform: translate(-90%, -90%)rotate(-30deg);
        color: rgba(0, 0, 0, 0.1); /* Light gray, adjust as needed */
        font-size: 80px;          /* Adjust size */
        font-weight: 100rem;
        z-index: -1;
        pointer-events: none;
        white-space: nowrap;
        user-select: none;
        opacity:0.6;
    }
        }


         
    </style>
    <style>
    .custom-date-filter-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 12px 20px;
        background-color: #f0f4f8;
        border-radius: 12px;
        font-family: 'Segoe UI', sans-serif;
        flex-wrap: wrap;
        max-width: fit-content;
        margin: 30px auto;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }

    .custom-date-filter-wrapper label {
        font-weight: 600;
        color: #333;
    }

    .custom-date-filter-wrapper input[type="date"] {
        padding: 6px 12px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 6px;
        min-width: 140px;
        background-color: #fff;
        color: #333;
    }

    .custom-date-filter-wrapper button {
        padding: 8px 16px;
        font-size: 14px;
        border: none;
        border-radius: 6px;
        background-color: #007bff;
        color: white;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .custom-date-filter-wrapper button:hover {
        background-color: #0056b3;
    }
</style>
</head>
<body>
    
    <div class="date-filter">
        <form class="custom-date-filter-wrapper" method="GET">
    <label for="fromDate">From:</label>
    <input type="date" name="fromDate" id="fromDate" value="<?php echo $fromDate; ?>" required>

    <label for="toDate">To:</label>
    <input type="date" name="toDate" id="toDate" value="<?php echo $toDate; ?>" required>

    <button type="submit">Filter</button>
</form>
    </div>
   <div class="watermark" >Beatle Analytics</div>



    <?php
    if (empty($data)) {
        echo "<h4 style='text-align: center;'>No Data Available for Selected Date Range</h4>";
    } else {
        foreach ($data as $date => $report) {
            
             echo"<h2>SOUTH CENTRAL RAILWAY</h2>
    <h3>Equipment, Consumables & Chemical</h3>";
       
            echo "<p style='text-align: center;'>
                Daily uses of type and quantity of consumables of environmental sanitation, mechanized cleaning and housekeeping contract at $station Railway station<br>
                Date: $date &nbsp;&nbsp; Division: $division  &nbsp;&nbsp; Station: $station  
                Name Of Contractor: $contractor
              </p>";
            
            echo "<table>
                <tr>
                    <th rowspan='2'>S.No</th>
                    <th rowspan='2'>Description Of Material</th>
                    <th rowspan='2'>Units</th>
                    <th colspan='3'>Quantity Used</th>
                    <th rowspan='2'>Total Qty</th>
                </tr>
                <tr>
                    <th>Shift 1</th>
                    <th>Shift 2</th>
                    <th>Shift 3</th>
                </tr>";
            
            $sno = 1;
            foreach ($report as $param => $shifts) {
                $total = $shifts['shift1'] + $shifts['shift2'] + $shifts['shift3'];
                echo "<tr>
                        <td>{$sno}</td>
                        <td>{$param}</td>
                        <td>{$shifts['unit']}</td>
                        <td>{$shifts['shift1']}</td>
                        <td>{$shifts['shift2']}</td>
                        <td>{$shifts['shift3']}</td>
                        <td>{$total}</td>
                    </tr>";
                $sno++;
            }
            echo "<tr>
                    <td colspan='3'>Auditor Name</td>
                    <td>{$shifts['auditor1']}</td>
                    <td>{$shifts['auditor2']}</td>
                    <td>{$shifts['auditor3']}</td>
                    <td></td>
                  </tr>";
              echo"<tr>
                    <td colspan='3'>Signature of On DUTY SUPERVISOR</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                  </tr>";
            echo "</table>";
           
                    echo "<br>
              <span style='margin-left:20%'>Signature of Contractor Representative </span>
              <span style='margin-left:20%'>CHI IN Charge</span>
              <br>
              <span style='margin-left:20%'>----------------------------------------------</span>
              <span style='margin-left:20%'>-----------------------------------------------</span>";
        }
    }
    ?>
</body>
</html>
