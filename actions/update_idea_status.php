<?php
// actions/update_idea_status.php - Handles updating idea status (approve/reject)

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => 'Unknown error occurred'
];

// Check if user is logged in and is government user type
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'govt') {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Get idea ID and action from request
$idea_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($idea_id <= 0) {
    $response['message'] = 'Invalid idea ID';
    echo json_encode($response);
    exit;
}

if ($action !== 'approve' && $action !== 'reject') {
    $response['message'] = 'Invalid action';
    echo json_encode($response);
    exit;
}

// For mock ideas (ID 1000+), just return success
if ($idea_id >= 1000) {
    $response['success'] = true;
    $response['message'] = 'Mock idea ' . ($action === 'approve' ? 'approved' : 'rejected') . ' successfully';
    echo json_encode($response);
    exit;
}

try {
    // Check if idea exists
    $check_sql = "SELECT id, user_id, title FROM ideas WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $idea_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = 'Idea not found';
        echo json_encode($response);
        exit;
    }
    
    $idea_data = $result->fetch_assoc();
    $entrepreneur_id = $idea_data['user_id'];
    $idea_title = $idea_data['title'];
    
    // Update idea status
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    $update_sql = "UPDATE ideas SET status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_status, $idea_id);
    
    if ($update_stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Idea ' . $new_status . ' successfully';
        
        // Record activity for both government user and entrepreneur
        record_activity($conn, $_SESSION['user_id'], $action . '_idea', "You " . $action . "d idea: " . $idea_title);
        
        // Record activity for entrepreneur
        $action_description = "Your idea \"" . $idea_title . "\" was " . $new_status . " by a government department";
        record_activity($conn, $entrepreneur_id, 'idea_' . $new_status, $action_description);
        
        // Send notification to entrepreneur (could be implemented with a notification system)
        // For now, let's just log it
        error_log("Notification to user $entrepreneur_id: Your idea \"$idea_title\" has been $new_status");
    } else {
        $response['message'] = 'Database error: ' . $conn->error;
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
exit; 