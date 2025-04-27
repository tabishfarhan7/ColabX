<?php
// Include database connection and functions
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'govt') {
    header("Location: ../pages/login.php");
    exit();
}

// Initialize response message
$response = [];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $title = trim($_POST['initiativeTitle']);
    $department = trim($_POST['initiativeDepartment']);
    $description = trim($_POST['initiativeDescription']);
    $start_date = $_POST['startDate'];
    $end_date = $_POST['endDate'];
    $objectives = trim($_POST['initiativeObjectives']);
    $budget = !empty($_POST['initiativeBudget']) ? $_POST['initiativeBudget'] : 0;
    
    // Validate required fields
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Initiative title is required";
    }
    
    if (empty($department)) {
        $errors[] = "Department is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    if (empty($start_date)) {
        $errors[] = "Start date is required";
    }
    
    if (empty($end_date)) {
        $errors[] = "End date is required";
    }
    
    if (empty($objectives)) {
        $errors[] = "Objectives are required";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        $sql = "INSERT INTO initiatives (user_id, title, description, department, status, start_date, end_date, objectives, budget) 
                VALUES (?, ?, ?, ?, 'active', ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssd", 
            $_SESSION['user_id'], 
            $title,
            $description,
            $department,
            $start_date,
            $end_date,
            $objectives,
            $budget
        );
        
        // Execute the statement
        if ($stmt->execute()) {
            // Log activity
            $activity = "Created new initiative: " . $title;
            log_activity($conn, $_SESSION['user_id'], 'initiative_created', $activity);
            
            // Set success message
            $_SESSION['success_message'] = "Initiative created successfully!";
            
            // Redirect back to dashboard
            header("Location: ../pages/dashboard/govt_dashboard.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error: " . $stmt->error;
            header("Location: ../pages/dashboard/govt_dashboard.php");
            exit();
        }
    } else {
        // Return error message
        $_SESSION['error_message'] = "Please fix the following errors: " . implode(", ", $errors);
        header("Location: ../pages/dashboard/govt_dashboard.php");
        exit();
    }
} else {
    // Not a POST request
    header("Location: ../pages/dashboard/govt_dashboard.php");
    exit();
}
?> 