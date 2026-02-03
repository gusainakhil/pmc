<?php
ob_start();                    
session_start();               
error_reporting(E_ALL);        
ini_set('display_errors', 1);  

include "../../connection.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query = "SELECT * FROM baris_userlogin 
              WHERE db_userLoginName = '$username' 
              AND db_usertype = 'SU_admin' 
              LIMIT 1";

    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['db_password'])) {
            $_SESSION['user_id'] = $user['userId'];
            $_SESSION['username'] = $user['db_userLoginName'];
            $_SESSION['usertype'] = $user['db_usertype'];
            
            

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login to station cleaning</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
  <div class="container">
    <div class="left-panel">
      <form class="login-form" method="POST">
        <h2>Log In to <span class="highlight">Station cleaning</span></h2>
        <?php if (!empty($error)): ?>
          <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <div class="input-group">
          <label>Username</label>
          <input type="text" name="username" required />
        </div>
        <div class="input-group">
          <label>Password</label>
          <input type="password" name="password" id="password" required />
        </div>
        <div class="toggle-password">
          <input type="checkbox" id="showPassword" onclick="togglePassword()" />
          <label for="showPassword">Show Password</label>
        </div>
        <button type="submit" name="login" class="btn">Log In</button>
        <div class="extras">
          <label><input type="checkbox" /> Keep me logged in</label>
          <a href="#">Forgot Password?</a>
        </div>
        <p class="copyright">© 2025 Beatlebuddy. All rights reserved.</p>
      </form>
    </div>
    <div class="right-panel">
      <img src="assets/image/tarin_logo.png" alt="Indian Railways Logo" />
    </div>
  </div>

  <script>
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
    }
  </script>
</body>
</html>
