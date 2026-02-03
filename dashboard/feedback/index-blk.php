<?php
session_start();
include 'connection.php';
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
  // Redirect to login page if not logged in
  header("Location: login.php");
  exit();
}
// User data
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'];
$station_id = $_SESSION['station_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Subscription Expired</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      height: 100vh;
      margin: 0;
      background: linear-gradient(to right, #ece9e6, #ffffff);
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .card {
      background: #ffffff;
      padding: 3rem 2.5rem;
      border: none;
      border-radius: 1.25rem;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      max-width: 460px;
      width: 100%;
      text-align: center;
      animation: fadeInUp 0.6s ease-in-out;
    }

    @keyframes fadeInUp {
      from {
        transform: translateY(30px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .card .icon {
      font-size: 3rem;
      color: #dc3545;
      margin-bottom: 1rem;
    }

    .card h2 {
      font-weight: 700;
      color: #343a40;
      margin-bottom: 0.5rem;
    }

    .card p {
      color: #6c757d;
      font-size: 1.05rem;
    }
  </style>
</head>
<body>

  <div class="card">
    <div class="icon">
      <i class="fas fa-exclamation-triangle"></i>
    </div>
    <h2>Subscription Ended</h2>
    <p>Your subscription has expired. Please get in touch with your system administrator to continue using our services.</p>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
