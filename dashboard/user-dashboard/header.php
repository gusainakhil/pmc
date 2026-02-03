<?php

// Check if userId is not set
if (!isset($_SESSION['userId']) || empty($_SESSION['userId'])) {
  // Destroy the session
  session_unset();
  session_destroy();
  header("Location: https://pmc.beatleme.co.in/");
  exit();
}
?>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include "../../connection.php";
?>
<?php
$user_id = $_SESSION['userId'];
$_SESSION['stationId'];
$_SESSION['db_usertype'];
$_SESSION['OrgID'];


$sql = "SELECT 
            baris_userlogin.userId, 
            baris_userlogin.OrgID, 
            baris_userlogin.StationId, 
            baris_station.db_questionsId, 
            baris_question.queId, 
            baris_question.queName, 
            baris_question.subqueId, 
            baris_subquestion.subqueName,
            baris_subquestion.subqueType,
            baris_station.stationName ,
            
            baris_subquestion.subqueId AS report_id
        FROM 
            baris_userlogin 
        JOIN 
            baris_station ON baris_userlogin.OrgID = baris_station.OrgID 
        JOIN 
            baris_question ON baris_station.db_questionsId = baris_question.queId 
        JOIN 
            baris_subquestion ON FIND_IN_SET(baris_subquestion.subqueId, baris_question.subqueId)
        WHERE 
            baris_userlogin.userId = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// $row = $result->fetch_assoc();
// $_SESSION['queId'] = $row ? $row['queId'] : null;


?>

<nav class="app-header navbar navbar-expand bg-body" style="background: #3c8dbc !important;">
  <!--begin::Container-->
  <div class="container-fluid">
    <!--begin::Start Navbar Links-->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-lte-toggle="sidebar" href="#"
          role="button">
          <i class="bi bi-list" style="color:white; font-size:25px;"></i>
        </a>
      </li>
      <li style="float: left;font-size: 25px;color: #fff;font-weight:100; margin-left: 11px;line-height: 50px;" class="nav-item d-none d-md-block">INDIAN RAILWAYS - Station Cleaning</li>

    </ul>
    <!--end::Start Navbar Links-->
    <!--begin::End Navbar Links-->
    <ul class="navbar-nav ms-auto">
      <!--begin::Navbar Search-->


      <li class="nav-item dropdown user-menu">
        <a href="../../logout.php" class="nav-link ">

          <span class="brand-text fw-light" style="color:white">Sign Out</span>
        </a>

      </li>
      <!--end::User Menu Dropdown-->
    </ul>
    <!--end::End Navbar Links-->
  </div>
  <!--end::Container-->
</nav>
<!--end::Header-->
<!--begin::Sidebar-->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <!--begin::Sidebar Brand-->
  <div class="sidebar-brand">
    <!--begin::Brand Link-->
    <a href="./index.php" class="brand-link">
      <!--begin::Brand Image-->
      <img
        src="../assets/img/tarin_logo.png"
        alt="AdminLTE Logo"
        class="brand-image opacity-75 shadow" />
      <!--end::Brand Image-->
      <!--begin::Brand Text-->
      <span class="brand-text fw-light">INDIAN RAILWAY</span>
    </a>

  </div>
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <!--begin::Sidebar Menu-->
      <ul
        class="nav sidebar-menu flex-column"
        data-lte-toggle="treeview"
        role="menu"
        data-accordion="false">
        <li class="nav-item menu-open">
          <a href="#" class="nav-link active">
            <i class="nav-icon bi bi-speedometer"></i>
            <p>
              Dashboard
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>

        </li>
        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon bi bi-box-seam-fill"></i>
            <p>
             Station Cleaning
              <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
          </a>
    
             <ul class="nav nav-treeview">


            <?php
            $first = true;
            while ($row = $result->fetch_assoc()) {
              if ($first) {
                $_SESSION['queId'] = $row['queId'];
                $_SESSION['stationName'] = $row['stationName'];
                $first = false;
              }

             
while ($row = $result->fetch_assoc()) {
    // Safe session key
    $key = str_replace(['/', '.'], '_', $row["subqueType"]);
    $_SESSION[$key] = $row["report_id"];
              

    echo '<li class="nav-item">
        <a href="' . htmlspecialchars($row["subqueType"]) . '.php?id=' . htmlspecialchars($row["report_id"]) . '" class="nav-link"'
        . ($row["subqueType"] === '../feedback/index' ? ' target="_blank"' : '') . '>
            <i class="nav-icon bi bi-circle"></i>
            <p>' . htmlspecialchars($row["subqueName"]) . '</p>
        </a>
      </li>';
}


            }



//     [daily_machine] => 58
//     [manpower_log] => 59
//     [daily_surprise] => 61
//     [daily_performance] => 62
//     [machine_report_id] => 58
// )
///print machine_report_id session variable
            

            ?>

            

            <li class="nav-item">
              <a href="billing-invoice.php" class="nav-link" target="_blank">
                <i class="nav-icon bi bi-circle"></i>
                <p>Billig Invoice </p>
              </a>
            </li>
          </ul>
        </li>
        <!--<li class="nav-item">-->
        <!--  <a href="#" class="nav-link">-->
        <!--    <i class="nav-icon bi bi-circle"></i>-->
        <!--    <p>Passenger Feedback </p>-->
        <!--  </a>-->
        <!--</li>-->

        <!--<li class="nav-item">-->
        <!--  <a href="equipments.php" class="nav-link">-->
        <!--    <i class="nav-icon bi bi-circle"></i>-->
        <!--    <p>equipments</p>-->
        <!--  </a>-->
        <!--</li>-->
        <li class="nav-item">
          <a href="photo-report.php" class="nav-link">
            <i class="nav-icon bi bi-circle"></i>
            <p>Photo Report </p>
          </a>
        </li>
      </ul>
      <!--end::Sidebar Menu-->
    </nav>
  </div>
  <!--end::Sidebar Wrapper-->
</aside>