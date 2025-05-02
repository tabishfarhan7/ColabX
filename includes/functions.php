<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return [
            'full_name' => 'Unknown User',
            'email' => 'unknown@example.com',
            'user_type' => 'normal',
            'govt_id' => '',
            'company_name' => '',
            'business_type' => ''
        ];
    }
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

// Function to log user activities
function log_activity($conn, $user_id, $activity_type, $description) {
    $sql = "INSERT INTO user_activities (user_id, activity_type, description) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $activity_type, $description);
    return $stmt->execute();
}

// Function to generate a password reset token and store it in the database
function generatePasswordResetToken($email) {
    global $conn;
    
    // Verify user exists
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false; // User not found
    }
    
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    
    // Generate a random token
    $token = bin2hex(random_bytes(32));
    
    // Set expiration time (24 hours from now)
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Delete any existing tokens for this user
    $delete_sql = "DELETE FROM password_resets WHERE user_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $user_id);
    $delete_stmt->execute();
    
    // Store the new token
    $insert_sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iss", $user_id, $token, $expires_at);
    
    if ($insert_stmt->execute()) {
        return $token;
    } else {
        return false;
    }
}

// Function to verify if a password reset token is valid
function verifyPasswordResetToken($token) {
    global $conn;
    
    // Check if token exists and is not expired or used
    $sql = "SELECT pr.id, pr.user_id, u.email 
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token = ? 
            AND pr.expires_at > NOW() 
            AND pr.used = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false; // Token invalid, expired, or already used
    }
    
    return $result->fetch_assoc();
}

// Function to update password and mark token as used
function resetPassword($token, $new_password) {
    global $conn;
    
    // Verify token is valid
    $token_data = verifyPasswordResetToken($token);
    if (!$token_data) {
        return false;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update user password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_user_sql = "UPDATE users SET password = ? WHERE id = ?";
        $update_user_stmt = $conn->prepare($update_user_sql);
        $update_user_stmt->bind_param("si", $hashed_password, $token_data['user_id']);
        $update_user_stmt->execute();
        
        // Mark token as used
        $update_token_sql = "UPDATE password_resets SET used = 1 WHERE token = ?";
        $update_token_stmt = $conn->prepare($update_token_sql);
        $update_token_stmt->bind_param("s", $token);
        $update_token_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return false;
    }
}

// Other utility functions can be added here
?> 