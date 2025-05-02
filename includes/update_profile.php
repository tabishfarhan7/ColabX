<?php
// Include database connection and functions
require_once('db_connect.php');
require_once('functions.php');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize response array
$response = array(
    'success' => false,
    'message' => '',
    'updatedName' => '',
    'reloadPage' => true  // Set to true by default
);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to update your profile.';
    echo json_encode($response);
    exit();
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user ID from the form
    $userId = intval($_POST['user_id']);
    
    // Verify user is updating their own profile
    if ($userId != $_SESSION['user_id']) {
        $response['message'] = 'You can only update your own profile.';
        echo json_encode($response);
        exit();
    }
    
    // Sanitize inputs
    $fullName = sanitize_input($_POST['fullName']);
    $email = sanitize_input($_POST['email']);
    
    // Store original values for comparison
    $originalName = isset($_SESSION['name']) ? $_SESSION['name'] : '';
    $originalEmail = $_SESSION['email'];
    
    // Additional profile fields
    $interests = isset($_POST['interests']) ? sanitize_input($_POST['interests']) : null;
    $bio = isset($_POST['bio']) ? sanitize_input($_POST['bio']) : null;
    
    // Validate inputs
    if (empty($fullName)) {
        $response['message'] = 'Full name is required.';
        echo json_encode($response);
        exit();
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'A valid email address is required.';
        echo json_encode($response);
        exit();
    }
    
    // Check if email already exists (for a different user)
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response['message'] = 'Email address is already in use by another account.';
        echo json_encode($response);
        exit();
    }
    
    // Handle profile picture upload
    $profilePicPath = '';
    $uploadProfilePic = false;
    
    if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] == 0) {
        // Define allowed file types
        $allowed = array('jpg', 'jpeg', 'png', 'gif');
        
        // Get file extension
        $filename = $_FILES['profilePicture']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Check if file type is allowed
        if (!in_array($ext, $allowed)) {
            $response['message'] = 'Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.';
            echo json_encode($response);
            exit();
        }
        
        // Check file size (max 5MB)
        if ($_FILES['profilePicture']['size'] > 5242880) {
            $response['message'] = 'File is too large. Maximum file size is 5MB.';
            echo json_encode($response);
            exit();
        }
        
        // Create unique filename
        $newFilename = 'user_' . $userId . '_' . time() . '.' . $ext;
        $uploadDir = '../uploads/profile_pics/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $uploadPath = $uploadDir . $newFilename;
        
        // Try to upload the file
        if (move_uploaded_file($_FILES['profilePicture']['tmp_name'], $uploadPath)) {
            // Store the path relative to the website root for proper display
            $profilePicPath = 'uploads/profile_pics/' . $newFilename;
            $uploadProfilePic = true;
            $response['reloadPage'] = true; // Will reload page to show new image
        } else {
            $response['message'] = 'Failed to upload profile picture. Please try again.';
            echo json_encode($response);
            exit();
        }
    }
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Check which columns exist in the users table
        $checkInterests = $conn->query("SHOW COLUMNS FROM users LIKE 'interests'");
        $interestsExists = $checkInterests->num_rows > 0;
        
        $checkBio = $conn->query("SHOW COLUMNS FROM users LIKE 'bio'");
        $bioExists = $checkBio->num_rows > 0;
        
        $checkProfilePic = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
        $profilePicExists = $checkProfilePic->num_rows > 0;
        
        // Create query parts based on which columns exist
        $setParts = ["full_name = ?", "email = ?"];
        $paramTypes = "ss";
        $paramValues = [$fullName, $email];
        
        if ($interestsExists) {
            $setParts[] = "interests = ?";
            $paramTypes .= "s";
            $paramValues[] = $interests;
        }
        
        if ($bioExists) {
            $setParts[] = "bio = ?";
            $paramTypes .= "s";
            $paramValues[] = $bio;
        }
        
        if ($profilePicExists && $uploadProfilePic) {
            $setParts[] = "profile_pic = ?";
            $paramTypes .= "s";
            $paramValues[] = $profilePicPath;
        }
        
        // Add user ID to parameters
        $paramTypes .= "i";
        $paramValues[] = $userId;
        
        // Build the final query
        $query = "UPDATE users SET " . implode(", ", $setParts) . " WHERE id = ?";
        
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        $stmt->bind_param($paramTypes, ...$paramValues);
        
        if ($stmt->execute()) {
            // Commit transaction
            $conn->commit();
            
            // Update session variables
            $_SESSION['email'] = $email;
            
            // Record activities based on what was updated
            $activitiesRecorded = false;
            
            // Record profile photo update if applicable
            if ($uploadProfilePic && $profilePicExists) {
                record_activity($conn, $userId, 'photo_update', 'You updated your profile photo');
                $activitiesRecorded = true;
            }
            
            // Record profile info update
            if ($fullName != $originalName || isset($interests) || isset($bio)) {
                record_activity($conn, $userId, 'profile_update', 'You updated your profile information');
                $activitiesRecorded = true;
            }
            
            // Set success response
            $response['success'] = true;
            $response['message'] = 'Profile updated successfully.';
            $response['updatedName'] = $fullName;
            
            // If profile was updated, update session name
            if ($fullName != $originalName) {
                $_SESSION['name'] = $fullName;
            }
            
            // Notify about limited update if any columns are missing
            if (!($interestsExists && $bioExists && $profilePicExists)) {
                $response['message'] .= ' Note: Some profile features are limited. Please run the database update script for full functionality.';
                $response['limitedUpdate'] = true;
            }
        } else {
            // Rollback transaction
            $conn->rollback();
            $response['message'] = 'Failed to update profile: ' . $conn->error;
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $response['message'] = 'An error occurred: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Return JSON response
echo json_encode($response);
exit();
?> 