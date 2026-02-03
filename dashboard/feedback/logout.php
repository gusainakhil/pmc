<?php
// Start the session
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Optionally redirect the user to the login page or home page
header("Location: login.php");
exit();
?>
