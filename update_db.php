<?php
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

echo "<h2>ColabX Database Update Script</h2>";
echo "<p>Adding new columns to users table...</p>";

// Array of columns to add to users table
$columns = [
    "interests TEXT NULL",
    "bio TEXT NULL",
    "profile_pic VARCHAR(255) NULL",
    "email_notifications TINYINT(1) NOT NULL DEFAULT 1",
    "idea_updates TINYINT(1) NOT NULL DEFAULT 1",
    "initiative_alerts TINYINT(1) NOT NULL DEFAULT 1",
    "event_reminders TINYINT(1) NOT NULL DEFAULT 1",
    "privacy_level ENUM('public', 'limited', 'private') NOT NULL DEFAULT 'public'",
    "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
];

// Get existing columns in users table
$result = $conn->query("SHOW COLUMNS FROM users");
$existingColumns = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $existingColumns[] = $row['Field'];
    }
}

// Add each column if it doesn't exist
$addedColumns = 0;
$skippedColumns = 0;

foreach ($columns as $columnDefinition) {
    // Extract column name from definition
    preg_match('/^(\w+)/', $columnDefinition, $matches);
    $columnName = $matches[1];
    
    if (!in_array($columnName, $existingColumns)) {
        $sql = "ALTER TABLE users ADD COLUMN $columnDefinition";
        
        if ($conn->query($sql) === TRUE) {
            echo "<p>✅ Added column '$columnName' successfully</p>";
            $addedColumns++;
        } else {
            echo "<p>❌ Error adding column '$columnName': " . $conn->error . "</p>";
        }
    } else {
        echo "<p>⏩ Column '$columnName' already exists, skipping</p>";
        $skippedColumns++;
    }
}

// Create uploads directory if it doesn't exist
$uploadsDir = 'uploads/profile_pics';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
    echo "<p>✅ Created uploads directory for profile pictures</p>";
}

// Summary
echo "<h3>Update Summary:</h3>";
echo "<p>Added $addedColumns new columns</p>";
echo "<p>Skipped $skippedColumns existing columns</p>";

if ($addedColumns > 0) {
    echo "<p>Database updated successfully! You can now use the new profile and settings features.</p>";
} else if ($skippedColumns == count($columns)) {
    echo "<p>Database is already up to date. No changes were made.</p>";
}

echo "<p><a href='index.php'>Return to homepage</a></p>";

// Close connection
$conn->close();
?> 