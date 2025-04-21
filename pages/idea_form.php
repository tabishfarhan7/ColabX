<?php
// Include database connection and functions
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Determine if this is an edit or a new submission
$editMode = false;
$idea = null;
$pageTitle = "Submit a New Idea";
$formAction = "idea_form.php";
$submitButtonText = "Submit Idea";

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $editMode = true;
    $ideaId = $_GET['id'];
    $pageTitle = "Edit Your Idea";
    $formAction = "idea_form.php?id=$ideaId";
    $submitButtonText = "Update Idea";
    
    // Fetch the idea data
    $stmt = $conn->prepare("SELECT * FROM ideas WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $ideaId, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $idea = $result->fetch_assoc();
    } else {
        // Idea not found or doesn't belong to this user
        header("Location: dashboard/entrepreneur_dashboard.php");
        exit();
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $sector = trim($_POST['sector']);
    $budget = !empty($_POST['budget']) ? trim($_POST['budget']) : null;
    $timeline = !empty($_POST['timeline']) ? trim($_POST['timeline']) : null;
    $technology = !empty($_POST['technology']) ? trim($_POST['technology']) : null;
    $target_audience = !empty($_POST['target_audience']) ? trim($_POST['target_audience']) : null;
    $expected_impact = !empty($_POST['expected_impact']) ? trim($_POST['expected_impact']) : null;
    
    // Validate required fields
    $errors = [];
    if (empty($title)) $errors[] = "Title is required.";
    if (empty($description)) $errors[] = "Description is required.";
    if (empty($sector)) $errors[] = "Sector is required.";
    
    // Handle file upload
    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $filename = $_FILES['attachment']['name'];
        $fileTmpName = $_FILES['attachment']['tmp_name'];
        $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($fileExt, $allowed)) {
            // Create a unique filename
            $newFilename = uniqid() . '.' . $fileExt;
            $uploadPath = '../uploads/idea_attachments/' . $newFilename;
            
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                $attachment = $newFilename;
            } else {
                $errors[] = "Failed to upload file.";
            }
        } else {
            $errors[] = "Invalid file type. Allowed types: PDF, DOC, DOCX, JPG, JPEG, PNG.";
        }
    }
    
    // If no errors, proceed with database operation
    if (empty($errors)) {
        // Prepare SQL statement based on edit mode
        if ($editMode) {
            // Update existing idea
            $sql = "UPDATE ideas SET 
                    title = ?,
                    description = ?,
                    sector = ?,
                    budget = ?,
                    timeline = ?,
                    technology_used = ?,
                    target_audience = ?,
                    expected_impact = ?";
            
            // Only update attachment if a new one was uploaded
            if ($attachment) {
                $sql .= ", attachments = ?";
            }
            
            $sql .= " WHERE id = ? AND user_id = ?";
            
            $stmt = $conn->prepare($sql);
            
            if ($attachment) {
                $stmt->bind_param("sssssssssii", $title, $description, $sector, $budget, $timeline, $technology, 
                                 $target_audience, $expected_impact, $attachment, $ideaId, $_SESSION['user_id']);
            } else {
                $stmt->bind_param("ssssssssii", $title, $description, $sector, $budget, $timeline, $technology, 
                                 $target_audience, $expected_impact, $ideaId, $_SESSION['user_id']);
            }
            
            $successMessage = "Your idea has been updated successfully!";
        } else {
            // Insert new idea
            $sql = "INSERT INTO ideas (user_id, title, description, sector, budget, timeline, technology_used, target_audience, expected_impact, attachments, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssssss", $_SESSION['user_id'], $title, $description, $sector, $budget, $timeline, 
                             $technology, $target_audience, $expected_impact, $attachment);
            
            $successMessage = "Your idea has been submitted successfully!";
        }
        
        if ($stmt->execute()) {
            // Record activity
            $activityType = $editMode ? 'update_idea' : 'submit_idea';
            $activityDesc = $editMode ? "Updated idea: $title" : "Submitted new idea: $title";
            record_activity($conn, $_SESSION['user_id'], $activityType, $activityDesc);
            
            // Redirect to dashboard with success message
            $_SESSION['success_message'] = $successMessage;
            header("Location: dashboard/entrepreneur_dashboard.php");
            exit();
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
    }
}

