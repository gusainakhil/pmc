<?php
session_start();
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

$startDate = "$selectedYear-$selectedMonth-01";
$endDate = date("Y-m-t", strtotime($startDate));
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$org_id = $_SESSION['OrgID'];

include "../../connection.php";

$query = "SELECT   
    bap.db_pagename AS parameters, 
    (bas.stationName) AS station_name,
    (bo.db_Orgname) AS organisation_name,
    (bd.DivisionName) AS division_name,
    (bap.db_pageChoice2) AS rank1,
    (bas.created_date) AS report_date,
    (bap.pageId) AS page_id ,
    Bt.value As target ,
    SUM(CASE WHEN bp.paramName = 'Shift 1' THEN bcr.db_surveyValue END) AS Shift_1,
    SUM(CASE WHEN bp.paramName = 'Shift 2' THEN bcr.db_surveyValue END) AS Shift_2,
    SUM(CASE WHEN bp.paramName = 'Shift 3' THEN IFNULL(bcr.db_surveyValue,0) END) AS Shift_3
FROM baris_target AS Bt
INNER JOIN baris_chemical_report AS bcr ON Bt.pageId = bcr.db_surveyPageId
INNER JOIN baris_param bp ON bp.paramId = bcr.db_surveyParamId  
INNER JOIN baris_page bap ON bap.pageId = bcr.db_surveyPageId
INNER JOIN baris_station bas ON bas.stationId = bcr.db_surveyStationId
INNER JOIN baris_organization bo ON bo.OrgID = bcr.OrgID
INNER JOIN baris_division bd ON bd.DivisionId = bcr.DivisionId
WHERE Bt.OrgID = $org_id  
    AND Bt.subqueId = bcr.db_surveySubQuestionId 
    AND Bt.created_date BETWEEN '$startDate' AND '$endDate'
    AND bcr.created_date BETWEEN '$startDate' AND '$endDate'
GROUP BY bap.db_pagename  
ORDER BY page_id ASC;";


$result = $conn->query($query);
$data = [];
$station = $division = $contractor = "";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $param = $row['parameters'];
        $target = $row['target'];
        $unit = $row['rank1'];
        $division = $row['division_name'];
        $station = $row['station_name'];
        $contractor = $row['organisation_name'];

        $data[] = [
            'parameters' => $param,
            'unit' => $unit,
            'target' => $target,
            'Shift_1' => $row['Shift_1'] ?? 0,
            'Shift_2' => $row['Shift_2'] ?? 0,
            'Shift_3' => $row['Shift_3'] ?? 0,
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Consumables Report</title>
    <?php include "head.php"; ?>
    <style>
        .body{
            width: 90%;
            /*margin: auto;*/
            font-weight: 800;
            font-size: 12px;
            font-family: 'Roboto';
        }
        h4,
        h5 {
            text-align: center;
            margin: 0;
        }

        table {
      
            border-collapse: collapse;
            margin: 20px;
            table-layout: fixed;
        }
        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 0px;

            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #f2f2f2;
        }

        .signature {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body class="sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <?php include "header.php"; ?>
        <main class="app-main">
            <div class="app-content">
                <div class="container-fluid">
                    <div class="row g-4">
                        <div class="col-md-12">
                            <div class="card card-outline mb-4">
                                <div class="body">
                                    <form method="get" style="text-align: center; margin-top: 20px;">
                                        <label for="month">Select Month:</label>
                                        <select name="month" id="month">
                                            <?php
                                            for ($m = 1; $m <= 12; $m++) {
                                                $monthPadded = str_pad($m, 2, '0', STR_PAD_LEFT);
                                                $selected = ($selectedMonth == $monthPadded) ? "selected" : "";
                                                echo "<option value=\"$monthPadded\" $selected>" . date('F', mktime(0, 0, 0, $m, 10)) . "</option>";
                                            }
                                            ?>
                                        </select>

                                        <label for="year">Select Year:</label>
                                        <select name="year" id="year">
                                            <?php
                                            $currentYear = date("Y");
                                            for ($y = $currentYear; $y >= 2020; $y--) {
                                                $selected = ($selectedYear == $y) ? "selected" : "";
                                                echo "<option value=\"$y\" $selected>$y</option>";
                                            }
                                            ?>
                                        </select>
                                        <input type="submit" value="Generate Report">
                                    </form><br>

                                    <?php if (!empty($data)) { ?>
                                    <h4>SOUTH CENTRAL RAILWAY</h4>
                                    <h5>Equipment, Consumables & Chemical</h5>
                                    <p style="text-align: center;">
                                        Daily uses of type and quantity of consumables of environmental sanitation,
                                        mechanized cleaning and housekeeping contract at
                                        <?php echo htmlspecialchars($station); ?> Railway station<br>
                                        Month: <?php echo date('F Y', strtotime($startDate)); ?> &nbsp;&nbsp; Division:
                                        <?php echo htmlspecialchars($division); ?> &nbsp;&nbsp; Station:
                                        <?php echo htmlspecialchars($station); ?>
                                        Name Of Contractor: <?php echo htmlspecialchars($contractor); ?>
                                    </p>
                                
                                    <table>
                                        <tr>
                                            <th rowspan="2">S.No</th>
                                            <th rowspan="2" style="width:50%;">Description Of Material</th>
                                            <th rowspan="2">Target</th>
                                            <th rowspan="2" style="width: 5%">Units</th>
                                            <th colspan="3">Quantity Used</th>
                                            <th rowspan="2">Total Qty</th>
                                            <th rowspan="2">Difference</th>
                                            <th rowspan="2">Achieved</th>
                                            <th rowspan="2">Deficit</th>
                                        </tr>
                                        <tr>
                                            <th>Shift 1</th>
                                            <th>Shift 2</th>
                                            <th>Shift 3</th>
                                        </tr>
                                
                                        <?php
                                        $sno = 1;
                                        foreach ($data as $row) {
                                            $total = $row['Shift_1'] + $row['Shift_2'] + $row['Shift_3'];
                                            echo "<tr>
                                                <td>{$sno}</td>
                                                <td>{$row['parameters']}</td>
                                                <td>{$row['target']}</td>
                                                <td>{$row['unit']}</td>
                                                <td>{$row['Shift_1']}</td>
                                                <td>{$row['Shift_2']}</td>
                                                <td>{$row['Shift_3']}</td>
                                                <td>{$total}</td>
                                                <td>" . ((int)$row['target'] - (int)$total) . "</td>
                                                <td>" . ((int)$row['target'] != 0 ? min(100, round(((int)$total / (int)$row['target']) * 100, 2)) . "%" : "N/A") . "</td>
                                                <td>" . ((int)$row['target'] != 0 ? round(100 - ((int)$total / (int)$row['target']) * 100, 2) . "%" : "N/A") . "</td>
                                            </tr>";
                                            $sno++;
                                        }
                                        ?>
                                    </table>
                                    <br>
                                    <span style="margin-left: 20%;">Signature of Contractor Representative </span>
                                    <span style="margin-left: 20%;">CHI IN Charge</span>
                                    <br><br>
                                    <span style="margin-left: 20%;">----------------------------------------------</span>
                                    <span style="margin-left: 20%;">-----------------------------------------------</span>
                                <?php } else { ?>
                                    <p style="text-align: center; font-weight: 600; color: red; margin-top: 20px;">
                                        No data available for the selected month and year.
                                    </p>
                                <?php } ?>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include "footer.php"; ?>
    </div>
</body>

</html>