<?php
// actions/like_initiative.php - Handles likes for government initiatives

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
    $response['message'] = 'You must be logged in to like government initiatives';
    echo json_encode($response);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Get initiative ID from request
$initiative_id = isset($_POST['initiative_id']) ? intval($_POST['initiative_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($initiative_id <= 0) {
    $response['message'] = 'Invalid initiative ID';
    echo json_encode($response);
    exit;
}

if ($action !== 'like' && $action !== 'unlike') {
    $response['message'] = 'Invalid action';
    echo json_encode($response);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

try {
    // Check if initiative exists in the database
    $initiative_query = "SELECT i.id, i.title, i.department 
                       FROM initiatives i 
                       WHERE i.id = ?";
    $stmt = $conn->prepare($initiative_query);
    $stmt->bind_param("i", $initiative_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if initiative exists
    if ($result->num_rows === 0) {
        // Check if it's a mock initiative (ID 1000+)
        if ($initiative_id >= 1000) {
            // For mock initiatives, create a table entry first
            $mock_title = "";
            $mock_dept = "";
            
            switch ($initiative_id) {
                case 1000:
                    $mock_title = "Smart City Development Program";
                    $mock_dept = "Urban Development";
                    break;
                case 1001:
                    $mock_title = "Clean Energy Innovation Challenge";
                    $mock_dept = "Energy";
                    break;
                case 1002:
                    $mock_title = "Digital Governance Transformation";
                    $mock_dept = "IT & Communication";
                    break;
                case 1003:
                    $mock_title = "Healthcare Innovation Program";
                    $mock_dept = "Health";
                    break;
                case 1004:
                    $mock_title = "Agricultural Modernization Initiative";
                    $mock_dept = "Agriculture";
                    break;
                default:
                    $response['message'] = 'Initiative not found';
                    echo json_encode($response);
                    exit;
            }
        } else {
            $response['message'] = 'Initiative not found';
            echo json_encode($response);
            exit;
        }
    } else {
        $initiative = $result->fetch_assoc();
    }
    
    // Check if initiative_likes table exists, if not create it
    $check_table = $conn->query("SHOW TABLES LIKE 'initiative_likes'");
    if ($check_table->num_rows === 0) {
        $create_table = "CREATE TABLE initiative_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) UNSIGNED NOT NULL,
            initiative_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_like (user_id, initiative_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $conn->query($create_table);
    }
    
    // Check if user already liked this initiative
    $check_query = "SELECT id FROM initiative_likes 
                   WHERE user_id = ? AND initiative_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $user_id, $initiative_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $already_liked = $result->num_rows > 0;
    
    if ($action === 'like' && !$already_liked) {
        // Create new like entry
        $insert_query = "INSERT INTO initiative_likes (user_id, initiative_id, created_at)
                        VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ii", $user_id, $initiative_id);
        $stmt->execute();
        
        $response['success'] = true;
        $response['message'] = 'Initiative liked successfully';
        $response['action'] = 'liked';
    } else if ($action === 'unlike' && $already_liked) {
        // Remove the like
        $delete_query = "DELETE FROM initiative_likes 
                        WHERE user_id = ? AND initiative_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $user_id, $initiative_id);
        $stmt->execute();
        
        $response['success'] = true;
        $response['message'] = 'Initiative like removed';
        $response['action'] = 'unliked';
    } else {
        $response['success'] = true;
        $response['message'] = 'No change needed';
        $response['action'] = $already_liked ? 'already_liked' : 'not_liked';
    }
    
    // Get updated like count
    $count_query = "SELECT COUNT(*) as count FROM initiative_likes WHERE initiative_id = ?";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("i", $initiative_id);
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