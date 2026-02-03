<?php
session_start();
 'Station ID: ' . $_SESSION['stationId'];
'OrgID: ' . $_SESSION['OrgID'];
$subqueId = $_SESSION['id'];
$qued =$_SESSION['queId'];
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include "../../connection.php";

$query = "select DISTINCT  bsq.subqueId,bpg.pageId, bpg.db_pagename as Description_Of_Material , bpg.db_pagechoice2 as units from baris_subquestion bsq
INNER JOIN baris_param bp ON FIND_IN_SET(bp.paramId, bsq.db_paramId)
INNER JOIN baris_page bpg ON FIND_IN_SET(bpg.pageId, bp.db_pagesId)

where
1= 1 and subqueId = '$subqueId';";

$result = $conn->query($query);
$stationId =$_SESSION['stationId'];
$OrgID= $_SESSION['OrgID'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Machine Report <?php echo $station .$toDate."To".$fromDate  ;?> </title>
    <?php include "head.php"; ?>
      <style>
    .body {
       width: 90%;
            margin: auto;
           
            font-size: 12px;
            font-family: 'Roboto';
    }

    table {
      border-collapse: collapse;
      width: 100%;
      background: #fff;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    th, td {
      border: 1px solid #ccc;
      
      text-align: left;
    }

    th {
      background-color: #eaeaea;
      font-weight: bold;
    }

    input[type="text"], input[type="number"] {
      width: 100%;
      padding: 2px;
      box-sizing: border-box;
      text-align: right;
    }

    td:nth-child(1), th:nth-child(1) {
      width: 50px;
      text-align: center;
    }

    td:nth-child(4), td:nth-child(5) {
      text-align: right;
    }
  </style>x
   <style>
    .target-form-inline {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 15px 20px;
        background-color: #f1f1f1;
        border-radius: 10px;
       
        flex-wrap: wrap; /* Mobile responsive */
        margin: 20px auto;
        max-width: fit-content;
    }

    .target-form-inline label {
        margin-right: 5px;
        font-weight: 500;
    }

    .target-form-inline select,
    .target-form-inline input[type="submit"] {
        padding: 6px 10px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    .target-form-inline input[type="submit"] {
        background-color: #007bff;
        color: white;
        border: none;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .target-form-inline input[type="submit"]:hover {
        background-color: #0056b3;
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
     
                                    <?php
                                    $selectedMonth = isset($_POST['month']) ? $_POST['month'] : '';
                                    $selectedYear = isset($_POST['year']) ? $_POST['year'] : date("Y");
                                    ?>
                                    
                                    <form class="target-form-inline" method="post" action="">
                                        <label for="month">Month:</label>
                                        <select name="month" required>
                                            <option value="all" <?= $selectedMonth == 'all' ? 'selected' : '' ?>>Select</option>
                                            <?php 
                                            for ($m = 1; $m <= 12; $m++) {
                                                $monthName = date('F', mktime(0, 0, 0, $m, 10));
                                                $selected = ($selectedMonth == $m) ? 'selected' : '';
                                                echo "<option value='$m' $selected>$monthName</option>";
                                            }
                                            ?>
                                        </select>
                                    
                                        <label for="year">Year:</label>
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
                                        $subqueId ;
                                    
                                      $month = $_POST['month'];
                                    $year = $_POST['year'];
                                    
                                    $whereMonth = ($month != 'all') ? "AND bt.month = '$month'" : "";
                                    
                                    $sql = "SELECT bt.*, bpg.db_pagename AS Description_Of_Material, bpg.db_pagechoice2 AS units 
                                            FROM baris_target bt
                                            INNER JOIN baris_page bpg ON bpg.pageId = bt.pageId
                                            WHERE bt.OrgID = '$OrgID' AND bt.subqueId = '$subqueId' AND bt.year = '$year' $whereMonth";
                                    
                                        $result = $conn->query($sql);
                                    
                                        echo "<h3>Targets for " . date("F", mktime(0,0,0,$month,1)) . " $year</h3>";
                                        echo "<table border='1'>";
                                        echo "<tr><th style='width:3%'>S.No</th><th style='width:40%'>Description</th><th style='width:5%'>Units</th><th style='width:5%'>Penalty</th><th style='width:5%'>Target</th></tr>";
                                    
                                        if ($result && $result->num_rows > 0) {
                                            $sn = 1;
                                            while ($row = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>$sn</td>";
                                                echo "<td>{$row['Description_Of_Material']}</td>";
                                                echo "<td style='text-align:center'>{$row['units']}</td>";
                                                echo "<td style='text-align:center'>{$row['penalty_rate']}</td>";
                                                echo "<td  style='text-align:center' >{$row['value']}</td>";
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
                                        $subqueId ;
                                    
                                        $query = "SELECT DISTINCT bsq.subqueId, bpg.pageId, bpg.db_pagename AS Description_Of_Material, bpg.db_pagechoice2 AS units 
                                                  FROM baris_subquestion bsq 
                                                  INNER JOIN baris_param bp ON FIND_IN_SET(bp.paramId, bsq.db_paramId)
                                                  INNER JOIN baris_page bpg ON FIND_IN_SET(bpg.pageId, bp.db_pagesId)
                                                  WHERE bsq.subqueId = '$subqueId'";
                                    
                                        $result = $conn->query($query);
                                    
                                        echo "<form method='post' action=''>";
                                        echo "<input type='hidden' name='month' value='$month'>";
                                        echo "<input type='hidden' name='year' value='$year'>";
                                        echo "<table border='1'>";
                                        echo "<tr><th>S.No</th><th>Description</th><th>Units</th><th>Penalty</th><th>Monthly Target</th></tr>";
                                    
                                        $sn = 1;
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>$sn</td>";
                                            echo "<td>{$row['Description_Of_Material']}</td>";
                                            echo "<td>{$row['units']}</td>";
                                            echo "<td><input type='number' name='penalty_rate[]' value='0' required></td>";
                                            echo "<td><input type='number' name='monthly_target[]' value='0' required></td>";
                                            echo "<input type='hidden' name='pageId[]' value='{$row['pageId']}'>";
                                            echo "</tr>";
                                            $sn++;
                                        }
                                    
                                        echo "</table>";
                                        echo "<br><input type='submit' name='submit_target' value='Submit Target'>";
                                        echo "</form>";
                                    }
                                    ?>

                                    
                                    <?php
                                    if (isset($_POST['submit_target'])) {
                                        include "../../connection.php";
                                    
                                        $month = $_POST['month'];
                                        $year = $_POST['year'];
                                        $OrgID = $_SESSION['OrgID'];
                                        $subqueId ;
                                        $today = date("Y-m-d");
                                        $created = date("Y-m-d H:i:s");
                                    
                                        $penaltyRates = $_POST['penalty_rate'];
                                        $monthlyTargets = $_POST['monthly_target'];
                                        $pageIds = $_POST['pageId'];
                                    
                                        for ($i = 0; $i < count($pageIds); $i++) {
                                            $penalty = $penaltyRates[$i];
                                            $target = $monthlyTargets[$i];
                                            $pageId = $pageIds[$i];
                                    
                                            $query = "INSERT INTO baris_target (OrgID, queId, subqueId, pageId, value, penalty_rate, month, year, today_date, created_date)
                                                      VALUES ('$OrgID', '$qued', '$subqueId', '$pageId', '$target', '$penalty', '$month', '$year', '$today', '$created')";
                                    
                                            $conn->query($query);
                                        }
                                    
                                        echo "<h5 style='color:green;'>Target successfully saved for " . date("F", mktime(0,0,0,$month,1)) . " $year.</h5>";
                                    }
                                    ?>


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