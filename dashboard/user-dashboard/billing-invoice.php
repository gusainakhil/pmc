<?php 
session_start();
// print php error
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL); 
include "../../connection.php";
include "functions.php";
// session_start();
            if (!isset($_SESSION['OrgID'], $_SESSION['stationId'])) {
    die("Session data missing.");
   
}
$org_id = $_SESSION['OrgID'];   
$station_id = $_SESSION['stationId'];

// Reusable calculation function
function calculatealreportAmount($sactioned_amount, $totalWeight, $monthDate) {
    if ($sactioned_amount <= 0 || $totalWeight <= 0) {
        return 0;
    }
    $weightedAmount = ($sactioned_amount * $totalWeight) / 100;
    $totalDaysInFourYears = 1461;
    $perDayAmount = $weightedAmount / $totalDaysInFourYears;
    $daysInMonth = date('t', strtotime($monthDate));
    return round($perDayAmount * $daysInMonth, 2);
}

// Billing details
$stmt = $conn->prepare("SELECT sactioned_amount, nos_of_worker, security_deposit, mb_no, performance_guarant, agreement_letter_no_dt, cost_of_work_per_day, period_of_contract_from, period_of_contract_to FROM baris_bill_rate WHERE OrgID = ?");
$stmt->bind_param("i", $org_id);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();
if (!$bill) die("Billing record not found.");

// Date range
$currentMonth = date('m');
$currentYear = date('Y');
$selectedMonth = $_GET['month'] ?? $currentMonth;
$selectedYear = $_GET['year'] ?? $currentYear;
$firstDay = date('Y-m-01', strtotime("$selectedYear-$selectedMonth-01"));
$lastDay = date('Y-m-t', strtotime("$selectedYear-$selectedMonth-01"));


// echo $station_id ."<br>";
// Surprise visit score
$sql = "
    SELECT 
        SUM(bas.db_surveyValue) AS total_score,
        COUNT(bas.db_surveyValue) AS total_records,
        brw.weightage
    FROM baris_param bap
    INNER JOIN baris_survey bas ON bap.paramId = bas.db_surveyParamId
    INNER JOIN baris_page bp ON bas.db_surveyPageId = bp.pageId
    INNER JOIN baris_report_weight brw ON bas.db_surveySubQuestionId = brw.subqueId
    WHERE bas.db_surveyStationId = '$station_id'  AND DATE(bas.created_date) BETWEEN '$firstDay' AND '$lastDay'
";
$result = $conn->query($sql);
$data = $result->fetch_assoc();
$overallAverage = $data['total_records'] > 0 ? round(($data['total_score'] / ($data['total_records'] * 10)) * 100, 2) : 0;
$totalWeight = $data['weightage'] ?? 0;
$surpriseVisitAmount = calculatealreportAmount($bill['sactioned_amount'], $totalWeight, $firstDay);

// calculate chemiacl reord important for chemical report
$sql ="SELECT 
    (
        SELECT SUM(Bt.value)
        FROM baris_target AS Bt
        WHERE Bt.OrgID = $org_id
          AND Bt.created_date BETWEEN '$firstDay' AND '$lastDay'
          AND Bt.subqueId IN (
              SELECT DISTINCT bcr.db_surveySubQuestionId
              FROM baris_chemical_report AS bcr
              WHERE bcr.OrgID = $org_id
                AND bcr.created_date BETWEEN '$firstDay' AND '$lastDay'
          )
    ) AS total_target,

    (
        SELECT 
            SUM(bcr.db_surveyValue)
        FROM baris_chemical_report AS bcr
        INNER JOIN baris_report_weight brw 
            ON bcr.db_surveySubQuestionId = brw.subqueId
        WHERE bcr.OrgID = $org_id
          AND bcr.created_date BETWEEN '$firstDay' AND '$lastDay'
    ) AS total_survey_value,

    (
        SELECT brw.weightage
        FROM baris_chemical_report AS bcr
        INNER JOIN baris_report_weight brw 
            ON bcr.db_surveySubQuestionId = brw.subqueId
        WHERE bcr.OrgID = $org_id
          AND bcr.created_date BETWEEN '$firstDay' AND '$lastDay'
        LIMIT 1
    ) AS weightage;";
