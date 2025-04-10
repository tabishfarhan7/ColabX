<?php
// Set default timezone to match your local timezone
date_default_timezone_set('UTC');

// Database Connection Parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "colabx_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set MySQL timezone to match PHP timezone
$timezone = date('P');
$conn->query("SET time_zone = '$timezone'");

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?> 