<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include "../../connection.php";
$sql = "
    SELECT 
        bap.paramName AS task, 
        bp.db_pagename AS parameters, 
        bas.db_surveyValue AS Quality_of_done_work,
        bu.db_username AS name,
        bs.stationName AS station_name,
        bo.db_Orgname AS organisation_name,
        bd.DivisionName AS division_name,
        CASE 
            WHEN bas.db_surveyValue IN (9, 10) THEN 'Excellent'
            WHEN bas.db_surveyValue IN (7, 8) THEN 'Very Good'
            WHEN bas.db_surveyValue IN (5, 6) THEN 'Good'
            WHEN bas.db_surveyValue IN (3, 4) THEN 'Average'
            WHEN bas.db_surveyValue IN (1, 2) THEN 'Poor'
            ELSE 'Not Applicable'
        END AS payable_grade,
        bp.db_pageChoice AS grade,
        bp.db_pageChoice2 AS rank1,
        bas.created_date AS report_date
    FROM 
        baris_param bap
        INNER JOIN baris_survey bas ON bap.paramId = bas.db_surveyParamId
        INNER JOIN baris_page bp ON bas.db_surveyPageId = bp.pageId
        INNER JOIN baris_userlogin bu ON bas.db_surveyUserid = bu.userId
        INNER JOIN baris_station bs ON bas.db_surveyStationId = bs.stationId
        INNER JOIN baris_organization bo ON bas.OrgID = bo.OrgID
        INNER JOIN baris_division bd ON bas.DivisionId = bd.DivisionId
    WHERE 
        bas.db_surveyStationId = '19' 
        AND bas.created_date >= '2025-01-01' 
        AND bas.created_date < '2025-01-02';
";
$result = $conn->query($sql);
$date = '';
$auditor = '';
$division = '';
$station = '';
$contractor = '';
$overallAverage = 0;
$totalScore = 0;
$totalRecords = 0;
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $date = $row['report_date'];
        $auditor = $row['name'];
        $division = $row['division_name'];
        $station = $row['station_name'];
        $contractor = $row['organisation_name'];
        $totalScore += $row['Quality_of_done_work'];
        $totalRecords++;
    }
    $overallAverage = $totalRecords > 0 ? round(($totalScore / ($totalRecords * 10)) * 100, 2) : 0;
} else {
    echo "<p>No records found</p>";
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Survey Report</title>
    <style>
        body {
            font-weight: 100;
            font-size: 12px;
            font-family: 'Roboto';
        }
        .container {
            width: 80%;
            margin: auto;
        }
        .report-title {
            text-align: center;
            font-weight: bold;
            margin-top: 20px;
        }
        .report-subtitle {
            text-align: center;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
        }
        .report-subtitle span {
            font-weight: bold;
            color: #333;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid black;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .section-title {
            text-align: center;
            font-weight: bold;
            padding: 5px;
            background-color: #e0e0e0;
        }
        table th:nth-child(1) { width: 5%; }   
        table th:nth-child(2) { width: 35%; } 
        table th:nth-child(3) { width: 15%; }  
        table th:nth-child(4) { width: 10%; } 
        table th:nth-child(5) { width: 10%; }  
        table th:nth-child(6) { width: 10%; }
    </style>
</head>
<body>
    <div class='container'>
        <h2 class="report-title">SOUTH CENTRAL RAILWAY</h2>
        <h4 class="report-subtitle">Daily Surprise Visit</h4>
        <p class="report-subtitle">
            <span>Date:</span> <span><?php echo $date; ?></span> &nbsp;|&nbsp;
            <span>Auditor:</span> <span><?php echo $auditor; ?></span> &nbsp;|&nbsp;
            <span>Division:</span> <span><?php echo $division; ?></span> &nbsp;|&nbsp;
            <span>Station:</span> <span><?php echo $station; ?></span><br>
            <span>Name Of Contractor:</span> <span><?php echo $contractor; ?></span> &nbsp;|&nbsp;
            <span>Overall Average:</span> <span><?php echo $overallAverage; ?>%</span> &nbsp;|&nbsp;
            <span>Total Score Obtained:</span> <span><?php echo $totalScore; ?></span>
        </p>

        <?php
        $currentTask = '';
        $result->data_seek(0); 
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if ($currentTask != $row['task']) {
                    if ($currentTask != '') {
                        echo "</table>";
                    }
                    $currentTask = $row['task'];
                    echo "<h4 class='section-title'>$currentTask</h4>";
                    echo "
                    <table>
                        <tr>
                            <th>S.No</th>
                            <th>Parameters</th>
                            <th>Range</th>
                            <th>Grade</th>
                            <th>Quality of Work Done</th>
                            <th>Payable Grade</th>
                        </tr>";
                    $serialNo = 1;
                }
                echo "<tr>
                    <td>" . $serialNo++ . "</td>
                    <td>" . $row['parameters'] . "</td>
                    <td>";
               $values = explode(',', $row['rank1']); 
                    foreach ($values as $value) {
                        
                        $subValues = preg_split('/[\/,]+/', $value);
                        foreach ($subValues as $subValue) {
                            $subValue = trim($subValue); 
                            if (!empty($subValue)) { 
                                echo $subValue . '<br>'; 
                            }
                        }
                    }
                 echo "</td><td>";
                $grades = explode(',', $row['grade']); 
            foreach ($grades as $grade) {
               
                $subGrades = preg_split('/\s+|,/', $grade);
                foreach ($subGrades as $subGrade) {
                    echo $subGrade . '<br>'; 
                }
            }

                echo "</td>
                    <td>" . $row['Quality_of_done_work'] . "</td>
                    <td>" . $row['payable_grade'] . "</td>
                </tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No records found</p>";
        }
        ?>
    </div>
</body>
</html>