$result = $conn->query($sql);
$data = $result->fetch_assoc();
$total_target = $data['total_target'] ?? 0;
$total_survey_value = $data['total_survey_value'] ?? 0;

$weightage = $data['weightage'] ?? 0;
$cleanlinessRecordPercentage = $total_target > 0 ? round(($total_survey_value / $total_target) * 100, 2) : 0;
$cleanlinessrecordamount = calculatealreportAmount($bill['sactioned_amount'], $weightage, $firstDay);
// echo "chemical : $cleanlinessRecordPercentage%";

// CLEANLINESS RECORD /performane log


// Sanitize & validate input
function getParam($key, $default = '') {
    return isset($_GET[$key]) ? htmlspecialchars(trim($_GET[$key])) : $default;
}  
$month     = (int) getParam('month', date('n'));
$year      = (int) getParam('year', date('Y'));
// $firstDay = sprintf("%04d-%02d-01", $year, $month);
// $lastDay = date("Y-m-t", strtotime($startDate));
// Auto-fetch subqueId from Daily_Performance_Log
$subqueId = null;
$subque_query = "
    SELECT DISTINCT db_surveySubQuestionId
    FROM Daily_Performance_Log 
    WHERE db_surveyStationId = ?
";
$stmt = $conn->prepare($subque_query);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $subqueId = (int)$row['db_surveySubQuestionId'];
}
$stmt->close();

if (!$subqueId) {
    // die("Subquestion ID not found for the given station and date range.");
}



// Fetch monthly targets
$targets = [];
$t_sql = "
    SELECT pageId,
           SUBSTRING_INDEX(value, ',', 1) AS t1,
           SUBSTRING_INDEX(SUBSTRING_INDEX(value, ',', 2), ',', -1) AS t2,
           SUBSTRING_INDEX(SUBSTRING_INDEX(value, ',', 3), ',', -1) AS t3
    FROM baris_target
    WHERE OrgID = ? AND month = ? AND subqueId = ?
    ORDER BY id DESC
    LIMIT 24
";
$stmt = $conn->prepare($t_sql);
$stmt->bind_param("iii", $org_id, $month, $subqueId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $targets[$row['pageId']] = [(float)$row['t1'], (float)$row['t2'], (float)$row['t3']];
}
$stmt->close();

// Fetch achievements
$score_sql = "
    SELECT dpl.db_surveyPageId, brw.weightage as weiht,
           SUBSTRING(bp2.db_pageChoice2, INSTR(bp2.db_pageChoice2, '@') + 1) AS weightage,
           SUM(CASE WHEN bp1.paramName='Shift 1' THEN dpl.db_surveyValue ELSE 0 END) AS a1,
           SUM(CASE WHEN bp1.paramName='Shift 2' THEN dpl.db_surveyValue ELSE 0 END) AS a2,
           SUM(CASE WHEN bp1.paramName='Shift 3' THEN dpl.db_surveyValue ELSE 0 END) AS a3
    FROM Daily_Performance_Log dpl
    JOIN baris_param bp1 ON dpl.db_surveyParamId = bp1.paramId
    JOIN baris_page bp2 ON dpl.db_surveyPageId = bp2.pageId
    JOIN baris_report_weight brw ON dpl.db_surveySubQuestionId = brw.subqueId

    WHERE dpl.db_surveyStationId = ? AND dpl.created_date BETWEEN ? AND ?
    GROUP BY dpl.db_surveyPageId
";

$stmt = $conn->prepare($score_sql);
$stmt->bind_param("iss", $station_id, $firstDay, $lastDay);
$stmt->execute();
$result = $stmt->get_result();

$total_weightage = 0;
while ($row = $result->fetch_assoc()) {
    $weightagec = $row['weiht'] ?? 0;
   
    $pageId = $row['db_surveyPageId'];
    $target = $targets[$pageId] ?? [0, 0, 0];

    $target_sum = $target[0] + $target[1] + $target[2];
    $achieved_sum = 
        ($target[0] > 0 ? $row['a1'] : 0) +
        ($target[1] > 0 ? $row['a2'] : 0) +
        ($target[2] > 0 ? $row['a3'] : 0);

    $final_score = $target_sum > 0 ? ($achieved_sum / $target_sum) * 100 : 0;
    $weightage = (float)$row['weightage'];
    $weightage_achieved = ($final_score * $weightage) / 100;

    $total_weightage += $weightage_achieved;
}
// $stmt->close();



