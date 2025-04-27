<?php
// actions/check_initiative_like.php - Check if a user has already liked an initiative

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/db_connect.php';

// Initialize response
$response = [
    'success' => false,
    'liked' => false,
    'message' => ''
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

// Check if initiative ID is provided
if (!isset($_GET['initiative_id']) || empty($_GET['initiative_id'])) {
    $response['message'] = 'Initiative ID is required';
    echo json_encode($response);
    exit;
}

// Get initiative ID
$initiative_id = intval($_GET['initiative_id']);
$user_id = $_SESSION['user_id'];

try {
    // Check if initiative_likes table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'initiative_likes'");
    
    if ($tableCheck->num_rows === 0) {
        // Table doesn't exist yet, so no likes
        $response['success'] = true;
        $response['message'] = 'Like table does not exist yet';
        echo json_encode($response);
        exit;
    }
    
    // Check if user has liked this initiative
    $query = "SELECT id FROM initiative_likes WHERE user_id = ? AND initiative_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $initiative_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $response['success'] = true;
    $response['liked'] = ($result->num_rows > 0);
    $response['message'] = $response['liked'] ? 'User has liked this initiative' : 'User has not liked this initiative';
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response);
exit; 