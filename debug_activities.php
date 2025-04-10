<?php
// Include database connection
require_once('includes/db_connect.php');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p>You must be logged in to use this page. <a href='pages/login.php'>Login here</a></p>";
    exit();
}

echo "<h1>ColabX Activity System Debugging</h1>";

// Display user info
echo "<h2>User Information</h2>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>User Email: " . $_SESSION['email'] . "</p>";
echo "<p>User Type: " . $_SESSION['user_type'] . "</p>";
echo "<p>User Name: " . ($_SESSION['name'] ?? 'Not set') . "</p>";

// Check database connection
echo "<h2>Database Connection</h2>";
if ($conn->connect_error) {
    echo "<p style='color:red'>Database connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color:green'>Database connection successful.</p>";
}

// Check user_activities table
echo "<h2>User Activities Table Check</h2>";

try {
    $checkTable = $conn->query("SHOW TABLES LIKE 'user_activities'");
    
    if ($checkTable === false) {
        echo "<p style='color:red'>Error checking for table: " . $conn->error . "</p>";
    } else if ($checkTable->num_rows == 0) {
        echo "<p>Table 'user_activities' does not exist. Attempting to create it now...</p>";
        
        // Create table
        $createTableQuery = "CREATE TABLE user_activities (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) UNSIGNED NOT NULL,
            activity_type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        $createResult = $conn->query($createTableQuery);
        
        if ($createResult === false) {
            echo "<p style='color:red'>Failed to create table: " . $conn->error . "</p>";
            
            // Try again without foreign key constraint if it failed
            echo "<p>Trying again without foreign key constraint...</p>";
            $createTableNoFK = "CREATE TABLE user_activities (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) UNSIGNED NOT NULL,
                activity_type VARCHAR(50) NOT NULL,
                description TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (user_id)
            )";
            
            $createResultNoFK = $conn->query($createTableNoFK);
            
            if ($createResultNoFK === false) {
                echo "<p style='color:red'>Still failed to create table: " . $conn->error . "</p>";
            } else {
                echo "<p style='color:green'>Table created successfully without foreign key constraint.</p>";
            }
        } else {
            echo "<p style='color:green'>Table created successfully.</p>";
        }
    } else {
        echo "<p style='color:green'>Table 'user_activities' exists.</p>";
        
        // Check table structure
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
            echo "<p style='color:red'>Could not retrieve table structure: " . $conn->error . "</p>";
        }
    }
    
    // Check direct SQL insert
    echo "<h2>Testing Direct Insert</h2>";
    
    $testInsert = $conn->prepare("INSERT INTO user_activities (user_id, activity_type, description) VALUES (?, 'test', 'Direct debug test')");
    
    if ($testInsert === false) {
        echo "<p style='color:red'>Failed to prepare statement: " . $conn->error . "</p>";
    } else {
        $userId = $_SESSION['user_id'];
        $testInsert->bind_param("i", $userId);
        
        if ($testInsert->execute()) {
            echo "<p style='color:green'>Successfully inserted test activity.</p>";
        } else {
            echo "<p style='color:red'>Failed to insert: " . $testInsert->error . "</p>";
        }
    }
    
    // Display current activities
    echo "<h2>Current Activities</h2>";
    
    $activities = $conn->prepare("SELECT id, activity_type, description, created_at FROM user_activities WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    
    if ($activities === false) {
        echo "<p style='color:red'>Failed to prepare select: " . $conn->error . "</p>";
    } else {
        $userId = $_SESSION['user_id'];
        $activities->bind_param("i", $userId);
        
        if ($activities->execute()) {
            $result = $activities->get_result();
            
            if ($result->num_rows > 0) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>ID</th><th>Type</th><th>Description</th><th>Created At</th></tr>";
                
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['activity_type']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                    echo "<td>" . $row['created_at'] . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No activities found for your user ID.</p>";
            }
        } else {
            echo "<p style='color:red'>Failed to execute select: " . $activities->error . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo '<h2>Actions</h2>';
echo '<form method="post" action="debug_activities.php">';
echo '<button type="submit" name="create_test_activity" style="padding: 10px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">Create Test Activity</button>';
echo '<button type="submit" name="fix_dashboard" style="padding: 10px 15px; background-color: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">Fix Dashboard Display</button>';
echo '</form>';

// Handle form submissions
if (isset($_POST['create_test_activity'])) {
    // Create a test activity directly
    $activityDesc = "Test activity created at " . date('Y-m-d H:i:s');
    $insertTest = $conn->prepare("INSERT INTO user_activities (user_id, activity_type, description) VALUES (?, 'test', ?)");
    
    if ($insertTest) {
        $userId = $_SESSION['user_id'];
        $insertTest->bind_param("is", $userId, $activityDesc);
        
        if ($insertTest->execute()) {
            echo "<p style='color:green'>Test activity created successfully! Refreshing in 2 seconds...</p>";
            echo "<script>setTimeout(function() { window.location.reload(); }, 2000);</script>";
        } else {
            echo "<p style='color:red'>Failed to create test activity: " . $insertTest->error . "</p>";
        }
    } else {
        echo "<p style='color:red'>Failed to prepare statement: " . $conn->error . "</p>";
    }
}

if (isset($_POST['fix_dashboard'])) {
    echo "<p>Redirecting to dashboard...</p>";
    echo "<script>window.location.href = 'pages/dashboard/user_dashboard.php';</script>";
}

echo "<p><a href='pages/dashboard/user_dashboard.php'>Return to Dashboard</a></p>";
?> 