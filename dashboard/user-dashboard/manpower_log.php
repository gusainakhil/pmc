<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include "../../connection.php";


// $fromDate = isset($_GET['fromDate']) ? $_GET['fromDate'] : '';
// $toDate = isset($_GET['toDate']) ? $_GET['toDate'] : '';
 $fromDate = '2025-01-01';
 $toDate ='2025-01-10';

if (empty($fromDate) || empty($toDate)) {
    $fromDate = date('Y-m-d', strtotime('-7 days'));
    $toDate = date('Y-m-d');
}

$query = "SELECT 
                bap.paramName AS task, 
                bp.db_pagename AS parameters, 
               (bas.db_surveyValue) AS Quality_of_done_work,
                bap.paramchoice,
                bas.auditorname AS auditor_name,
                bs.stationName AS station_name,
                bo.db_Orgname AS organisation_name,
                bd.DivisionName AS division_name,
                bas.created_date AS report_date ,
             (bt.value) AS target 
            FROM 
                baris_param bap
                INNER JOIN Manpower_Log_Details bas ON bap.paramId = bas.db_surveyParamId
                INNER JOIN baris_page bp ON bas.db_surveyPageId = bp.pageId
                INNER JOIN baris_station bs ON bas.db_surveyStationId = bs.stationId
                INNER JOIN baris_organization bo ON bas.OrgID = bo.OrgID
                INNER JOIN baris_division bd ON bas.DivisionId = bd.DivisionId
             INNER JOIN baris_target bt ON bas.db_surveySubQuestionId = bt.subqueId
            WHERE 
                bas.db_surveyStationId = '19' 
                AND DATE(bas.created_date) BETWEEN '2025-01-01' AND '2025-01-02'
             AND DATE(bt.created_date) BETWEEN '2025-01-01' AND '2025-01-31' 
            -- GROUP BY 
   -- bap.paramName, bap.paramchoice
              
ORDER BY `task` ASC";

$result = $conn->query($query);

?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="../css/manpower_log.css">
</head>
<body>
    <h2 style="text-align: center;">SOUTH CENTRAL RAILWAY</h2>
    <h3 style="text-align: center;">Manpower Log</h3>
    
    <div style="text-align: center;">
        <p>Date : <strong>2025-03-27</strong> &nbsp;&nbsp; Division : <strong>Guntakal</strong> &nbsp;&nbsp;
            Station : <strong>Tirupati</strong> &nbsp;&nbsp; Total Required Manpower : <strong>91</strong> &nbsp;&nbsp;
            Present for the Day : <strong>91</strong>
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Shift</th>
                <th>Description</th>
                <th>To be provided as per norms</th>
                <th>Provided by contractor <br>(as per bio-metric attendance sheet)</th>
                <th>Found absent during the shift check</th>
                <th>Actual available</th>
                <th>Found without dress code & ID cards</th>
                <th>Found without protective gears</th>
                <th>Signature of On duty CHI</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td rowspan="2">General (Round the clock)</td>
                <td>Project Manager</td>
                <td>1</td>
                <td>1</td>
                <td>0</td>
                <td>1</td>
                <td>0</td>
                <td>0</td>
                <td>CHI Shift 1/2/3 (Y VENUGOPAL RAO)</td>
            </tr>
            <tr>
                <td  class="total">Total: 1</td>
            </tr>

            <tr>
                <td rowspan="2">General (Round the clock)</td>
                <td>Driver</td>
                <td>1</td>
                <td>1</td>
                <td>0</td>
                <td>1</td>
                <td>0</td>
                <td>0</td>
                <td>CHI Shift 1/2/3 (Y VENUGOPAL RAO)</td>
            </tr>
            <tr>
                <td colspan="8" class="total">Total: 1</td>
            </tr>

            <tr>
                <td rowspan="2">General (Round the clock)</td>
                <td>Pest Control</td>
                <td>1</td>
                <td>1</td>
                <td>0</td>
                <td>1</td>
                <td>0</td>
                <td>0</td>
                <td>CHI Shift 1/2/3 (Y VENUGOPAL RAO)</td>
            </tr>
            <tr>
                <td colspan="8" class="total">Total: 1</td>
            </tr>
            <tr>
                <td rowspan="2">Shift 1</td>
                <td>Supervisor</td>
                <td>2</td>
                <td>2</td>
                <td>0</td>
                <td>2</td>
                <td>0</td>
                <td>0</td>
                <td>CHI Shift 1 (Y VENUGOPAL RAO)</td>
            </tr>
            <tr>
                <td>Housekeeper</td>
                <td>46</td>
                <td>46</td>
                <td>0</td>
                <td>46</td>
                <td>0</td>
                <td>0</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="8" class="total">Total: 48</td>
            </tr>
            <tr>
                <td rowspan="2">Shift 2</td>
                <td>Supervisor</td>
                <td>2</td>
                <td>2</td>
                <td>0</td>
                <td>2</td>
                <td>0</td>
                <td>0</td>
                <td>CHI Shift 2 (B MURALIKRISHNA)</td>
            </tr>
            <tr>
                <td>Housekeeper</td>
                <td>28</td>
                <td>28</td>
                <td>0</td>
                <td>28</td>
                <td>0</td>
                <td>0</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="8" class="total">Total: 30</td>
            </tr>
            <tr>
                <td rowspan="2">Shift 3</td>
                <td>Supervisor</td>
                <td>1</td>
                <td>1</td>
                <td>0</td>
                <td>1</td>
                <td>0</td>
                <td>0</td>
                <td>CHI Shift 3 (Y VENUGOPAL RAO)</td>
            </tr>
            <tr>
                <td>Housekeeper</td>
                <td>9</td>
                <td>9</td>
                <td>0</td>
                <td>9</td>
                <td>0</td>
                <td>0</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="8" class="total">Total: 10</td>
            </tr>

            <tr class="grand-total">
                <td colspan="8">Grand Total: 91</td>
            </tr>
        </tbody>
    </table>

    <br>
    <div style="text-align: center;">
        <p>Signature of Contractor Representative ________________________</p>
        <p>CHI IN Charge ________________________</p>
    </div>

</body>
</html>
