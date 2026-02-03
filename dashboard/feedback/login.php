<?php
session_start(); // Start a session
include 'connection.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the input data from the form
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Prepare and execute query to fetch user by username
    $stmt = $conn->prepare("SELECT id, username, password_hash, station_id FROM feedback_users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc();

        // Verify the hashed password
        if (password_verify($pass, $userData['password_hash'])) {
            // Start session and store user data
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['stationId'] = $userData['station_id'];

            // Redirect to index.php
            header("Location: index.php");
            exit();
        } else {
            // Invalid password
            $error = "Invalid username or password";
        }
    } else {
        // User not found
        $error = "Invalid username or password";
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="rankz-verification" content="RxWuHooLOzDvCWCl">
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- favicon -->
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/frontend/img/favicon.html">

    <!--  CSS Libraries -->
    <link rel="stylesheet" href="../../assets/frontend/css/plugins.css">
    <link rel="stylesheet" href="../../assets/frontend/css/style.css">
    <link rel="stylesheet" href="../../assets/frontend/css/responsive.css">
    <link rel="stylesheet" href="../cdn.jsdelivr.net/gh/t4t5/sweetalert%40v0.2.0/lib/sweet-alert.css">
    <link rel="stylesheet" href="../cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.css" />
    <script data-ad-client="ca-pub-2299349886568986" async
        src="../pagead2.googlesyndication.com/pagead/js/f.txt"></script>
    <link rel="stylesheet" href="../use.fontawesome.com/releases/v5.5.0/css/all.css"
        integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3BlXeVIU" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="../cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css">
    <title>Beatle station cleaning</title>
    <meta property="og:title" content="Custom Software Development for Railway Industry">
    <meta property="og:description" content="Our company specializes in custom software development for the railway industry, delivering reliable and high-quality solutions tailored to your business needs.">
    <meta property="og:image" content="https://www.beatleanalytics.com/assets/frontend/img/logo-white.png">
    <meta property="og:url" content="https://pmc.beatleme.co.in/index.php">
    <meta name="twitter:card" content="summary_large_image">
</head>

<body>
    <style>
        .error {
            font-size: 12px !important;
            color: red !important;
        }
    </style>
    <div class="alert_view" style="display: none;">
        <button id="alert_btn">Alert</button>
        <button id="success_btn">success</button>
    </div>
    <!-- ====== Go to top ====== -->
    <a id="toTop" title="Go to top" href="javascript:void(0)">
        <span id="toTopHover"></span>TOP
    </a>
    <!-- Preloader start-->
    <div class="preloader">
        <div class="loader-inner">
            <img class="lodar-img" src="../../assets/frontend/img/loding.gif" alt="lodar">
        </div>
    </div>
    <!-- Preloader end -->
    <!-- Header start-->
    <header class="header">
        <div class="head-top head-top-one d-none d-lg-block">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-3">
                        <div class="socials socials-header text-lg-left text-center">
                            <a class="facebook" href="https://www.facebook.com/beatleanalytics/" target="_blank">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a class="linkedin" href="https://in.linkedin.com/company/beatle-analytics" target="_blank">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a class="blogger" href="https://beatleanalytics.wordpress.com/" target="_blank">
                                <i class="fab fa-blogger"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-9">
                        <ul class="head-contact text-lg-right text-center">
                            <li>
                                <a href="tel:+91 8000221818" class="" target="_blank">
                                    <i class="fa fa-phone"></i> +91 80002 21818
                                </a>
                            </li>
                            <li class="email-id">
                                <a href="mailto:info@beatleanalytics.com" class="" target="_blank">
                                    <i class="fa fa-envelope"></i>
                                    info@beatleanalytics.com
                                </a>
                            </li>
                            <li class="login-sign" id="intro_login">
                                <a href="">
                                    <i class="fa fa-user"></i> login
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="theme-header-one affix">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-3 col-md-12">
                        <div class="logo-one logo-wrap">
                            <div class="logo my-1">
                                <a href="index.php/beatle/index.html">
                                    <img src="../../assets/logo/logo-white.png" alt="logo">
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-9 col-md-12">
                        <div class="menu menu-one">
                            <nav class="navbar navbar-expand-lg">
                                <button class="navbar-toggler" type="button" data-toggle="collapse"
                                    data-target="#nav-content" aria-controls="nav-content" aria-expanded="false"
                                    aria-label="Toggle navigation">
                                    <span class="navbar-toggler-icon bar1"></span>
                                    <span class="navbar-toggler-icon bar2"></span>
                                    <span class="navbar-toggler-icon bar3"></span>
                                </button>
                                <!-- Links -->
                                <div class="main-menu collapse navbar-collapse" id="nav-content">
                                    <ul class="navbar-nav ml-auto align-items-lg-center">
                                        <li class="nav-item">
                                            <a class="nav-link" href="https://beatleanalytics.com/">Home</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="https://www.beatleanalytics.com/index.php/beatle/aboutus">about us</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="index.php/beatle/history.html">History</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="https://www.beatleanalytics.com/index.php/beatle/news">News</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="https://beatleanalytics.com/">Blog</a>
                                        </li>
                                        <li class="nav-item" style="visibility: hidden;">
                                            <a class="nav-link" href="https://beatleanalytics.com/">Maru Ahmedabad</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link d-none" href="https://beatleanalytics.com/">contact
                                                us</a>
                                        </li>
                                        <li class="login-sign nav-item" id="intro_login" style="display:none;">
                                            <a href="reception-manager/index.html"
                                                class="btn btn-one btn-anim br-5 px-3 nav-btn ">
                                                <i class="fa fa-user"></i> login
                                            </a>
                                        </li>
                                        <li class="nav-item d-lg-block d-none">
                                            <a href="../../assets/frontend/video/BA.html"
                                                class="btn btn-one btn-anim br-5 px-3 nav-btn popup-vimeo"
                                                id="intro_for_Business">
                                                <i class="fa fa-plus-circle"></i> beatleanalytics for Business
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <section class="page-banner">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h3>Login </h3>
                    <ul class="banner-link text-center">
                        <li>
                            <a href="index.php/beatle/index.html">Home</a>
                        </li>
                        <li>
                            <span class="active">login</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <!-- page-banner ends-->
    <!-- login start-->
    <div class="login-section- bg-w sp-100">
        <div class="container">
            <div class="row justify-content-end">
                <div class="col-lg-6">
                    <ul class="nav d-flex log-tab mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" href="https://beatleanalytics.com/" role="tab">Beatle Analytics -
                                Survey </a>
                        </li>
                    </ul>
                    <br>
                    <ul class="nav d-flex log-tab mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" href="https://beatleanalytics.com/" role="tab">Beatle Analytics -
                                Score
                                Cards </a>
                        </li>
                    </ul>
                    <br>
                    <ul class="nav d-flex log-tab mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" href="http://rrms.beatlebuddy.com/" role="tab">Beatle Analytics -
                                RRMS </a>
                        </li>
                    </ul>
                    <br>
                    <ul class="nav d-flex log-tab mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" href="https://pmc.beatleme.co.in/" role="tab">Beatle Analytics -
                                Staion Cleanliness </a>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <ul class="nav d-flex log-tab mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" href="#register" role="tab" data-toggle="tab">Beatle Analytics -
                                Station Cleanliness Feedback</a>
                        </li>
                    </ul>
                    <!-- Tab panes -->
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane fade in active show" id="login">
                            <form class="custom-form"  method="POST">
                                <?php if(isset($error)): ?>
                                    <div class="error mb-2"><?php echo $error; ?></div>
                                <?php endif; ?>
                                <div class="row">
                                    <div class="col-12">
                                        <span class="fa fa-user"></span>
                                        <input type="text" id="username" name="username" class="form-control"
                                            placeholder="Enter User Name" required>
                                        <span class="invalid-feedback" role="alert">
                                        </span>
                                    </div>
                                    <div class="col-12">
                                        <span class="fa fa-lock"></span>
                                        <input type="password" name="password" class="form-control"
                                            placeholder="Enter Password" required>
                                        <span class="invalid-feedback" role="alert">
                                        </span>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <button type="submit" name="login" class="btn btn-one btn-anim w-100">
                                            Login</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="footer footer-one">
        <div class="foot-top">
            <div class="container">
                <div class="row">
                    <div class="col-xl-4 col-lg-12 col-12 mb-60">
                        <div class="company-details">
                            <img src="../../assets/frontend/img/logo-white.png" class="foot-logo mb-4" alt="">
                            <p class="pb-2 text-justify">At Beatle Analytics, it's all about growth following the
                                trends. We are passionate about moving ahead towards success of our clients envisage.
                                Team Beatle Analytics is a group of experienced hardworking entrepreneurs with zeal
                                and drive to deliver.
                            </p>
                            <div class="socials mt-4">
                                <a class="facebook" href="https://www.facebook.com/beatleanalytics/" target="_blank">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a class="linkedin" href="https://in.linkedin.com/company/beatle-analytics"
                                    target="_blank">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                                <a class="blogger" href="https://beatleanalytics.wordpress.com/" target="_blank">
                                    <i class="fab fa-blogger"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-6 col-sm-6 col-12 mb-60">
                        <div class="recent-post">
                            <div class="foot-title">
                                <h4>useful links</h4>
                            </div>
                            <ul class="quick-link">
                                <li>
                                    <a href="index.php/beatle/PrivacyPolicy.html">Privacy Policy
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-6 col-sm-6 col-12 mb-60">
                        <div class="recent-post">
                            <div class="foot-title">
                                <h4> Menu</h4>
                            </div>
                            <ul class="quick-link">
                                <li>
                                    <a href="index.php/beatle/index.html">Home</a>
                                </li>
                                <li>
                                    <a href="index.php/beatle/aboutus.html">About us</a>
                                </li>
                                <li>
                                    <a href="index.php/beatle/news.html">News</a>
                                </li>
                                <li>
                                    <a href="index.php/beatle/blog.html">Blog</a>
                                </li>
                                <li>
                                    <a href="index.php/beatle/contact.html">Contact us</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-6 col-md-12 col-sm-12 col-12 mb-60">
                        <div class="recent-post">
                            <div class="foot-title">
                                <h4>Latest Blogs</h4>
                            </div>
                            <div class="widget">
                                <div class="widget-post">
                                    <ul>
                                        <li class="news-post">
                                            <figure class="thumb">
                                                <a
                                                    href="index.php/beatle/blogDetails/how-to-collect-restaurant-feedbacks-effectively/Mw%3d%3d.html">
                                                    <img src="blog_image/1645617173_blogImage.html" alt="news"
                                                        class="h56">
                                                </a>
                                            </figure>
                                            <div class="news-content">
                                                <a class="color_fff two_line"
                                                    href="index.php/beatle/blogDetails/how-to-collect-restaurant-feedbacks-effectively/Mw%3d%3d.html">How
                                                    to collect restaurant feedbacks effectively</a>
                                                <p>
                                                    23, Feb 2022
                                                </p>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="foot-bottom">
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <p class="text-capitalize">Copyright © 2020, All rights Reserved. Created by
                            <a href="https://www.beatlebuddy.com/" target="_blank">BeatleBuddy</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!-- footer end -->
    <!-- JavaScript Libraries -->
    <script src="../../assets/frontend/js/jquery-3.3.1.min.js"></script>
    <script src="../../assets/frontend/js/popper.min.js"></script>
    <script src="../../assets/frontend/js/bootstrap.min.js"></script>
    <script src="../../assets/frontend/js/owl.carousel.min.js"></script>
    <script src="../../assets/frontend/js/slick.min.js"></script>
    <script src="../../assets/frontend/js/tilt.jquery.js"></script>
    <script src="../../assets/frontend/js/custom.js"></script>
    <script src="../cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js"></script>
    <script src="../../assets/admin/js/new_front_custom.js"></script>
    <script src="../cdn.jsdelivr.net/gh/t4t5/sweetalert%40v0.2.0/lib/sweet-alert.min.js"></script>
    <div class="container">
    </div>
    <a href="https://api.whatsapp.com/send?phone=+91%2080002%2021818&amp;text=Welcome%20BeatleAnalytics." class="float"
        target="_blank">
        <i class="fab fa-whatsapp-square"></i></a>
    <a href="tel:8000221818" class="float_left" target="_blank">
        <i class="fa fa-phone"></i>
    </a>
    <script src="../cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.js"></script>
    <script>
    </script>
</body>
</html>