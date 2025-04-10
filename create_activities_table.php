<?php
// Include database connection
require_once('includes/db_connect.php');

echo "<h1>ColabX Database Update</h1>";

try {
    // Check if table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'user_activities'");
    
    if ($checkTable->num_rows > 0) {
        echo "<p>The user_activities table already exists.</p>";
    } else {
        // Create the table
        $createTableQuery = "CREATE TABLE user_activities (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            activity_type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        if ($conn->query($createTableQuery)) {
            echo "<p style='color:green'>Successfully created user_activities table!</p>";
            
            // Insert a test activity for the current user if logged in
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                $testActivityQuery = "INSERT INTO user_activities 
                    (user_id, activity_type, description) 
                    VALUES (?, 'table_created', 'System created the activities table')";
                
                $stmt = $conn->prepare($testActivityQuery);
                $stmt->bind_param("i", $userId);
                
                if ($stmt->execute()) {
                    echo "<p style='color:green'>Added a test activity for your account.</p>";
                } else {
                    echo "<p style='color:red'>Failed to add test activity: " . $stmt->error . "</p>";
                }
            }
        } else {
            echo "<p style='color:red'>Error creating table: " . $conn->error . "</p>";
        }
    }
    
    // Verify table structure
    echo "<h2>Table Structure</h2>";
    $tableStructure = $conn->query("DESCRIBE user_activities");
    
    if ($tableStructure) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $tableStructure->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color:red'>Could not retrieve table structure.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='pages/dashboard/user_dashboard.php'>Return to Dashboard</a></p>";
?> 