<?php
// this handles the logic for Url authentication.

session_start();
if (!isset($_SESSION['username']) || empty($_SESSION['username'])){
    header("Location: login.php");
    exit();
}
function isLoggedIn() {
    // Check if user is logged in
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}
?>