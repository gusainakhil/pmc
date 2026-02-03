<?php

// Prepare query to fetch station name
$sql = "SELECT name FROM feedback_stations WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $station_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $station_name = $row['name'];
} else {
    $station_name = 'user';
}
?>
<nav class="app-header navbar navbar-expand" style="background-color: #2ea4e9;" data-bs-theme="light">
    <!--begin::Container-->
    <div class="container-fluid">
        <!--begin::Start Navbar Links-->
       <ul class="navbar-nav">
    <li class="nav-item">
        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
            <i class="bi bi-list"></i>
        </a>
    </li>

       <span style="
    color: white;
    font-family: 'Varela Round', sans-serif;
    font-size: 27px;
    font-weight: 600;
    letter-spacing: 0.5px;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-rendering: optimizeLegibility;
">
    Feedback – <?php echo htmlspecialchars($station_name); ?>
</span>


   
</ul>


        <!--end::Start Navbar Links-->
        <!--begin::End Navbar Links-->
        <ul class="navbar-nav ms-auto">

            <!--begin::Notifications Dropdown Menu-->
            <!--<li class="nav-item dropdown">-->
            <!--    <a class="nav-link" data-bs-toggle="dropdown" href="#">-->
            <!--        <i class="bi bi-bell-fill"></i>-->
            <!--        <span class="navbar-badge badge text-bg-warning">5</span>-->
            <!--    </a>-->
            <!--    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">-->
            <!--        <span class="dropdown-item dropdown-header">5 Notifications</span>-->
            <!--        <div class="dropdown-divider"></div>-->
            <!--        <a href="#" class="dropdown-item">-->
            <!--            <i class="bi bi-envelope me-2"></i> 4 new Attendence-->
            <!--            <span class="float-end text-secondary fs-7">3 mins</span>-->
            <!--        </a>-->
            <!--        <div class="dropdown-divider"></div>-->
            <!--        <a href="#" class="dropdown-item">-->
            <!--            <i class="bi bi-people-fill me-2"></i> 1 New update-->
            <!--            <span class="float-end text-secondary fs-7">12 hours</span>-->
            <!--        </a>-->
            <!--        <div class="dropdown-divider"></div>-->
            <!--        <a href="#" class="dropdown-item dropdown-footer"> See All Notifications </a>-->
            <!--    </div>-->
            <!--</li>-->
            <!--end::Notifications Dropdown Menu-->

            <!--begin::Download App Button-->
            <!-- <li class="nav-item">
                <a href="./obhs bandra.apk" class="btn btn-success ms-3 mt-1 shadow" download>
                    <i class="bi bi-cloud-arrow-down me-1"></i> Download App
                </a>
            </li> -->
            <!--end::Download App Button-->

            <!--begin::Fullscreen Toggle-->
            <li class="nav-item">
                <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                    <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                    <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
                </a>
            </li>
            <!--end::Fullscreen Toggle-->



            <!--begin::User Menu Dropdown-->
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <img src="./assets/img/beatle.jpeg" class="user-image rounded-circle shadow" alt="User Image" />
                    <span class="d-none d-md-inline"><?php echo $station_name; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                    <!--begin::User Image-->
                    <li class="user-header text-bg-secondary">
                        <img src="./assets/img/beatle.jpeg" class="rounded-circle shadow" alt="User Image" />
                        <p>
                            <small><?php echo $station_name; ?></small>
                        </p>
                    </li>
                    <!--end::User Image-->



                    <!--begin::Menu Footer-->
                    <li class="user-footer">
                        <a href="./logout.php" class="btn btn-default btn-flat float-end">Sign out</a>
                    </li>
                    <!--end::Menu Footer-->
                </ul>
            </li>
            <!--end::User Menu Dropdown-->


        </ul>

        <!--end::End Navbar Links-->
    </div>
    <!--end::Container-->
</nav>