<?php
session_start();
'Station ID: ' . $_SESSION['stationId'];
'OrgID: ' . $_SESSION['OrgID'];
$machine_report_id = $_SESSION['machine_report_id'];
$qued = $_SESSION['queId'];

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include "../../connection.php";



$stationId = $_SESSION['stationId'];
$OrgID = $_SESSION['OrgID'];


// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Machine Report <?php echo $station . $toDate . "To" . $fromDate; ?> </title>
    <?php include "head.php" ?>
    <style>
        .body {
            width: 90%;
            margin: auto;
            font-weight: 800;
            font-size: 12px;
            font-family: 'Roboto';
        }

        table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            border: 1px solid #ccc;

            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #eaeaea;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 2px;
            box-sizing: border-box;
            text-align: right;
        }

        td:nth-child(1),
        th:nth-child(1) {
            width: 50px;
            text-align: center;
        }

        td:nth-child(4),
        td:nth-child(5) {
            text-align: right;
        }
    </style>
    <style>
        .custom-month-year-form {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            padding: 15px 25px;
            background-color: #f5f9ff;
            border-radius: 12px;
            font-family: 'Segoe UI', sans-serif;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin: 30px auto;
            max-width: fit-content;
        }

        .custom-month-year-form label {
            font-weight: 600;
            color: #333;
        }

        .custom-month-year-form select {
            padding: 6px 12px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background-color: #fff;
            color: #333;
        }

        .custom-month-year-form input[type="submit"] {
            padding: 8px 14px;
            font-size: 14px;
            border: none;
            border-radius: 6px;
            background-color: #17a2b8;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .custom-month-year-form input[type="submit"]:hover {
            background-color: #138496;
        }
    </style>