//  echo $weightagec ."<br>";
//  echo     $weightage ."<br>";


function calculatePerformanceConsumablesAmount($sactioned_amount, $weightagec, $firstDay) {
    if ($sactioned_amount <= 0 || $weightagec <= 0) {
        return 0;
    }
    $weightedAmount = ($sactioned_amount * $weightagec) / 100;
    $totalDaysInFourYears = 1461; // Includes 1 leap year (366 + 365*3)
    $perDayAmount = $weightedAmount / $totalDaysInFourYears;
    $daysInMonth = date('t', strtotime($firstDay)); // Days in the month
    return round($perDayAmount * $daysInMonth, 2);
}
$performanceConsumablesAmount = calculatePerformanceConsumablesAmount($bill['sactioned_amount'], $weightagec, $firstDay);
// echo "Performance Amount: $performanceConsumablesAmount";




    

//calculate MACHINE USAGE

$sql = "SELECT 
    (
        SELECT SUM(CAST(REPLACE(Bt.value, ',', '') AS UNSIGNED))
        FROM baris_target AS Bt
        WHERE Bt.OrgID = $org_id
          AND Bt.created_date BETWEEN '$firstDay' AND '$lastDay'
          AND Bt.subqueId IN (
              SELECT DISTINCT bcr.db_surveySubQuestionId
              FROM baris_machine_report AS bcr
              WHERE bcr.OrgID = $org_id
                AND bcr.created_date BETWEEN '$firstDay' AND '$lastDay'
          )
    ) AS total_target,

    (
        SELECT 
            SUM(bcr.db_surveyValue)
        FROM baris_machine_report AS bcr
        INNER JOIN baris_report_weight brw 
            ON bcr.db_surveySubQuestionId = brw.subqueId
        WHERE bcr.OrgID = $org_id
          AND bcr.created_date BETWEEN '$firstDay' AND '$lastDay'
    ) AS total_survey_value,

    (
        SELECT brw.weightage
        FROM baris_machine_report AS bcr
        INNER JOIN baris_report_weight brw 
            ON bcr.db_surveySubQuestionId = brw.subqueId
        WHERE bcr.OrgID =$org_id
          AND bcr.created_date BETWEEN '$firstDay' AND '$lastDay'
        LIMIT 1
    ) AS weightage;";

$result = $conn->query($sql);
$data = $result->fetch_assoc();
$total_target = $data['total_target'] ?? 0;
$total_survey_value = $data['total_survey_value'] ?? 0;
$weightage = $data['weightage'] ?? 0;
$machineConsumablesPercentage = $total_target > 0 ? round(($total_survey_value / $total_target) * 100, 2) : 0;
$machineConsumablesAmount = calculatealreportAmount($bill['sactioned_amount'], $weightage, $firstDay);
//echo "Machine Consumables Percentage: $machineConsumablesPercentage%";



// calculate ATTENDANCE RECORDS OF THE STAFF 

// $sql="SELECT 
//     (
//         SELECT SUM(CAST(REPLACE(Bt.value, ',', '') AS UNSIGNED))
//         FROM baris_target AS Bt
//         WHERE Bt.OrgID = $org_id
//           AND Bt.created_date BETWEEN '2025-01-01' AND '2025-01-31'
//           AND Bt.subqueId IN (
//               SELECT DISTINCT bcr.db_surveySubQuestionId
//               FROM  Manpower_Log_Details AS bcr
//               WHERE bcr.OrgID = $org_id
//                 AND bcr.created_date BETWEEN '2025-01-01' AND '2025-01-31'
//           )
//     ) AS total_target,

//     (
//         SELECT 
//             SUM(bcr.db_surveyValue)
//         FROM  Manpower_Log_Details AS bcr
//         WHERE bcr.OrgID = $org_id
//           AND bcr.created_date BETWEEN '2025-01-01' AND '2025-01-31'
//     ) AS total_survey_value,

