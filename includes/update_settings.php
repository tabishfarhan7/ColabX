<?php
// Include database connection and functions
require_once('db_connect.php');
require_once('functions.php');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize response array
$response = array(
    'success' => false,
    'message' => ''
);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to update your settings.';
    echo json_encode($response);
    exit();
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user ID from the form
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : $_SESSION['user_id'];
    
    // Verify user is updating their own settings
    if ($userId != $_SESSION['user_id']) {
        $response['message'] = 'You can only update your own settings.';
        echo json_encode($response);
        exit();
    }
    
    // Get current user data
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows != 1) {
        $response['message'] = 'User not found.';
        echo json_encode($response);
        exit();
    }
    
    $userData = $result->fetch_assoc();
    
    // Handle password change if requested
    $passwordChanged = false;
    $currentPassword = isset($_POST['currentPassword']) ? $_POST['currentPassword'] : '';
    $newPassword = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';
    $confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';
    
    if (!empty($newPassword)) {
        // Verify current password
        if (empty($currentPassword)) {
            $response['message'] = 'Current password is required to set a new password.';
            echo json_encode($response);
            exit();
        }
        
        if (!password_verify($currentPassword, $userData['password'])) {
            $response['message'] = 'Current password is incorrect.';
            echo json_encode($response);
            exit();
        }
        
        // Verify new password matches confirmation
        if ($newPassword !== $confirmPassword) {
            $response['message'] = 'New password and confirmation do not match.';
            echo json_encode($response);
            exit();
        }
        
        // Validate password strength
        if (strlen($newPassword) < 8) {
            $response['message'] = 'Password must be at least 8 characters long.';
            echo json_encode($response);
            exit();
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $passwordChanged = true;
    }
    
    // Get notification preferences
    $emailNotifications = isset($_POST['notifications']['email']) ? 1 : 0;
    $ideaUpdates = isset($_POST['notifications']['ideas']) ? 1 : 0;
    $initiativeAlerts = isset($_POST['notifications']['initiatives']) ? 1 : 0;
    $eventReminders = isset($_POST['notifications']['events']) ? 1 : 0;
    
    // Get privacy level
    $privacyLevel = sanitize_input($_POST['privacyLevel']);
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Prepare SQL for settings update
        if ($passwordChanged) {
            $stmt = $conn->prepare("
                UPDATE users 
                SET 
                    password = ?,
                    email_notifications = ?,
                    idea_updates = ?,
                    initiative_alerts = ?,
                    event_reminders = ?,
                    privacy_level = ?
                WHERE id = ?
            ");
            $stmt->bind_param("siiiisi", $hashedPassword, $emailNotifications, $ideaUpdates, $initiativeAlerts, $eventReminders, $privacyLevel, $userId);
        } else {
            $stmt = $conn->prepare("
                UPDATE users 
                SET 
                    email_notifications = ?,
                    idea_updates = ?,
                    initiative_alerts = ?,
                    event_reminders = ?,
                    privacy_level = ?
                WHERE id = ?
            ");
            $stmt->bind_param("iiiisi", $emailNotifications, $ideaUpdates, $initiativeAlerts, $eventReminders, $privacyLevel, $userId);
        }
        
        if ($stmt->execute()) {
            // Commit transaction
            $conn->commit();
            
            // Record activities
            if ($passwordChanged) {
                record_activity($conn, $userId, 'password_change', 'You changed your password');
            }
            
            // Record notification settings changes
            record_activity($conn, $userId, 'settings_update', 'You updated your account settings');
            
            // Set success response
            $response['success'] = true;
            $response['message'] = 'Account settings updated successfully.' . ($passwordChanged ? ' Your password has been changed.' : '');
        } else {
            // Rollback transaction
            $conn->rollback();
            $response['message'] = 'Failed to update settings: ' . $conn->error;
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $response['message'] = 'An error occurred: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Return JSON response
echo json_encode($response);
exit();
?> 