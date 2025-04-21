<?php
// Initialize the session if not already started
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include database connection
require_once 'db_connect.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $initiative_id = $_POST['initiative_id'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    $action = $_POST['action'] ?? 'add'; // 'add' or 'remove'
    $proposal = $_POST['proposal'] ?? '';
    $idea_id = $_POST['idea_id'] ?? '';

    // Validate the data
    if (empty($initiative_id) || empty($user_id)) {
        $_SESSION['error'] = "Required fields are missing";
        header("Location: ../dashboard/entrepreneur_dashboard.php");
        exit();
    }

    // Sanitize the data
    $initiative_id = filter_var($initiative_id, FILTER_SANITIZE_NUMBER_INT);
    $user_id = filter_var($user_id, FILTER_SANITIZE_NUMBER_INT);
    $proposal = htmlspecialchars($proposal);
    $idea_id = !empty($idea_id) ? filter_var($idea_id, FILTER_SANITIZE_NUMBER_INT) : null;

    try {
        if ($action === 'remove') {
            // Remove interest
            $stmt = $conn->prepare("DELETE FROM initiative_interests WHERE initiative_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $initiative_id, $user_id);
            
            if ($stmt->execute()) {
                // Also remove the activity record
                $activity_stmt = $conn->prepare("DELETE FROM user_activities WHERE user_id = ? AND reference_id = ? AND activity_type = 'initiative_interest'");
                $activity_stmt->bind_param("ii", $user_id, $initiative_id);
                $activity_stmt->execute();
                
                $_SESSION['success'] = "Your interest has been removed successfully!";
            } else {
                $_SESSION['error'] = "Failed to remove your interest. Please try again.";
            }
        } else {
            // Add interest
            if (empty($proposal)) {
                $_SESSION['error'] = "Proposal is required when expressing interest";
                header("Location: ../dashboard/entrepreneur_dashboard.php");
                exit();
            }

            $stmt = $conn->prepare("
                INSERT INTO initiative_interests 
                (initiative_id, user_id, proposal, idea_id, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");

            $stmt->bind_param("iisi", $initiative_id, $user_id, $proposal, $idea_id);

            if ($stmt->execute()) {
                // Success - also add an activity record
                $activity_stmt = $conn->prepare("
                    INSERT INTO user_activities 
                    (user_id, activity_type, reference_id, activity_data, created_at) 
                    VALUES (?, 'initiative_interest', ?, ?, NOW())
                ");
                
                $activity_data = json_encode([
                    'initiative_id' => $initiative_id,
                    'proposal' => substr($proposal, 0, 100) . (strlen($proposal) > 100 ? '...' : ''),
                    'idea_id' => $idea_id
                ]);
                
                $activity_stmt->bind_param("iis", $user_id, $initiative_id, $activity_data);
                $activity_stmt->execute();
                
                $_SESSION['success'] = "Your interest has been submitted successfully!";
            } else {
                $_SESSION['error'] = "Failed to submit your interest. Please try again.";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    
    // Redirect back to the dashboard
    header("Location: ../dashboard/entrepreneur_dashboard.php");
    exit();
} else {
    // Not a POST request, redirect to dashboard
    header("Location: ../dashboard/entrepreneur_dashboard.php");
    exit();
}
?> 