<?php
// Include database connection and functions
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'govt') {
    header("Location: ../pages/login.php");
    exit();
}

// Check if initiative ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No initiative ID provided.";
    header("Location: ../pages/dashboard/govt_dashboard.php");
    exit();
}

$initiative_id = (int)$_GET['id'];

// Check if the initiative belongs to the current user
$check_sql = "SELECT * FROM initiatives WHERE id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $initiative_id, $_SESSION['user_id']);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "You do not have permission to delete this initiative.";
    header("Location: ../pages/dashboard/govt_dashboard.php");
    exit();
}

// Delete the initiative
$delete_sql = "DELETE FROM initiatives WHERE id = ? AND user_id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("ii", $initiative_id, $_SESSION['user_id']);

if ($delete_stmt->execute()) {
    // Log activity
    $activity = "Deleted initiative #" . $initiative_id;
    log_activity($conn, $_SESSION['user_id'], 'initiative_deleted', $activity);
    
    $_SESSION['success_message'] = "Initiative deleted successfully.";
} else {
    $_SESSION['error_message'] = "Error deleting initiative: " . $conn->error;
}

// Redirect back to dashboard
header("Location: ../pages/dashboard/govt_dashboard.php");
exit();
?> 