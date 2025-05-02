<?php
// Initialize the session
session_start();

// Include database connection and functions
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a government user
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'govt') {
    header("Location: login.php");
    exit();
}

// Check if initiative ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "No initiative ID provided.";
    header("Location: dashboard/govt_dashboard.php");
    exit();
}

$initiative_id = (int)$_GET['id'];

// Get initiative data
$sql = "SELECT * FROM initiatives WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $initiative_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Initiative not found or you don't have permission to edit it.";
    header("Location: dashboard/govt_dashboard.php");
    exit();
}

$initiative = $result->fetch_assoc();

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
    $status = $_POST['status'];
    
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
    
    // If no errors, update database
    if (empty($errors)) {
        $sql = "UPDATE initiatives SET 
                title = ?, 
                description = ?, 
                department = ?, 
                status = ?, 
                start_date = ?, 
                end_date = ?, 
                objectives = ?, 
                budget = ? 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssdii", 
            $title,
            $description,
            $department,
            $status,
            $start_date,
            $end_date,
            $objectives,
            $budget,
            $initiative_id,
            $_SESSION['user_id']
        );
        
        // Execute the statement
        if ($stmt->execute()) {
            // Log activity
            $activity = "Updated initiative: " . $title;
            log_activity($conn, $_SESSION['user_id'], 'initiative_updated', $activity);
            
            $_SESSION['success_message'] = "Initiative updated successfully!";
            header("Location: dashboard/govt_dashboard.php");
            exit();
        } else {
            $error_message = "Error updating initiative: " . $stmt->error;
        }
    } else {
        $error_message = "Please fix the following errors: " . implode(", ", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Initiative - ColabX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .container {
            max-width: 800px;
            margin: 120px auto 50px;
            padding: 20px;
        }
        
        .page-header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .dashboard-form {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-group.half {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .action-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .primary-btn {
            background-color: #FFE535;
            color: #333;
        }
        
        .secondary-btn {
            background-color: #e9ecef;
            color: #333;
        }
        
        .primary-btn:hover {
            background-color: #FFD700;
        }
        
        .secondary-btn:hover {
            background-color: #dee2e6;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 5px;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
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
                <li><a href="dashboard/govt_dashboard.php" class="link active">Dashboard</a></li>
            </ul>
            <div class="user-actions">
                <form action="logout.php" method="POST">
                    <button type="submit" class="btn sign-in">Logout</button>
                </form>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="page-header">
            <h1>Edit Initiative</h1>
            <p>Update your government initiative details</p>
        </div>
        
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <form class="dashboard-form" method="POST">
            <div class="form-group">
                <label for="initiativeTitle">Initiative Title</label>
                <input type="text" id="initiativeTitle" name="initiativeTitle" value="<?php echo htmlspecialchars($initiative['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="initiativeDepartment">Department</label>
                <select id="initiativeDepartment" name="initiativeDepartment" required>
                    <option value="">Select Department</option>
                    <option value="Urban Development" <?php echo ($initiative['department'] == 'Urban Development') ? 'selected' : ''; ?>>Urban Development</option>
                    <option value="Energy" <?php echo ($initiative['department'] == 'Energy') ? 'selected' : ''; ?>>Energy</option>
                    <option value="IT & Communication" <?php echo ($initiative['department'] == 'IT & Communication') ? 'selected' : ''; ?>>IT & Communication</option>
                    <option value="Healthcare" <?php echo ($initiative['department'] == 'Healthcare') ? 'selected' : ''; ?>>Healthcare</option>
                    <option value="Agriculture" <?php echo ($initiative['department'] == 'Agriculture') ? 'selected' : ''; ?>>Agriculture</option>
                    <option value="Education" <?php echo ($initiative['department'] == 'Education') ? 'selected' : ''; ?>>Education</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="initiativeDescription">Description</label>
                <textarea id="initiativeDescription" name="initiativeDescription" rows="4" required><?php echo htmlspecialchars($initiative['description']); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group half">
                    <label for="startDate">Start Date</label>
                    <input type="date" id="startDate" name="startDate" value="<?php echo htmlspecialchars($initiative['start_date']); ?>" required>
                </div>
                
                <div class="form-group half">
                    <label for="endDate">End Date</label>
                    <input type="date" id="endDate" name="endDate" value="<?php echo htmlspecialchars($initiative['end_date']); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="initiativeObjectives">Objectives</label>
                <textarea id="initiativeObjectives" name="initiativeObjectives" rows="3" required><?php echo htmlspecialchars($initiative['objectives']); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group half">
                    <label for="initiativeBudget">Budget (USD)</label>
                    <input type="number" id="initiativeBudget" name="initiativeBudget" value="<?php echo htmlspecialchars($initiative['budget']); ?>" min="0" step="1000">
                </div>
                
                <div class="form-group half">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="draft" <?php echo ($initiative['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                        <option value="active" <?php echo ($initiative['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="closed" <?php echo ($initiative['status'] == 'closed') ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="action-btn primary-btn">Save Changes</button>
                <a href="dashboard/govt_dashboard.php" class="action-btn secondary-btn">Cancel</a>
            </div>
        </form>
    </div>
    
    <script>
        // Basic form validation
        document.querySelector('.dashboard-form').addEventListener('submit', function(e) {
            const title = document.getElementById('initiativeTitle').value.trim();
            const department = document.getElementById('initiativeDepartment').value;
            const description = document.getElementById('initiativeDescription').value.trim();
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (!title || !department || !description || !startDate || !endDate) {
                e.preventDefault();
                alert('Please fill all required fields');
            }
            
            // Validate dates
            if (new Date(endDate) < new Date(startDate)) {
                e.preventDefault();
                alert('End date cannot be earlier than start date');
            }
        });
    </script>
</body>
</html> 