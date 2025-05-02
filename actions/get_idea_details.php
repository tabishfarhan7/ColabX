<?php
// actions/get_idea_details.php - Fetches detailed information about an idea

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
    'idea' => null
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Get idea ID from request
$idea_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idea_id <= 0) {
    $response['message'] = 'Invalid idea ID';
    echo json_encode($response);
    exit;
}

try {
    // Query for idea details
    $sql = "SELECT i.*, u.full_name, u.company_name, u.email 
            FROM ideas i 
            JOIN users u ON i.user_id = u.id 
            WHERE i.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idea_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = 'Idea not found';
        echo json_encode($response);
        exit;
    }
    
    // Process and sanitize idea data
    $idea = $result->fetch_assoc();
    
    // Format and sanitize data for JSON output
    $response['idea'] = [
        'id' => $idea['id'],
        'title' => htmlspecialchars($idea['title']),
        'description' => htmlspecialchars($idea['description']),
        'sector' => htmlspecialchars($idea['sector']),
        'status' => ucwords(str_replace('_', ' ', $idea['status'])),
        'entrepreneur' => htmlspecialchars($idea['full_name']),
        'company' => htmlspecialchars($idea['company_name'] ?: 'Individual Entrepreneur'),
        'created_at' => date('Y-m-d', strtotime($idea['created_at'])),
        'updated_at' => date('Y-m-d', strtotime($idea['updated_at'])),
        'budget' => htmlspecialchars($idea['budget'] ?? ''),
        'timeline' => htmlspecialchars($idea['timeline'] ?? ''),
        'technology_used' => htmlspecialchars($idea['technology_used'] ?? ''),
        'target_audience' => htmlspecialchars($idea['target_audience'] ?? ''),
        'expected_impact' => htmlspecialchars($idea['expected_impact'] ?? ''),
        'contact_email' => htmlspecialchars($idea['email'] ?? '')
    ];
    
    $response['success'] = true;
    $response['message'] = 'Idea details retrieved successfully';
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
exit; 