<?php
// Include database connection and functions
require_once('db_connect.php');
require_once('functions.php');

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in',
        'activities' => []
    ]);
    exit();
}

// Get recent activities
$activities = get_user_activities($conn, $_SESSION['user_id'], 5);

// Return activities as JSON
echo json_encode([
    'success' => true,
    'message' => 'Activities retrieved successfully',
    'activities' => $activities
]); 