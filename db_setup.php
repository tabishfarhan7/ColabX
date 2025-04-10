<?php
// Database Connection Parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "colabx_db";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('normal', 'govt', 'entrepreneur') NOT NULL DEFAULT 'normal',
    govt_id VARCHAR(50) NULL,
    company_name VARCHAR(100) NULL,
    business_type VARCHAR(50) NULL,
    interests TEXT NULL,
    bio TEXT NULL,
    profile_pic VARCHAR(255) NULL,
    email_notifications TINYINT(1) NOT NULL DEFAULT 1,
    idea_updates TINYINT(1) NOT NULL DEFAULT 1,
    initiative_alerts TINYINT(1) NOT NULL DEFAULT 1,
    event_reminders TINYINT(1) NOT NULL DEFAULT 1,
    privacy_level ENUM('public', 'limited', 'private') NOT NULL DEFAULT 'public',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully or already exists.<br>";
} else {
    die("Error creating users table: " . $conn->error);
}

// Create ideas table for project submissions
$sql = "CREATE TABLE IF NOT EXISTS ideas (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    sector VARCHAR(50) NOT NULL,
    status ENUM('pending', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Ideas table created successfully or already exists.<br>";
} else {
    die("Error creating ideas table: " . $conn->error);
}

// Create initiatives table
$sql = "CREATE TABLE IF NOT EXISTS initiatives (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    department VARCHAR(50) NOT NULL,
    status ENUM('draft', 'active', 'closed') NOT NULL DEFAULT 'draft',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Initiatives table created successfully or already exists.<br>";
} else {
    die("Error creating initiatives table: " . $conn->error);
}

// Create connections table (to track connections between entrepreneurs and government)
$sql = "CREATE TABLE IF NOT EXISTS connections (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idea_id INT(11) UNSIGNED NOT NULL,
    initiative_id INT(11) UNSIGNED NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (idea_id) REFERENCES ideas(id) ON DELETE CASCADE,
    FOREIGN KEY (initiative_id) REFERENCES initiatives(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Connections table created successfully or already exists.<br>";
} else {
    die("Error creating connections table: " . $conn->error);
}

// Create user activities table
$sql = "CREATE TABLE IF NOT EXISTS user_activities (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) UNSIGNED NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "User activities table created successfully or already exists.<br>";
} else {
    die("Error creating user activities table: " . $conn->error);
}

// Create uploads directory if it doesn't exist
$uploadsDir = 'uploads/profile_pics';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
    echo "Uploads directory created successfully.<br>";
}

echo "<p>Database setup completed successfully!</p>";
echo "<p>You can now <a href='pages/register.php'>register</a> a new user or <a href='pages/login.php'>login</a> if you already have an account.</p>";

// Close connection
$conn->close();
?> 