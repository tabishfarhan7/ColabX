<?php
// Include database connection
require_once('db_connect.php');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as admin or just run as a one-time update
// Uncomment this if you want to restrict access
/*
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo "Access denied. Admin privileges required.";
    exit();
}
*/

// Function to create a column if it doesn't exist
function add_column_if_not_exists($conn, $table, $column, $definition) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($conn->query($sql) === TRUE) {
            echo "Column '$column' added to '$table' table.<br>";
        } else {
            echo "Error adding column '$column': " . $conn->error . "<br>";
        }
    } else {
        echo "Column '$column' already exists in '$table' table.<br>";
    }
}

// Add missing columns to users table
add_column_if_not_exists($conn, 'users', 'interests', 'TEXT NULL');
add_column_if_not_exists($conn, 'users', 'bio', 'TEXT NULL');
add_column_if_not_exists($conn, 'users', 'profile_pic', 'VARCHAR(255) NULL');

// Ensure uploads directory exists
$uploadDir = '../uploads/profile_pics/';
if (!file_exists($uploadDir)) {
    if (mkdir($uploadDir, 0777, true)) {
        echo "Created uploads directory for profile pictures.<br>";
    } else {
        echo "Failed to create uploads directory. Please create it manually.<br>";
    }
}

// Create initiative_interests table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS initiative_interests (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    initiative_id INT(11) UNSIGNED NOT NULL,
    user_id INT(11) UNSIGNED NOT NULL,
    idea_id INT(11) UNSIGNED NULL,
    proposal TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX initiative_id_idx (initiative_id),
    INDEX user_id_idx (user_id),
    INDEX idea_id_idx (idea_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "Table 'initiative_interests' created successfully or already exists.<br>";
} else {
    echo "Error creating table 'initiative_interests': " . $conn->error . "<br>";
}

echo "<p>Database update completed. <a href='../index.php'>Return to home page</a></p>";
?> 