</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <?php include "header.php" ?>
        <main class="app-main">

            <div class="app-content">
                <div class="container-fluid">
                    <div class="row g-4">
                        <div class="col-md-12">
                            <div class="body">



                                <?php
                                $selectedMonth = isset($_POST['month']) ? $_POST['month'] : '';
                                $selectedYear = isset($_POST['year']) ? $_POST['year'] : date("Y");
                                ?>

                                <form class="custom-month-year-form" method="post" action="">
                                    <label for="month">Select Month:</label>
                                    <select name="month" required>
                                        <option value="all" <?= $selectedMonth == 'all' ? 'selected' : '' ?>>All Months
                                        </option>
                                        <?php
                                        for ($m = 1; $m <= 12; $m++) {
                                            $monthName = date('F', mktime(0, 0, 0, $m, 10));
                                            $selected = ($selectedMonth == $m) ? 'selected' : '';
                                            echo "<option value='$m' $selected>$monthName</option>";
                                        }
                                        ?>
                                    </select>

                                    <label for="year">Select Year:</label>
                                    <select name="year" required>
                                        <?php
                                        for ($y = date("Y") - 1; $y <= date("Y") + 4; $y++) {
                                            $selected = ($selectedYear == $y) ? 'selected' : '';
                                            echo "<option value='$y' $selected>$y</option>";
                                        }
                                        ?>
                                    </select>

                                    <input type="submit" name="show_target" value="Show Target">
                                    <input type="submit" name="create_target" value="Create Target">
                                </form>



                                <?php
                                if (isset($_POST['show_target'])) {
                                    include "../../connection.php";

                                    $OrgID = $_SESSION['OrgID'];
                                    $machine_report_id;

                                    $month = $_POST['month'];
                                    $year = $_POST['year'];

                                    $whereMonth = ($month != 'all') ? "AND bt.month = '$month'" : "";

                                    $sql = "SELECT 
    bt.OrgID, 
    bt.queId,
    bt.pageId,
    bt.year,
    bt.month,
    bt.subqueId,
    bt.penalty_rate,
    bpg.db_pagename AS Description_Of_Material,
    SUBSTRING(bpg.db_pagename, 1, INSTR(bpg.db_pagename, '-') - 1) AS machine_no,

    CASE WHEN SUBSTRING_INDEX(bt.value, ',', 1) = '1' THEN 'Y' ELSE 'NA' END AS shift1,
    CASE WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(bt.value, ',', 2), ',', -1) = '1' THEN 'Y' ELSE 'NA' END AS shift2,
    CASE WHEN SUBSTRING_INDEX(bt.value, ',', -1) = '1' THEN 'Y' ELSE 'NA' END AS shift3

    FROM baris_target bt
    INNER JOIN baris_page bpg ON bpg.pageId = bt.pageId
    WHERE bt.OrgID = '$OrgID' AND bt.subqueId = '$machine_report_id' AND bt.year = '$year' $whereMonth";




                                    $result = $conn->query($sql);

                                    if ($month != 'all') {
                                        echo "<h3>Targets for " . date("F", mktime(0, 0, 0, $month, 1)) . " $year</h3>";
                                    } else {
                                        echo "<h3>Targets for all months of $year</h3>";
                                    }

                                    echo "<table border='1'>";
                                                    echo "<tr>
                        <th>S.No</th>
                        <th>Machine No.</th>
                        <th>Name of Machines</th>
                        <th>Penalty</th>
                        <th>1st Shift</th>
                        <th>2nd Shift</th>
                        <th>3rd Shift</th>
                      </tr>";



                                    if ($result && $result->num_rows > 0) {
                                        $sn = 1;
                                        while ($row = $result->fetch_assoc()) {


                                            echo "<tr>";
                                            echo "<td>$sn</td>";
                                            echo "<td>{$row['machine_no']}</td>";
                                            echo "<td style='text-align:center'>{$row['Description_Of_Material']}</td>";
                                            echo "<td style='text-align:center'>{$row['penalty_rate']}</td>";
                                            echo "<td style='text-align:center'>{$row['shift1']}</td>";
                                            echo "<td style='text-align:center'>{$row['shift2']}</td>";
                                            echo "<td style='text-align:center'>{$row['shift3']}</td>";
                                            echo "</tr>";
                                            $sn++;
                                        }
                                    } else {
                                        echo "<tr><td colspan='5'>No target found for selected month/year.</td></tr>";
                                    }
                                    echo "</table>";
                                }
                                ?>


                                <?php
                                if (isset($_POST['create_target'])) {
                                    include "../../connection.php";

                                    $month = $_POST['month'];
                                    $year = $_POST['year'];
                                    $OrgID = $_SESSION['OrgID'];
                                    $machine_report_id;

                                    $query = "SELECT DISTINCT bsq.subqueId, bpg.pageId, bpg.db_pagename AS Description_Of_Material, 
              SUBSTRING(bpg.db_pagename, 1, INSTR(bpg.db_pagename, '-') - 1) AS machine_no 
              FROM baris_subquestion bsq 
              INNER JOIN baris_param bp ON FIND_IN_SET(bp.paramId, bsq.db_paramId)
              INNER JOIN baris_page bpg ON FIND_IN_SET(bpg.pageId, bp.db_pagesId)
              WHERE bsq.subqueId = '$machine_report_id '";

                                    $result = $conn->query($query);

                                    if ($result && $result->num_rows > 0) {
                                        echo "<form method='post' action=''>";
                                        echo "<input type='hidden' name='month' value='" . htmlspecialchars($month) . "'>";
                                        echo "<input type='hidden' name='year' value='" . htmlspecialchars($year) . "'>";
                                        echo "<table border='1'>";
                                        echo "<tr><th>S.No</th><th>Description</th><th>Machine No.</th><th>Penalty</th><th>Shift 1</th><th>Shift 2</th><th>Shift 3</th></tr>";

                                        $sn = 1;
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>{$sn}</td>";
                                            echo "<td>" . htmlspecialchars($row['Description_Of_Material']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['machine_no']) . "</td>";
                                            echo "<td><input type='number' name='penalty_rate[]' value='0' required></td>";

                                            echo "<td><input type='text' name='shift1[]' value='Y' maxlength='1' required></td>";
                                            echo "<td><input type='text' name='shift2[]' value='Y' maxlength='1' required></td>";
                                            echo "<td><input type='text' name='shift3[]' value='Y' maxlength='1' required></td>";

                                            echo "<input type='hidden' name='pageId[]' value='" . htmlspecialchars($row['pageId']) . "'>";
                                            echo "</tr>";
                                            $sn++;
                                        }

                                        echo "</table>";
                                        echo "<input type='submit' name='submit_target' value='Submit Target'>";
                                        echo "</form>";
                                    } else {
                                        echo "<p style='color:red;'>No records found to create target.</p>";
                                    }
                                }
                                ?>

                                <?php
                                if (isset($_POST['submit_target'])) {
                                    include "../../connection.php";

                                    $month = $_POST['month'];
                                    $year = $_POST['year'];
                                    $OrgID = $_SESSION['OrgID'];
                                    $machine_report_id;
                                    $today = date("Y-m-d");
                                    $created = date("Y-m-d H:i:s");

                                    $penaltyRates = $_POST['penalty_rate'];
                                    $shift1 = $_POST['shift1'];
                                    $shift2 = $_POST['shift2'];
                                    $shift3 = $_POST['shift3'];
                                    $pageIds = $_POST['pageId'];

                                    $success = true;

                                    for ($i = 0; $i < count($pageIds); $i++) {
                                        $penalty = intval($penaltyRates[$i]);
                                        $pageId = intval($pageIds[$i]);

                                        // Get raw Y/N values from form
                                        $s1 = strtoupper(trim($shift1[$i]));
                                        $s2 = strtoupper(trim($shift2[$i]));
                                        $s3 = strtoupper(trim($shift3[$i]));

                                        // Validate input
                                        if (!in_array($s1, ['Y', 'N']) || !in_array($s2, ['Y', 'N']) || !in_array($s3, ['Y', 'N'])) {
                                            echo "<p style='color:red;'>Invalid input at row " . ($i + 1) . ". Only Y or N allowed for shifts.</p>";
                                            $success = false;
                                            continue;
                                        }

                                        // Convert to 1 (Y) or 0 (N)
                                        $v1 = ($s1 === 'Y') ? '1' : '0';
                                        $v2 = ($s2 === 'Y') ? '1' : '0';
                                        $v3 = ($s3 === 'Y') ? '1' : '0';

                                        $value = "$v1,$v2,$v3";

                                        $query = "INSERT INTO baris_target 
                    (OrgID, queId, subqueId, pageId, value, penalty_rate, month, year, today_date, created_date)
                  VALUES 
                    ('$OrgID', '$qued', '$machine_report_id', '$pageId', '$value', '$penalty', '$month', '$year', '$today', '$created')";

                                        if (!$conn->query($query)) {
                                            $success = false;
                                            echo "<p style='color:red;'>Error inserting Page ID $pageId: " . $conn->error . "</p>";
                                        }
                                    }

                                    if ($success) {
                                        $monthName = ($month != 'all') ? date("F", mktime(0, 0, 0, $month, 1)) : "All Months";
                                        echo "<p style='color:green;'>Targets successfully saved for $monthName $year.</p>";
                                    }
                                }
                                ?>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </main>




</body>

</html>