//     (
//         SELECT brw.weightage
//         FROM  Manpower_Log_Details AS bcr
//         INNER JOIN baris_report_weight brw 
//             ON bcr.db_surveySubQuestionId = brw.subqueId
//         WHERE bcr.OrgID = $org_id
//           AND bcr.created_date BETWEEN '2025-01-01' AND '2025-01-31'
//         LIMIT 1
//     ) AS weightage";
// $result = $conn->query($sql);
// $data = $result->fetch_assoc();
// $total_target = $data['total_target'] ?? 0;
// $total_survey_value = $data['total_survey_value'] ?? 0;
// $weightage = $data['weightage'] ?? 0;
// $attendancePercentage = $total_target > 0 ? round(($total_survey_value / $total_target) * 100, 2) : 0;
// $attendanceAmount = calculatealreportAmount($bill['sactioned_amount'], $weightage, $firstDay);
//echo "Attendance Percentage: $attendancePercentage%";

// Earnings & Deductions
// $earnings = [
//     ['ATTENDANCE RECORDS OF THE STAFF', "$weightage%", "$attendancePercentage%", "$attendanceAmount"],
//     ['CLEANLINESS RECORD', "0", "0", "0"],
//     ['USE OF TYPE AND QUANTITY OF CONSUMABLES', "0", "0", "0"],
//     ['MACHINERY USAGE',"$weightage%", "$machineConsumablesPercentage%", "$machineConsumablesAmount"],
//     ['SURPRISE VISITS CONDUCTED BY OFFICIALS OF INDIAN RAILWAYS', "$totalWeight%", "$overallAverage%", "$surpriseVisitAmount"],
//     ['MACHINE CONSUMABLES', "$weightage%", "$machineConsumablesPercentage%", "$machineConsumablesAmount"],
//     ['PASSENGER FEEDBACK AND COMPLAINTS', "0", '0',"0"],
// ];
// this variable store $totalWeight% of ddaily surprise visit

// create condition if $stationId = 33 then earning  section will be hide


///sabarmati 1
if ($station_id == 33) {
   $earnings = [
    ['CLEANLINESS RECORD', "40%", cleanliness_score_station_wise($stationId, $OrgID, $squeld, $month, $year, $conn), "$performanceConsumablesAmount"],
    ['SURPRISE VISITS CONDUCTED BY OFFICIALS OF INDIAN RAILWAYS', "30%", "$overallAverage%", "$surpriseVisitAmount"],
    ['PASSENGER FEEDBACK', "30%", calculate_feedback_psi($conn, $station_id, $firstDay, $lastDay), "0"],
];
} 
//mehsan
elseif ($station_id == 35) {
    
    $earnings = [
        ['CLEANLINESS RECORD', "50%", cleanliness_score_station_wise($stationId, $OrgID, $squeld, $month, $year, $conn), "$performanceConsumablesAmount"],
        ['SURPRISE VISITS CONDUCTED BY OFFICIALS OF INDIAN RAILWAYS', "20%", "$overallAverage%", "$surpriseVisitAmount"],
        ['PASSENGER FEEDBACK', "30%", calculate_feedback_psi($conn, $station_id, $firstDay, $lastDay), "0"],
    ];
}
//BHujs
elseif ($station_id == 44) {
    $earnings = [
        ['CLEANLINESS RECORD', "50%", cleanliness_score_station_wise($stationId, $OrgID, $squeld, $month, $year, $conn), "$performanceConsumablesAmount"],
        ['SURPRISE VISITS CONDUCTED BY OFFICIALS OF INDIAN RAILWAYS', "20%", "$overallAverage%", "$surpriseVisitAmount"],
        ['PASSENGER FEEDBACK', "30%", calculate_feedback_psi($conn, $station_id, $firstDay, $lastDay), "0"],
    ];

}
// new sabarmati
    elseif ($station_id == 53) {
    $earnings = [
     ['CLEANLINESS RECORD', "40%", cleanliness_score_station_wise($stationId, $OrgID, $squeld, $month, $year, $conn), "$performanceConsumablesAmount"],
    ['SURPRISE VISITS CONDUCTED BY OFFICIALS OF INDIAN RAILWAYS', "30%", "$overallAverage%", "$surpriseVisitAmount"],
    ['PASSENGER FEEDBACK', "30%", calculate_feedback_psi($conn, $station_id, $firstDay, $lastDay), "0"],
];
} 



 else {
    $earnings = [
        ['CLEANLINESS RECORD', "$weightage%", "$cleanlinessRecordPercentage%", "$cleanlinessrecordamount"],
        ['SURPRISE VISITS CONDUCTED BY OFFICIALS OF INDIAN RAILWAYS', "$totalWeight%", "$overallAverage%", "$surpriseVisitAmount"],
        ['PASSENGER FEEDBACK AND COMPLAINTS', "0", '0', "0"],
        ['MACHINE USAGE', "$weightage%", "$machineConsumablesPercentage%", "$machineConsumablesAmount"],
        ['PERFORMANCE CONSUMABLES', "$weightagec%", "100", "$performanceConsumablesAmount"],
    ];
}

