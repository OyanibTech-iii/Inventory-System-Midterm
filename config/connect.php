<?php
// Include database class
require_once 'database.php';

// Create database connection
global $conn;
$database = new Database();
$conn = $database->getConnection();
?>