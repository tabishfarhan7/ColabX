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

echo "<h2>ColabX Initiatives Table Update Script</h2>";
echo "<p>Adding new columns to initiatives table...</p>";

// Array of columns to add to initiatives table
$columns = [
    "objectives TEXT NULL",
    "budget DECIMAL(10,2) DEFAULT 0"
];

// Get existing columns in initiatives table
$result = $conn->query("SHOW COLUMNS FROM initiatives");
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
        $sql = "ALTER TABLE initiatives ADD COLUMN $columnDefinition";
        
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

// Summary
echo "<h3>Update Summary:</h3>";
echo "<p>Added $addedColumns new columns to initiatives table</p>";
echo "<p>Skipped $skippedColumns existing columns from initiatives table</p>";

if ($addedColumns > 0) {
    echo "<p>Initiatives table updated successfully! You can now use the objectives and budget features.</p>";
} else if ($skippedColumns == count($columns)) {
    echo "<p>Initiatives table is already up to date. No changes were made.</p>";
}

echo "<p><a href='index.php'>Return to homepage</a></p>";

// Close connection
$conn->close();
?> 