$earnings_total = array_sum(array_column($earnings, 3));
$earnings_total = round($earnings_total, 2);
// Consolidated score  
$overallAverage = $overallAverage ?: 0;

// Calculate consolidated score as weighted sum of all earnings scores
$consolidated_score = 0;
foreach ($earnings as $row) {
    $weight = floatval(str_replace('%', '', (string)$row[1]));
    $score = floatval(str_replace('%', '', (string)$row[2]));
    $consolidated_score += ($weight * $score) / 100;
}
$consolidated_score = round($consolidated_score, 2) . '%';

$overallAverage = $overallAverage ?: 0;
// The following block is removed to prevent overwriting the $deductions array with an incompatible structure.
// $deductions = [
//     [$penalty_review, $penalty_amount, ],
//     // Add other deductions as needed, e.g.:
//     // ['PASSENGER COMPLAINT', 0, ''],
//     // ...
// ];
// $deductions_total = array_sum(array_column($deductions, 1));
// $total_payable = $earnings_total - $deductions_total;
// $total_payable_rounded = round($total_payable);
// Deductions
// $deductions = [
//     ['PASSENGER COMPLAINT', 0],
//     ['NON REMOVAL OF GARBAGE FROM DUSTBINS', 0],
//     ['OPEN BURNING OF WASTE IN RAILWAYS PREMISES', 0],
//     ['ROOF OF PLATFORM SHELTERS', 0],
//     ['MANPOWER AND UNIFORM PENALTY', 154912],
//     ['PENALTY IMPOSED BY NGT', 0],
//     ['SPOT PENALTY', 0],
//     ['PENALTY IMPOSED DUE TO MACHINE SHORTAGE/ OUT OF ORDER', 0],
//     ['PENALTY IMPOSED DUE TO SHORTAGE OF MACHINE CONSUMABLES', 9600],
//     ['PENALTY DUE TO NON AVAILABILITY OF CHEMICALS', 137850],
//     ['MONITORING EQUIPMENTS PENALTY', 0],
//     ['MISCELLANEOUS', 0],
// ];
// $deductions_total = array_sum(array_column($deductions, 1));
// $total_payable = $earnings_total - $deductions_total;
// $total_payable_rounded = round($total_payable);

function numberToWords($number) {
    return class_exists('NumberFormatter')
        ? strtoupper((new NumberFormatter('en_IN', NumberFormatter::SPELLOUT))->format($number)) . ' RUPEES'
        : "$number RUPEES";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>STATION CLEANING  Bill Invoice</title>
    <link rel="stylesheet" href="assets/css/billing.css">
    <style>
        /* create css for print buttton when we print then automatically hide the form  */
        @media print {
            .filter-form, #print-invoice, #impose-penalty {
                display: none;
            }
            body {
                margin: 0;
                padding: 0;
                font-size: 12px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                border: 1px solid #000;
                padding: 8px;
               
            }
        }
 
      
      </style>
