<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include "../../../connection.php";


          $query = "SELECT 
                bap.paramName AS task, 
                bp.db_pagename AS parameters, 
                bas.db_surveyValue AS Quality_of_done_work,
                bas.auditorname AS auditor_name,
                bs.stationName AS station_name,
                bo.db_Orgname AS organisation_name,
                bd.DivisionName AS division_name,
                bp.db_pageChoice AS grade,
                bp.db_pageChoice2 AS rank1,
                bas.created_date AS report_date
            FROM 
                baris_param bap
                INNER JOIN baris_chemical_report bas ON bap.paramId = bas.db_surveyParamId
                INNER JOIN baris_page bp ON bas.db_surveyPageId = bp.pageId
                INNER JOIN baris_userlogin bu ON bas.db_surveyUserid = bu.userId
                INNER JOIN baris_station bs ON bas.db_surveyStationId = bs.stationId
                INNER JOIN baris_organization bo ON bas.OrgID = bo.OrgID
                INNER JOIN baris_division bd ON bas.DivisionId = bd.DivisionId
            WHERE 
                bas.db_surveyStationId = '19' 
                AND bas.created_date BETWEEN '2025-01-01' AND '2025-01-02'";

            $result = $conn->query($query);
             $data = [];

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $param = $row['parameters'];
                    $task = $row['task'];
                    $unit = $row['rank1'];
                    $quantity = (int)$row['Quality_of_done_work'];
                    $auditor = $row['auditor_name'];
                    $division = $row['division_name'];
                    $station = $row['station_name'];
                    $contractor = $row['organisation_name'];
                    
                    

                    if (!isset($data[$param])) {
                        $data[$param] = [
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
                        $data[$param]['shift1'] += $quantity;
                        $data[$param]['auditor1'] = $auditor;
                    } elseif (strpos(strtolower($task), 'shift 2') !== false) {
                        $data[$param]['shift2'] += $quantity;
                        $data[$param]['auditor2'] = $auditor;
                    } elseif (strpos(strtolower($task), 'shift 3') !== false) {
                        $data[$param]['shift3'] += $quantity;
                        $data[$param]['auditor3'] = $auditor;
                    }
                }
            }
            
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Consumables Report</title>
    <style>
        body {
            width:90%;
            margin:auto;
            font-weight: 800;
            font-size: 12px;
            font-family: 'Roboto';
        }
        h2, h3 {
            text-align: center;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin:20px;
            table-layout: fixed;
            
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 1px;
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
        table th:nth-child(1) { width: 2%; }   
        table th:nth-child(2) { width: 30%; } 
        table th:nth-child(3) { width: 5%; }  
        table th:nth-child(4) { width: 10%; } 
        table th:nth-child(5) { width: 5%; }  
    
    </style>
</head>
<body>
    <h2>SOUTH CENTRAL RAILWAY</h2>
    <h3>Equipment, Consumables & Chemical</h3>
    <p style="text-align: center;">
        Daily uses of type and quantity of consumables of environmental sanitation, mechanized cleaning and housekeeping contract at <?php echo $station  ?> Railway station<br>
        Date: 2025-01-01 &nbsp;&nbsp; Division: <?php echo $division  ?>  &nbsp;&nbsp; Station: <?php echo $station  ?>  
        Name Of Contractor: <?php echo  $contractor  ?> 
    </p>

    <table>
        <tr>
            <th rowspan="2">S.No</th>
            <th rowspan="2">Description Of Material</th>
            <th rowspan="2">Units</th>
            <th colspan="3">Quantity Used</th>
            <th rowspan="2">Total Qty</th>
        </tr>
        <tr>
            <th>Shift 1</th>
            <th>Shift 2</th>
            <th>Shift 3</th>
        </tr>
   
        <?php
  
           

            $sno = 1;
            foreach ($data as $param => $shifts) {
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
                  </tr>
                  <tr>
                    <td colspan='3'>Signature of On DUTY SUPERVISOR</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                  </tr>
                  ";

            if (empty($data)) {
                echo "<tr><td colspan='7'>No Data Available</td></tr>";
            }
            $conn->close();
        ?>
    </table>
    <br>
    <span style="margin-left:20%">Signature of Contractor Representative </span>
    <span style="margin-left:20%">CHI IN Charge</span>
    <br><br>
    <span style="margin-left:20%">----------------------------------------------</span>
    <span style="margin-left:20%">-----------------------------------------------</span>
</body>
</html>. 