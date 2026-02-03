<?php
session_start();
'Station ID: ' . $_SESSION['stationId'];
'OrgID: ' . $_SESSION['OrgID'];
$subqueld = $_GET['id'];
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include "../../connection.php";
$stationId = $_SESSION['stationId'];
$qued = $_SESSION['queId'];
$OrgID = $_SESSION['OrgID'];
$subqueld = $_GET['id'];
// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily performance report target <?php echo $station . $toDate . "To" . $fromDate; ?> </title>
    <?php include "head.php" ?>
    <style>
        .body {
            width: 100%;
            margin: auto;
            font-weight: 800;
            font-size: 11px;
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
                                        <option value="all" <?= $selectedMonth == 'all' ? 'selected' : '' ?>>All Months</option>
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
                                    $subqueId = $_GET['id'];

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
                                                bpg.db_pageChoice2 ,
                                                SUBSTRING(bpg.db_pageChoice2,  INSTR(bpg.db_pageChoice2, '@') + 1) AS machine_no,

                                            SUBSTRING_INDEX(bt.value, ',', 1) AS shift1,
                                            SUBSTRING_INDEX(SUBSTRING_INDEX(bt.value, ',', 2), ',', -1) AS shift2,
                                            SUBSTRING_INDEX(bt.value, ',', -1) AS shift3



                                                    FROM baris_target bt
                                                    INNER JOIN baris_page bpg ON bpg.pageId = bt.pageId
                                                    WHERE bt.OrgID = '$OrgID' AND bt.subqueId = '$subqueId' AND bt.year = '$year' $whereMonth";




                                    $result = $conn->query($sql);

                                    if ($month != 'all') {
                                        echo "<h3>Targets for " . date("F", mktime(0, 0, 0, $month, 1)) . " $year</h3>";
                                    } else {
                                        echo "<h3>Targets for all months of $year</h3>";
                                    }

                                    echo "<table border='1'>";
                                              echo "<tr>
                                                <th>S.No</th>
                                                <th>Description of items</th>
                                                <th>Frequency</th>
                                                <th>% of Weightage</th>
                                                <th>1st Shift</th>
                                                <th>2nd Shift</th>
                                                <th>3rd Shift</th>
                                            </tr>";



                                    if ($result && $result->num_rows > 0) {
                                        $sn = 1;
                                        while ($row = $result->fetch_assoc()) {


                                            echo "<tr>";
                                            echo "<td>$sn</td>";

                                            echo "<td>{$row['Description_Of_Material']}</td>";

                                            echo "<td>{$row['db_pageChoice2']}</td>";
                                            echo "<td>{$row['machine_no']}</td>";
                                            echo "<td>{$row['shift1']}</td>";
                                            echo "<td>{$row['shift2']}</td>";
                                            echo "<td>{$row['shift3']}</td>";
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
                                    $subqueId = $_GET['id'];

                                      $query = "SELECT DISTINCT bsq.subqueId, bpg.pageId, bpg.db_pagename AS Description_Of_Material, bpg.db_pageChoice2,
                                    TRIM(TRAILING '%' FROM SUBSTRING(bpg.db_pageChoice2 , INSTR(bpg.db_pageChoice2, '@') + 1)) AS machine_no

                                    FROM baris_subquestion bsq 
                                    INNER JOIN baris_param bp ON FIND_IN_SET(bp.paramId, bsq.db_paramId)
                                    INNER JOIN baris_page bpg ON FIND_IN_SET(bpg.pageId, bp.db_pagesId)
                                    WHERE bsq.subqueId = '$subqueId'";

                                    $result = $conn->query($query);

                                    if ($result && $result->num_rows > 0) {
                                        echo "<form method='post' action=''>";
                                        echo "<input type='hidden' name='month' value='" . htmlspecialchars($month) . "'>";
                                        echo "<input type='hidden' name='year' value='" . htmlspecialchars($year) . "'>";
                                        echo "<table border='1'>";
                                        echo "<tr><th>S.No</th>   <th>Description of items</th>
                                        <th>Frequency</th>
                                        <th>% of Weightage</th><th>Shift 1</th><th>Shift 2</th><th>Shift 3</th></tr>";
                                        $machineNos = [];

                                        $sn = 1;
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>{$sn}</td>";
                                            echo "<td>" . htmlspecialchars($row['Description_Of_Material']) . "</td>";

                                            echo "<td>" . htmlspecialchars($row['db_pageChoice2']) . "</td>";
                                            echo "<td><input type='text' name='machine_no[]' value='" . htmlspecialchars($row['machine_no']) . "' readonly></td>";


                                            echo "<td><input type='text' name='shift1[]' value='0' maxlength='10' required></td>";
                                            echo "<td><input type='text' name='shift2[]' value='0' maxlength='10' required></td>";
                                            echo "<td><input type='text' name='shift3[]' value='0' maxlength='10' required></td>";

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
                                    $subqueId = $_GET['id'];
                                    $today = date("Y-m-d");
                                    $created = date("Y-m-d H:i:s");

                                    // Safely parse penalty rates from machine_no[]
                                    $penaltyRates = [];
                                    foreach ($_POST['machine_no'] as $raw) {
                                        // Remove non-numeric characters like @, % (if any) from the raw value
                                        $penaltyRaw = preg_replace('/[^\d.]/', '', $raw);


                                        // echo "<p>Debug - Cleaned Penalty: {$penaltyRaw}</p>";

                                        // If the cleaned value is numeric, convert to float, else set to 0.00
                                        if (is_numeric($penaltyRaw)) {
                                            $penaltyRates[] = floatval($penaltyRaw);  // Convert the cleaned value to float
                                        } else {
                                            $penaltyRates[] = 0.00;  // Default to 0.00 if invalid
                                            echo "<p style='color:orange;'>Warning: Invalid penalty in '{$raw}', defaulting to 0.00</p>";
                                        }
                                    }


                                    $shift1 = $_POST['shift1'];
                                    $shift2 = $_POST['shift2'];
                                    $shift3 = $_POST['shift3'];
                                    $pageIds = $_POST['pageId'];

                                    $success = true;

                                    for ($i = 0; $i < count($pageIds); $i++) {
                                        $penalty = $penaltyRates[$i];
                                        $pageId = intval($pageIds[$i]);

                                        $v1 = trim($shift1[$i]);
                                        $v2 = trim($shift2[$i]);
                                        $v3 = trim($shift3[$i]);

                                        $value = "$v1,$v2,$v3";

                                        $query = "INSERT INTO baris_target 
                    (OrgID, queId, subqueId, pageId, value, penalty_rate, month, year, today_date, created_date)
                  VALUES 
                    ('$OrgID', '$qued', '$subqueId', '$pageId', '$value', '$penalty', '$month', '$year', '$today', '$created')";

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