</head>
<body>
<form method="get" class="filter-form" style="display:flex; justify-content:center; align-items:center; gap:16px; margin-bottom:24px; background:#f8f8f8; padding:16px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
    <div>
        <label for="month" style="font-weight:500; margin-right:8px;">Month:</label>
        <select name="month" id="month" style="padding:6px 12px; border-radius:4px; border:1px solid #ccc;">
            <?php for ($m=1; $m<=12; $m++) {
                $val = str_pad($m, 2, '0', STR_PAD_LEFT);
                $sel = ($val == $selectedMonth) ? 'selected' : '';
                echo "<option value=\"$val\" $sel>" . date('F', mktime(0, 0, 0, $m, 1)) . "</option>";
            } ?>
        </select>
    </div>
    <div>
        <label for="year" style="font-weight:500; margin-right:8px;">Year:</label>
        <select name="year" id="year" style="padding:6px 12px; border-radius:4px; border:1px solid #ccc;">
            <?php for ($y=2020; $y<=date('Y')+2; $y++) {
                $sel = ($y == $selectedYear) ? 'selected' : '';
                echo "<option value=\"$y\" $sel>$y</option>";
            } ?>
        </select>
    </div>
    <button type="submit" style="padding:8px 20px; background:#007bff; color:#fff; border:none; border-radius:4px; font-weight:600; cursor:pointer; transition:background 0.2s;">
        Filter
    </button>
    <!-- create impose penalty button  billing-invoice.php--> 
    <button  type="button" id="impose-penalty" style="padding:8px 20px; background:#dc3545; color:#fff; border:none; border-radius:4px; font-weight:600; cursor:pointer; transition:background 0.2s;">
       <a href="impose-penalty.php" style="color: white; text-decoration: none;">Impose Penalty</a>
    </button>
    <!-- print button -->
    <button type="button" id="print-invoice" onclick="printInvoice()" style="padding:8px 20px; background:#28a745; color:#fff; border:none; border-radius:4px; font-weight:600; cursor:pointer; transition:background 0.2s;">
        Print Invoice
    </button>
    <script>
        function printInvoice() {
            window.print();
        }
    </script>

</form>

<!-- <div style="text-align:center;">
    <strong>Selected Date Range:</strong>
    <?= date('d.m.Y', strtotime($firstDay)) ?> TO <?= date('d.m.Y', strtotime($lastDay)) ?>
</div> -->

