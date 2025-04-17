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

// Get idea details
$stmt = $conn->prepare("SELECT i.*, u.full_name, u.user_type, u.company_name 
                       FROM ideas i 
                       JOIN users u ON i.user_id = u.id 
                       WHERE i.id = ?");
$stmt->bind_param("i", $ideaId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    $_SESSION['error_message'] = "Idea not found.";
    header("Location: dashboard/entrepreneur_dashboard.php");
    exit();
}

$idea = $result->fetch_assoc();

// Check if user has permission to view this idea
// Entrepreneurs can only view their own ideas
// Government users can view all ideas
if ($_SESSION['user_type'] == 'entrepreneur' && $idea['user_id'] != $_SESSION['user_id']) {
    $_SESSION['error_message'] = "You don't have permission to view this idea.";
    header("Location: dashboard/entrepreneur_dashboard.php");
    exit();
}

// Check if the user has already supported this idea
$supportStmt = $conn->prepare("SELECT * FROM idea_supports WHERE idea_id = ? AND user_id = ?");
$supportStmt->bind_param("ii", $ideaId, $_SESSION['user_id']);
$supportStmt->execute();
$supportResult = $supportStmt->get_result();
$isSupported = ($supportResult->num_rows > 0);

// Get total supports for this idea
$totalSupportsStmt = $conn->prepare("SELECT COUNT(*) as total FROM idea_supports WHERE idea_id = ?");
$totalSupportsStmt->bind_param("i", $ideaId);
$totalSupportsStmt->execute();
$totalSupportsResult = $totalSupportsStmt->get_result();
$totalSupports = $totalSupportsResult->fetch_assoc()['total'];

// Handle support action
if (isset($_POST['support_action'])) {
    if ($_POST['support_action'] == 'support') {
        // Add support
        $addSupportStmt = $conn->prepare("INSERT INTO idea_supports (idea_id, user_id, created_at) VALUES (?, ?, NOW())");
        $addSupportStmt->bind_param("ii", $ideaId, $_SESSION['user_id']);
        $addSupportStmt->execute();
        $isSupported = true;
        $totalSupports++;
    } else if ($_POST['support_action'] == 'unsupport') {
        // Remove support
        $removeSupportStmt = $conn->prepare("DELETE FROM idea_supports WHERE idea_id = ? AND user_id = ?");
        $removeSupportStmt->bind_param("ii", $ideaId, $_SESSION['user_id']);
        $removeSupportStmt->execute();
        $isSupported = false;
        $totalSupports--;
    }
}

// Increment view count if the viewer is different from the owner
if ($idea['user_id'] != $_SESSION['user_id']) {
    $updateViews = $conn->prepare("UPDATE ideas SET views = views + 1 WHERE id = ?");
    $updateViews->bind_param("i", $ideaId);
    $updateViews->execute();
    
    // Get updated view count
    $idea['views'] = $idea['views'] + 1;
}

// Get connections related to this idea
$connectionsQuery = $conn->prepare("SELECT c.*, i.title as initiative_title, i.department 
                                  FROM connections c 
                                  JOIN initiatives i ON c.initiative_id = i.id 
                                  WHERE c.idea_id = ?");
$connectionsQuery->bind_param("i", $ideaId);
$connectionsQuery->execute();
$connectionsResult = $connectionsQuery->get_result();
$connections = [];

while ($connection = $connectionsResult->fetch_assoc()) {
    $connections[] = $connection;
}

// Get user data
$userData = get_user_data($conn, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColabX - View Idea</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .idea-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .idea-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .idea-title {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .idea-meta {
            display: flex;
            gap: 1.5rem;
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .idea-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .idea-section {
            margin-bottom: 1.5rem;
        }
        
        .idea-section-title {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #495057;
        }
        
        .idea-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .support-form {
            margin: 0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-badge.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-badge.under-review {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-badge.approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-badge.rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .connections-container {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        
        .connection-card {
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        
        .connection-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .connection-title {
            font-weight: 500;
            margin: 0;
        }
        
        .connection-status {
            font-size: 0.85rem;
        }
        
        .connection-department {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .attachment-container {
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px dashed #ddd;
        }
        
        .attachment-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #007bff;
            text-decoration: none;
        }
        
        .attachment-link:hover {
            text-decoration: underline;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            background-color: #f8f9fa;
            border-radius: 5px;
            color: #6c757d;
        }
        
        .feedback-container {
            margin-top: 2rem;
            padding: 1.5rem;
            background-color: #f0f7ff;
            border-radius: 5px;
        }
        
        .feedback-title {
            margin-top: 0;
            color: #004085;
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
                <li><a href="dashboard/<?php echo $_SESSION['user_type'] === 'normal' ? 'user' : $_SESSION['user_type']; ?>_dashboard.php" class="link">Dashboard</a></li>
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
        <div class="idea-container">
            <div class="idea-header">
                <div>
                    <h2 class="idea-title"><?php echo htmlspecialchars($idea['title']); ?></h2>
                    <div class="idea-meta">
                        <div class="idea-meta-item">
                            <i class="fas fa-user"></i>
                            <span><?php echo htmlspecialchars($idea['full_name']); ?></span>
                            <?php if (!empty($idea['company_name'])): ?>
                                <span>(<?php echo htmlspecialchars($idea['company_name']); ?>)</span>
                            <?php endif; ?>
                        </div>
                        <div class="idea-meta-item">
                            <i class="fas fa-calendar"></i>
                            <span><?php echo date('M j, Y', strtotime($idea['created_at'])); ?></span>
                        </div>
                        <div class="idea-meta-item">
                            <i class="fas fa-folder"></i>
                            <span><?php echo htmlspecialchars($idea['sector']); ?></span>
                        </div>
                        <div class="idea-meta-item">
                            <i class="fas fa-eye"></i>
                            <span><?php echo $idea['views']; ?> views</span>
                        </div>
                        <div class="idea-meta-item">
                            <i class="fas fa-hands-helping"></i>
                            <span><?php echo $totalSupports; ?> supports</span>
                        </div>
                    </div>
                </div>
                <div class="idea-actions">
                    <span class="status-badge <?php echo strtolower(str_replace('_', '-', $idea['status'])); ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $idea['status'])); ?>
                    </span>
                    
                    <?php if ($idea['user_id'] != $_SESSION['user_id']): ?>
                        <form method="POST" class="support-form">
                            <?php if (!$isSupported): ?>
                                <input type="hidden" name="support_action" value="support">
                                <button type="submit" class="btn primary-btn">
                                    <i class="fas fa-hands-helping"></i> Support
                                </button>
                            <?php else: ?>
                                <input type="hidden" name="support_action" value="unsupport">
                                <button type="submit" class="btn secondary-btn">
                                    <i class="fas fa-hands-helping"></i> Supported
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($idea['user_id'] == $_SESSION['user_id']): ?>
                        <a href="edit_idea.php?id=<?php echo $idea['id']; ?>" class="btn secondary-btn">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    <?php endif; ?>
                    
                    <a href="dashboard/<?php echo $_SESSION['user_type'] === 'normal' ? 'user' : $_SESSION['user_type']; ?>_dashboard.php" class="btn secondary-btn">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            
            <div class="idea-section">
                <h3 class="idea-section-title">Description</h3>
                <p><?php echo nl2br(htmlspecialchars($idea['description'])); ?></p>
            </div>
            
            <?php if (!empty($idea['expected_impact'])): ?>
            <div class="idea-section">
                <h3 class="idea-section-title">Expected Impact</h3>
                <p><?php echo nl2br(htmlspecialchars($idea['expected_impact'])); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="idea-section grid-2">
                <?php if (!empty($idea['budget'])): ?>
                <div>
                    <h3 class="idea-section-title">Estimated Budget</h3>
                    <p><?php echo htmlspecialchars($idea['budget']); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($idea['timeline'])): ?>
                <div>
                    <h3 class="idea-section-title">Timeline</h3>
                    <p><?php echo htmlspecialchars($idea['timeline']); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($idea['technology_used'])): ?>
            <div class="idea-section">
                <h3 class="idea-section-title">Technology Used</h3>
                <p><?php echo nl2br(htmlspecialchars($idea['technology_used'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($idea['target_audience'])): ?>
            <div class="idea-section">
                <h3 class="idea-section-title">Target Audience</h3>
                <p><?php echo nl2br(htmlspecialchars($idea['target_audience'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($idea['attachments'])): ?>
            <div class="idea-section">
                <h3 class="idea-section-title">Attachments</h3>
                <div class="attachment-container">
                    <?php 
                    $fileExt = strtolower(pathinfo($idea['attachments'], PATHINFO_EXTENSION));
                    $fileIcon = 'fa-file';
                    
                    if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
                        $fileIcon = 'fa-file-image';
                    } elseif (in_array($fileExt, ['pdf'])) {
                        $fileIcon = 'fa-file-pdf';
                    } elseif (in_array($fileExt, ['doc', 'docx'])) {
                        $fileIcon = 'fa-file-word';
                    }
                    ?>
                    <a href="../uploads/idea_attachments/<?php echo $idea['attachments']; ?>" class="attachment-link" target="_blank">
                        <i class="fas <?php echo $fileIcon; ?>"></i>
                        <span>Download Attachment</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($idea['feedback'])): ?>
            <div class="feedback-container">
                <h3 class="feedback-title">Feedback from Government</h3>
                <p><?php echo nl2br(htmlspecialchars($idea['feedback'])); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Connections Section -->
            <?php if (!empty($connections)): ?>
            <div class="connections-container">
                <h3>Connected Initiatives</h3>
                <?php foreach ($connections as $connection): ?>
                <div class="connection-card">
                    <div class="connection-header">
                        <h4 class="connection-title"><?php echo htmlspecialchars($connection['initiative_title']); ?></h4>
                        <span class="connection-status"><?php echo ucfirst($connection['status']); ?></span>
                    </div>
                    <div class="connection-department">
                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($connection['department']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php elseif ($idea['user_id'] == $_SESSION['user_id']): ?>
            <div class="connections-container">
                <h3>Connected Initiatives</h3>
                <div class="empty-state">
                    <i class="fas fa-handshake"></i>
                    <p>This idea has no connections with government initiatives yet.</p>
                </div>
            </div>
            <?php endif; ?>
            
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