// Get user data
$userData = get_user_data($conn, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColabX - <?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        textarea.form-control {
            min-height: 150px;
        }
        
        .help-text {
            display: block;
            margin-top: 0.3rem;
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .form-actions {
            margin-top: 2rem;
            display: flex;
            justify-content: space-between;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
        }
        
        .required-field::after {
            content: "*";
            color: #dc3545;
            margin-left: 0.2rem;
        }
        
        .form-section {
            border-bottom: 1px solid #eee;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-section-title {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #495057;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="navbar flex">
            <a href="../index.php" class="logo">
                Colab<span>X</span>
            </a>
            <ul class="navlist flex">
                <li><a href="../index.php" class="link">Home</a></li>
                <li><a href="colab.php" class="link">Project</a></li>
                <li><a href="innovation.php" class="link">Innovation</a></li>
                <li><a href="about.php" class="link">About Us</a></li>
                <li><a href="dashboard/entrepreneur_dashboard.php" class="link">Dashboard</a></li>
            </ul>
            <div class="user-actions">
                <div class="notification-badge" data-count="0">
                    <i class="fas fa-bell"></i>
                </div>
                <form action="logout.php" method="POST">
                    <button type="submit" class="btn sign-in">Logout</button>
                </form>
                
                <!-- Language Selector -->
                <div class="language-dropdown">
                    <button class="lang-btn">
                        <i class="fa-solid fa-globe"></i> EN
                    </button>
                    <ul class="language-list">
                        <li>English</li>
                        <li>አማርኛ</li>
                        <li>العربية</li>
                        <li>বাংলা</li>
                        <li>简体中文</li>
                        <li>Français</li>
                        <li>हिंदी</li>
                        <li>Bahasa Indonesia</li>
                        <li>Português</li>
                        <li>Español</li>
                        <li>Kiswahili</li>
                        <li>ไทย</li>
                        <li>اردو</li>
                        <li>Tiếng Việt</li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <div class="dashboard-container">
        <div class="form-container">
            <div class="form-header">
                <h2><?php echo $pageTitle; ?></h2>
                <p>Share your innovative idea with government organizations and potential collaborators.</p>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form action="<?php echo $formAction; ?>" method="post" enctype="multipart/form-data">
                <div class="form-section">
                    <h3 class="form-section-title">Basic Information</h3>
                    <div class="form-group">
                        <label for="title" class="required-field">Title</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               value="<?php echo isset($idea) ? htmlspecialchars($idea['title']) : ''; ?>" required>
                        <span class="help-text">A concise title that summarizes your idea</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="required-field">Description</label>
                        <textarea id="description" name="description" class="form-control" required><?php echo isset($idea) ? htmlspecialchars($idea['description']) : ''; ?></textarea>
                        <span class="help-text">Detailed explanation of your idea, including its purpose and benefits</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="sector" class="required-field">Sector</label>
                        <select id="sector" name="sector" class="form-control" required>
                            <option value="">Select a sector</option>
                            <option value="Agriculture" <?php echo (isset($idea) && $idea['sector'] == 'Agriculture') ? 'selected' : ''; ?>>Agriculture</option>
                            <option value="Education" <?php echo (isset($idea) && $idea['sector'] == 'Education') ? 'selected' : ''; ?>>Education</option>
                            <option value="Energy" <?php echo (isset($idea) && $idea['sector'] == 'Energy') ? 'selected' : ''; ?>>Energy</option>
                            <option value="Environment" <?php echo (isset($idea) && $idea['sector'] == 'Environment') ? 'selected' : ''; ?>>Environment</option>
                            <option value="Healthcare" <?php echo (isset($idea) && $idea['sector'] == 'Healthcare') ? 'selected' : ''; ?>>Healthcare</option>
                            <option value="Infrastructure" <?php echo (isset($idea) && $idea['sector'] == 'Infrastructure') ? 'selected' : ''; ?>>Infrastructure</option>
                            <option value="Technology" <?php echo (isset($idea) && $idea['sector'] == 'Technology') ? 'selected' : ''; ?>>Technology</option>
                            <option value="Transportation" <?php echo (isset($idea) && $idea['sector'] == 'Transportation') ? 'selected' : ''; ?>>Transportation</option>
                            <option value="Other" <?php echo (isset($idea) && $idea['sector'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="form-section-title">Additional Details</h3>
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="budget">Estimated Budget</label>
                            <input type="number" id="budget" name="budget" class="form-control" step="0.01" min="0"
                                   value="<?php echo isset($idea['budget']) ? htmlspecialchars($idea['budget']) : ''; ?>">
                            <span class="help-text">Approximate budget required for your idea (optional)</span>
                        </div>
                        
                        <div class="form-group">
                            <label for="timeline">Timeline</label>
                            <input type="text" id="timeline" name="timeline" class="form-control"
                                   value="<?php echo isset($idea['timeline']) ? htmlspecialchars($idea['timeline']) : ''; ?>">
                            <span class="help-text">Estimated time required to implement (e.g., 6 months, 1-2 years)</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="technology">Technology Used</label>
                        <textarea id="technology" name="technology" class="form-control"><?php echo isset($idea['technology_used']) ? htmlspecialchars($idea['technology_used']) : ''; ?></textarea>
                        <span class="help-text">Technologies, tools, or methodologies you plan to use</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="target_audience">Target Audience</label>
                        <textarea id="target_audience" name="target_audience" class="form-control"><?php echo isset($idea['target_audience']) ? htmlspecialchars($idea['target_audience']) : ''; ?></textarea>
                        <span class="help-text">Who will benefit from your idea?</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="expected_impact">Expected Impact</label>
                        <textarea id="expected_impact" name="expected_impact" class="form-control"><?php echo isset($idea['expected_impact']) ? htmlspecialchars($idea['expected_impact']) : ''; ?></textarea>
                        <span class="help-text">Describe the potential impact of your idea on the community or society</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="attachment">Attachment</label>
                        <input type="file" id="attachment" name="attachment" class="form-control">
                        <span class="help-text">Upload supporting documents (PDF, DOC, DOCX) or images (JPG, PNG) (max 10MB)</span>
                        <?php if (isset($idea['attachments']) && !empty($idea['attachments'])): ?>
                            <p>Current attachment: <?php echo htmlspecialchars($idea['attachments']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="dashboard/entrepreneur_dashboard.php" class="btn secondary-btn">Cancel</a>
                    <button type="submit" class="btn primary-btn"><?php echo $submitButtonText; ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Show/hide language dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const langBtn = document.querySelector('.lang-btn');
            const langDropdown = document.querySelector('.language-dropdown');
            
            langBtn.addEventListener('click', function() {
                langDropdown.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!langDropdown.contains(event.target)) {
                    langDropdown.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html> 