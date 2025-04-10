<?php
// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to generate success alert
function success_alert($message) {
    return '<div class="alert alert-success">' . $message . '</div>';
}

// Function to generate error alert
function error_alert($message) {
    return '<div class="alert alert-danger">' . $message . '</div>';
}

// Function to redirect user to appropriate dashboard
function redirect_to_dashboard($user_type) {
    switch ($user_type) {
        case 'normal':
            header("Location: dashboard/user_dashboard.php");
            break;
        case 'govt':
            header("Location: dashboard/govt_dashboard.php");
            break;
        case 'entrepreneur':
            header("Location: dashboard/entrepreneur_dashboard.php");
            break;
        default:
            header("Location: ../index.php");
    }
    exit();
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to get user data
function get_user_data($conn, $user_id) {
    // Try to get all fields (including new profile fields)
    try {
        $stmt = $conn->prepare("SELECT id, full_name, email, user_type, govt_id, company_name, business_type, 
                              interests, bio, profile_pic, email_notifications, idea_updates, 
                              initiative_alerts, event_reminders, privacy_level, created_at 
                           FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    } catch (mysqli_sql_exception $e) {
        // If error happens (likely because new columns don't exist), fall back to basic fields
        $stmt = $conn->prepare("SELECT id, full_name, email, user_type, govt_id, company_name, business_type
                             FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    }
    
    return null;
}

// Function to record user activity
function record_activity($conn, $user_id, $activity_type, $activity_description) {
    try {
        // First check if the user_activities table exists
        $checkTable = $conn->query("SHOW TABLES LIKE 'user_activities'");
        
        // If table doesn't exist, create it
        if ($checkTable->num_rows == 0) {
            $createTable = "CREATE TABLE user_activities (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) NOT NULL,
                activity_type VARCHAR(50) NOT NULL,
                description TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (user_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $conn->query($createTable);
        }
        
        // Insert the activity
        $stmt = $conn->prepare("INSERT INTO user_activities (user_id, activity_type, description) VALUES (?, ?, ?)");
        if (!$stmt) {
            error_log("Failed to prepare activity insert: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("iss", $user_id, $activity_type, $activity_description);
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Failed to insert activity: " . $stmt->error);
        }
        
        return $success;
    } catch (Exception $e) {
        error_log("Error recording user activity: " . $e->getMessage());
        return false;
    }
}

// Function to get recent user activities
function get_user_activities($conn, $user_id, $limit = 5) {
    try {
        // Check if the table exists
        $checkTable = $conn->query("SHOW TABLES LIKE 'user_activities'");
        if ($checkTable->num_rows == 0) {
            // Create activities table if it doesn't exist
            $createTable = "CREATE TABLE user_activities (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) NOT NULL,
                activity_type VARCHAR(50) NOT NULL,
                description TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (user_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $conn->query($createTable);
            
            // No activities yet since the table was just created
            return [];
        }
        
        // Get activities with proper parameterized query
        $stmt = $conn->prepare("SELECT id, activity_type, description, UNIX_TIMESTAMP(created_at) as unix_time, created_at 
                               FROM user_activities 
                               WHERE user_id = ? 
                               ORDER BY created_at DESC 
                               LIMIT ?");
        
        if (!$stmt) {
            // Query preparation failed
            error_log("Failed to prepare activity query: " . $conn->error);
            return [];
        }
        
        $stmt->bind_param("ii", $user_id, $limit);
        $success = $stmt->execute();
        
        if (!$success) {
            // Query execution failed
            error_log("Failed to execute activity query: " . $stmt->error);
            return [];
        }
        
        $result = $stmt->get_result();
        
        $activities = [];
        $current_time = time();
        
        while ($row = $result->fetch_assoc()) {
            // Get the Unix timestamp directly from MySQL using UNIX_TIMESTAMP()
            $db_timestamp = isset($row['unix_time']) ? intval($row['unix_time']) : 0;
            
            // If UNIX_TIMESTAMP failed, try to convert the string manually
            if (!$db_timestamp && isset($row['created_at']) && !empty($row['created_at'])) {
                $db_timestamp = strtotime($row['created_at']);
            }
            
            // Default fallback if all else fails
            if (!$db_timestamp || $db_timestamp > $current_time) {
                error_log("Invalid timestamp for activity ID " . $row['id'] . ": " . $row['created_at']);
                $db_timestamp = $current_time;
            }
            
            // Calculate time difference
            $time_difference = $current_time - $db_timestamp;
            
            // Format time string based on the difference
            $formatted_time = '';
            if ($time_difference < 60) {
                if ($time_difference < 10) {
                    $formatted_time = 'Just now';
                } else {
                    $formatted_time = $time_difference . ' seconds ago';
                }
            } elseif ($time_difference < 3600) {
                $minutes = floor($time_difference / 60);
                $formatted_time = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
            } elseif ($time_difference < 86400) {
                $hours = floor($time_difference / 3600);
                $formatted_time = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
            } elseif ($time_difference < 604800) {
                $days = floor($time_difference / 86400);
                $formatted_time = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
            } else {
                $formatted_time = date('M j, Y', $db_timestamp);
            }
            
            // Log information for debugging
            error_log("Activity ID: " . $row['id'] . 
                     ", Current Time: " . $current_time . 
                     ", DB Time: " . $db_timestamp . 
                     ", Diff: " . $time_difference . 
                     ", Formatted: " . $formatted_time);
            
            // Map activity type to icon
            $icon = 'fa-info-circle';
            switch ($row['activity_type']) {
                case 'profile_update':
                    $icon = 'fa-user-edit';
                    break;
                case 'password_change':
                    $icon = 'fa-key';
                    break;
                case 'photo_update':
                    $icon = 'fa-image';
                    break;
                case 'login':
                    $icon = 'fa-sign-in-alt';
                    break;
                case 'logout':
                    $icon = 'fa-sign-out-alt';
                    break;
                case 'settings_update':
                    $icon = 'fa-cog';
                    break;
                case 'like':
                    $icon = 'fa-heart';
                    break;
                case 'comment':
                    $icon = 'fa-comment';
                    break;
                case 'view':
                    $icon = 'fa-eye';
                    break;
                case 'save':
                    $icon = 'fa-save';
                    break;
            }
            
            // Build the activity object with necessary data for proper display
            $activities[] = [
                'id' => $row['id'],
                'type' => $row['activity_type'],
                'title' => $row['description'],
                'time' => $formatted_time,
                'icon' => $icon,
                'timestamp' => $db_timestamp,
                'raw_date' => $row['created_at'],
                'unix_time' => $db_timestamp // Explicitly include the Unix timestamp
            ];
        }
        
        return $activities;
    } catch (Exception $e) {
        error_log("Error getting user activities: " . $e->getMessage());
        return [];
    }
}
?> 