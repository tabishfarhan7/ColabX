<?php
// Initialize the session
session_start();

// Check if user is logged in and is a government user
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'govt') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection and functions
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get and validate request data
$interest_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($interest_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid interest ID']);
    exit();
}

if ($action !== 'approve' && $action !== 'reject') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

// Verify ownership of the initiative associated with this interest
$verify_sql = "
    SELECT i.*, init.title as initiative_title, u.full_name as entrepreneur_name, u.email as entrepreneur_email
    FROM initiative_interests i 
    JOIN initiatives init ON i.initiative_id = init.id
    JOIN users u ON i.user_id = u.id
    WHERE i.id = ? AND init.user_id = ?
";

$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("ii", $interest_id, $_SESSION['user_id']);
$verify_stmt->execute();
$result = $verify_stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You do not have permission to update this interest expression']);
    exit();
}

$interest_data = $result->fetch_assoc();

// Update the status based on the action
$status = ($action === 'approve') ? 'approved' : 'rejected';
$update_sql = "UPDATE initiative_interests SET status = ?, updated_at = NOW() WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("si", $status, $interest_id);

if ($update_stmt->execute()) {
    // Log activity
    $activity_type = $action === 'approve' ? 'interest_approved' : 'interest_rejected';
    $activity_desc = $action === 'approve' 
        ? "Approved interest expression from {$interest_data['entrepreneur_name']} for initiative: {$interest_data['initiative_title']}" 
        : "Rejected interest expression from {$interest_data['entrepreneur_name']} for initiative: {$interest_data['initiative_title']}";
    
    log_activity($conn, $_SESSION['user_id'], $activity_type, $activity_desc);
    
    // TODO: Send email notification to entrepreneur
    // $to = $interest_data['entrepreneur_email'];
    // $subject = $action === 'approve' ? 'Your Interest in Initiative Has Been Approved' : 'Update on Your Interest in Initiative';
    // ... email code here
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}
?> 