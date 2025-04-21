<?php
// actions/support_project.php - Handles idea support functionality

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/db_connect.php';
 
// Initialize response array
$response = [
    'success' => false,
    'message' => 'Unknown error occurred',
    'action' => ''
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to support ideas';
    echo json_encode($response);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Get project ID from request
$idea_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

if ($idea_id <= 0) {
    $response['message'] = 'Invalid idea ID';
    echo json_encode($response);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

try {
    // Check if user already supported this idea
    $check_query = "SELECT id FROM idea_supports 
                   WHERE user_id = ? AND idea_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $user_id, $idea_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $already_supported = $result->num_rows > 0;
    
    // Check if idea exists
    $idea_query = "SELECT id, user_id, title FROM ideas WHERE id = ?";
    $stmt = $conn->prepare($idea_query);
    $stmt->bind_param("i", $idea_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = 'Idea not found';
        echo json_encode($response);
        exit;
    }
    
    $idea = $result->fetch_assoc();
    
    if ($already_supported) {
        // If already supported, remove the support
        $delete_query = "DELETE FROM idea_supports 
                        WHERE user_id = ? AND idea_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $user_id, $idea_id);
        $stmt->execute();
        
        $response['success'] = true;
        $response['message'] = 'Idea support removed';
        $response['action'] = 'unsupported';
    } else {
        // Create new support entry
        $insert_query = "INSERT INTO idea_supports (user_id, idea_id, created_at)
                        VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ii", $user_id, $idea_id);
        $stmt->execute();
        
        // Record activity
        if (function_exists('record_activity')) {
            record_activity($conn, $user_id, 'support_idea', "Supported idea: " . $idea['title']);
        }
        
        $response['success'] = true;
        $response['message'] = 'Idea supported successfully';
        $response['action'] = 'supported';
    }
    
    // Get updated support count
    $count_query = "SELECT COUNT(*) as count FROM idea_supports WHERE idea_id = ?";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("i", $idea_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count_data = $result->fetch_assoc();
    
    $response['count'] = $count_data['count'];
    
} catch (Exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
exit; 