<div class="container">
    <h3 style="text-align:center;">STATION CLEANING  BILL INVOICE</h3>
    <table>
        <tr><td colspan="2"><strong>PAYABLE AMOUNT DECRIPTION OF SANITATION WORK AT TIRUPATI STATION</strong></td></tr>
        <tr>
            <td>PERIOD OF CONTRACT: <?= date('d.m.Y', strtotime($bill['period_of_contract_from'])) ?> TO <?= date('d.m.Y', strtotime($bill['period_of_contract_to'])) ?></td>
            <td>DATE RANGE: <?= date('d.m.Y', strtotime($firstDay)) ?> TO <?= date('d.m.Y', strtotime($lastDay)) ?></td>
        </tr>
        <tr><td>SANCTIONED AMOUNT: <?= $bill['sactioned_amount'] ?></td><td>SECURITY DEPOSIT: <?= $bill['security_deposit'] ?></td></tr>
        <tr><td>NOS OF WORKERS: <?= $bill['nos_of_worker'] ?></td><td>PERFORMANCE GUARANTY: <?= $bill['performance_guarant'] ?></td></tr>
        <tr><td>M.B. NO: <?= $bill['mb_no'] ?></td><td>COST OF WORK PER MONTH: <?= $bill['cost_of_work_per_day'] ?></td></tr>
        <tr><td>AGREEMENT LETTER NO &DT: <?= $bill['agreement_letter_no_dt'] ?></td><td>NUMBER OF DAYS FOR BILLING: <?= date('t', strtotime($firstDay)) ?></td></tr>
        <tr><td colspan="2">ACCEPTANCE LETTER NO &DT: <?= $bill['agreement_letter_no_dt'] ?></td></tr>
    </table>

    <div class="section"><strong>EARNINGS</strong></div>
    <table>
        <tr><th style="text-align: center;">S.NO</th><th style="text-align: center;">EARNINGS</th><th style="text-align: center;">WEIGHTAGE</th><th style="text-align: center;">SCORED</th><th style="text-align: center;">AMOUNT</th></tr>
        <?php foreach ($earnings as $i => $row): ?>
            <tr>
                <td style="text-align: center;"><?= $i+1 ?></td>
                <td><?= htmlspecialchars($row[0]) ?></td>
                <td style="text-align: center;"><?= $row[1] ?></td>
                <td style="text-align: center;"><?= $row[2] ?></td>
                <td style="text-align: center;"><?= number_format($row[3], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr><td></td><td>CONSOLIDATED PERFORMANCE SCORE</td><td colspan="2" style="text-align: center;"><?= $consolidated_score ?></td><td></td></tr>
        <tr><th style="text-align: center;" colspan="4">TOTAL</th><th style="text-align: center;"><?= number_format($earnings_total, 2) ?></th></tr>
        <!-- <tr>
            <td colspan="5" style="background:#f9f9f9; font-weight:500;">
            Surprise Visit Amount is calculated for the selected month only (not daily).
            </td>
        </tr> -->
    </table>

    <?php
    // Map penalty types to display names
    $penalty_display_names = [
        'NonremovalGarbagePenalty' => strtoupper('Non Removal Of Garbage from Dustbins'),
        'OpenBurningPenalty' => strtoupper('Open Burning of Waste in Railways Premises'),
        'RodentWorkPenalty' => strtoupper('Roof of platform shelters'),
        'SpotPenalty' => strtoupper('SPOT PENALTY'),
        'NGTPenalty' => strtoupper('Penalty Imposed by NGT'),
        'OtherPenalty' => strtoupper('MISCELLANEOUS')
    ];

    $all_penalty_types = array_keys($penalty_display_names);

    // Fetch all penalties for the selected period and org
    $sql = "SELECT penalty_amount, penalty_review, penalty_type FROM `baris_penalty` WHERE created_date BETWEEN '$firstDay' AND '$lastDay' AND OrgID = $org_id";
    $result = $conn->query($sql);

    $deductions = [];
    $deductions_total = 0;
    $penalty_sums = array_fill_keys($all_penalty_types, 0);

    if ($result && $result->num_rows > 0) {
        $sn = 1;
        while ($row = $result->fetch_assoc()) {
            $amount = $row['penalty_amount'] ?? 0;
            $review = $row['penalty_review'] ?? '';
            $type = $row['penalty_type'] ?? 'OtherPenalty';
            $deductions[] = [
                'sn' => $sn++,
                'amount' => $amount,
                'deduction' => $review,
                'type' => $type
            ];
            $deductions_total += $amount;
            if (isset($penalty_sums[$type])) {
                $penalty_sums[$type] += $amount;
            } else {
                // If type not found, add to OtherPenalty
                $penalty_sums['OtherPenalty'] += $amount;
            }
        }
    } else {
        // No penalties found, add a default row
        $deductions[] = [
            'sn' => 1,
            'amount' => 0,
            'deduction' => '',
            'type' => 'None'
        ];
        $deductions_total = 0;
    }

    $total_payable = $earnings_total - $deductions_total;
    $total_payable_rounded = round($total_payable);
    ?>

    <div class="section"><strong>DEDUCTIONS SUMMARY</strong></div>
    <table>
        <tr>
            <th>S.NO</th>
            <th>Penalty Name</th>
            <th>Total Amount</th>
        </tr>
        <?php
        $sn = 1;
        foreach ($all_penalty_types as $type):
        ?>
            <tr>
                <td style="text-align: center;"><?= $sn++ ?></td>
                <td><?= htmlspecialchars($penalty_display_names[$type]) ?></td>
                <td style="text-align: center;"><?= number_format($penalty_sums[$type], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <th colspan="2">GRAND TOTAL</th>
            <th style="text-align: center;"><?= number_format(array_sum($penalty_sums), 2) ?></th>
        </tr>
    </table>

    <div class="summary">
        TOTAL PAYABLE AMOUNT: <?= number_format($total_payable, 2) ?><br>
        ROUND OFF PAYABLE AMOUNT: <strong><?= $total_payable_rounded ?></strong><br>
        IN WORDS: <?= numberToWords($total_payable_rounded) ?>
    </div>
    <div class="footer">
        THIS IS A COMPUTER GENERATED INVOICE AND NO SIGNATURE IS REQUIRED.
    </div>
</div>
</body>
</html>
