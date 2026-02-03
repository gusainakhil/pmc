<?php
include "../../connection.php";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Choose a strong secret key
$secret = 'YourSuperSecretKey_ChangeThis123!';

// Get all users without token or where token is null
$sql = "SELECT userId FROM baris_userlogin WHERE login_token IS NULL OR login_token = ''";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $userId = $row['userId'];
        $token = hash('sha256', $userId . $secret);

        // Update token for each user
        $update = "UPDATE baris_userlogin SET login_token = '$token' WHERE userId = $userId";
        if ($conn->query($update)) {
            echo "Token generated for userId: $userId<br>";
        } else {
            echo "Error updating userId $userId: " . $conn->error . "<br>";
        }
    }
} else {
    echo "All users already have tokens.";
}

$conn->close();
?>
