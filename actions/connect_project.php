<?php
// actions/connect_project.php - Handles connections between ideas and initiatives

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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to connect with ideas';
    echo json_encode($response);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Get idea ID and initiative ID from request
$idea_id = isset($_POST['idea_id']) ? intval($_POST['idea_id']) : 0;
$initiative_id = isset($_POST['initiative_id']) ? intval($_POST['initiative_id']) : 0;

if ($idea_id <= 0 || $initiative_id <= 0) {
    $response['message'] = 'Invalid parameters. Both idea and initiative must be specified.';
    echo json_encode($response);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

try {
    // Check if connection already exists
    $check_query = "SELECT id FROM connections 
                   WHERE idea_id = ? AND initiative_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $idea_id, $initiative_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response['message'] = 'This idea is already connected to this initiative';
        echo json_encode($response);
        exit;
    }
    
    // Check if idea exists and belongs to the user
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
    
    // Check if user owns this idea
    if ($idea['user_id'] != $user_id) {
        $response['message'] = 'You can only connect your own ideas';
        echo json_encode($response);
        exit;
    }
    
    // Check if initiative exists
    $initiative_query = "SELECT id, title FROM initiatives WHERE id = ?";
    $stmt = $conn->prepare($initiative_query);
    $stmt->bind_param("i", $initiative_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = 'Initiative not found';
        echo json_encode($response);
        exit;
    }
    
    $initiative = $result->fetch_assoc();
    
    // Create new connection
    $insert_query = "INSERT INTO connections (idea_id, initiative_id, status, created_at)
                    VALUES (?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ii", $idea_id, $initiative_id);
    $stmt->execute();
    
    // Record activity
    if (function_exists('record_activity')) {
        record_activity($conn, $user_id, 'connect_idea', "Connected idea: " . $idea['title'] . " with initiative: " . $initiative['title']);
    }
    
    // Success response
    $response['success'] = true;
    $response['message'] = 'Connection created successfully';
    
} catch (Exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
exit; 