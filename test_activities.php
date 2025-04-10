<?php
// Include database connection and functions
require_once('includes/db_connect.php');
require_once('includes/functions.php');

// Check if session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p>You need to be logged in to test activities. <a href='pages/login.php'>Login here</a></p>";
    exit;
}

// Display user info
echo "<h1>Testing Activity System</h1>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>User Name: " . ($_SESSION['name'] ?? 'Name not set') . "</p>";

// Create a test activity
$testActivity = "Test activity created at " . date('Y-m-d H:i:s');
$result = record_activity($conn, $_SESSION['user_id'], 'test', $testActivity);

if ($result) {
    echo "<p style='color:green'>Successfully recorded test activity!</p>";
} else {
    echo "<p style='color:red'>Failed to record test activity. Check error logs.</p>";
}

// Fetch activities
echo "<h2>Recent Activities</h2>";
$activities = get_user_activities($conn, $_SESSION['user_id'], 10);

if (count($activities) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Type</th><th>Description</th><th>Time</th></tr>";
    
    foreach ($activities as $activity) {
        echo "<tr>";
        echo "<td>" . ($activity['id'] ?? 'No ID') . "</td>";
        echo "<td>" . htmlspecialchars($activity['type']) . "</td>";
        echo "<td>" . htmlspecialchars($activity['title']) . "</td>";
        echo "<td>" . htmlspecialchars($activity['time']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No activities found for this user.</p>";
}

// Show raw database table structure
echo "<h2>Database Table Structure</h2>";
try {
    $tables = $conn->query("SHOW TABLES LIKE 'user_activities'");
    if ($tables->num_rows == 0) {
        echo "<p>user_activities table doesn't exist</p>";
    } else {
        $tableStructure = $conn->query("DESCRIBE user_activities");
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
    }
} catch (Exception $e) {
    echo "<p>Error checking table structure: " . $e->getMessage() . "</p>";
}

// Add a link back to dashboard
echo "<p><a href='pages/dashboard/user_dashboard.php'>Return to Dashboard</a></p>";
?> 