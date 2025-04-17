<?php
// Include database connection and functions
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if idea ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid idea ID.";
    header("Location: dashboard/entrepreneur_dashboard.php");
    exit();
}

$ideaId = $_GET['id'];
$userId = $_SESSION['user_id'];

// Verify that the idea exists and belongs to this user
$stmt = $conn->prepare("SELECT id, title FROM ideas WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $ideaId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    $_SESSION['error_message'] = "You don't have permission to delete this idea.";
    header("Location: dashboard/entrepreneur_dashboard.php");
    exit();
}

$idea = $result->fetch_assoc();

// Begin transaction to ensure data integrity
$conn->begin_transaction();

try {
    // Delete any connections related to this idea
    $deleteConnections = $conn->prepare("DELETE FROM connections WHERE idea_id = ?");
    $deleteConnections->bind_param("i", $ideaId);
    $deleteConnections->execute();
    
    // Delete the idea
    $deleteIdea = $conn->prepare("DELETE FROM ideas WHERE id = ? AND user_id = ?");
    $deleteIdea->bind_param("ii", $ideaId, $userId);
    
    if ($deleteIdea->execute()) {
        // Record the activity
        record_activity($conn, $userId, 'delete_idea', "Deleted idea: " . $idea['title']);
        
        // Commit the transaction
        $conn->commit();
        
        $_SESSION['success_message'] = "Your idea has been deleted successfully.";
    } else {
        throw new Exception("Failed to delete the idea.");
    }
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
}

// Redirect back to dashboard
header("Location: dashboard/entrepreneur_dashboard.php");
exit();
?> 