<?php
session_start();
require_once "../includes/db_connect.php";

// Function to get user data
function getUserData($user_id, $conn) {
    $stmt = $conn->prepare("SELECT id, full_name, profile_pic FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    
    // Ensure profile_pic contains the full path if it exists
    if (isset($userData['profile_pic']) && !empty($userData['profile_pic'])) {
        // The path is already stored correctly in the database
        // We just need to return it as is
    } else {
        $userData['profile_pic'] = ''; // Set empty if not available
    }
    
    return $userData;
}

// Handle new post submission
if (isset($_POST['submit_post'])) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $title = $_POST['post_title'];
        $content = $_POST['post_content'];
        $media_url = "";
        $media_type = null;
        
        // Handle file upload
        if (isset($_FILES['post_media']) && $_FILES['post_media']['error'] == 0) {
            $allowed_extensions = array("jpg", "jpeg", "png", "gif", "mp4", "pdf", "doc", "docx");
            $file_extension = pathinfo($_FILES["post_media"]["name"], PATHINFO_EXTENSION);
            
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                $new_filename = uniqid() . "." . $file_extension;
                $upload_path = "../uploads/innovation_posts/" . $new_filename;
                
                if (move_uploaded_file($_FILES["post_media"]["tmp_name"], $upload_path)) {
                    $media_url = $new_filename;
                    
                    // Set media type
                    if (in_array(strtolower($file_extension), ["jpg", "jpeg", "png", "gif"])) {
                        $media_type = "image";
                    } elseif (strtolower($file_extension) == "mp4") {
                        $media_type = "video";
                    } else {
                        $media_type = "document";
                    }
                }
            }
        }
        
        // Insert post into database
        $stmt = $conn->prepare("INSERT INTO innovation_posts (user_id, title, content, media_url, media_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $title, $content, $media_url, $media_type);
        
        if ($stmt->execute()) {
            // If tags were submitted, process them
            if (!empty($_POST['post_tags'])) {
                $post_id = $stmt->insert_id;
                $tags = explode(",", $_POST['post_tags']);
                
                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    if (!empty($tag)) {
                        $tag_stmt = $conn->prepare("INSERT INTO post_tags (post_id, tag_name) VALUES (?, ?)");
                        $tag_stmt->bind_param("is", $post_id, $tag);
                        $tag_stmt->execute();
                    }
                }
            }
            
            // Redirect to avoid form resubmission
            header("Location: innovation.php?success=1");
            exit();
        }
    } else {
        // User not logged in, redirect to login
        header("Location: login.php?redirect=innovation.php");
        exit();
    }
}

// Handle post reactions (upvote, like)
if (isset($_POST['react_to_post']) && isset($_SESSION['user_id'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    $reaction_type = $_POST['reaction_type'];
    
    // Check if user has ANY reaction to this post already
    $check_stmt = $conn->prepare("SELECT id, reaction_type FROM post_reactions WHERE post_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $post_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    // Begin transaction to ensure data consistency
    $conn->begin_transaction();
    
    try {
        if ($check_result->num_rows > 0) {
            // User already has a reaction
            $existing_reaction = $check_result->fetch_assoc();
            $existing_type = $existing_reaction['reaction_type'];
            
            // Delete the existing reaction
            $delete_stmt = $conn->prepare("DELETE FROM post_reactions WHERE id = ?");
            $delete_stmt->bind_param("i", $existing_reaction['id']);
            $delete_stmt->execute();
            
            // Update post counts for the existing reaction
            if ($existing_type == 'upvote') {
                $update_stmt = $conn->prepare("UPDATE innovation_posts SET upvotes = upvotes - 1 WHERE id = ?");
            } else if ($existing_type == 'downvote') {
                $update_stmt = $conn->prepare("UPDATE innovation_posts SET downvotes = downvotes - 1 WHERE id = ?");
            }
            $update_stmt->bind_param("i", $post_id);
            $update_stmt->execute();
            
            // Only add new reaction if it's different from the existing one
            if ($existing_type != $reaction_type) {
                // Add new reaction
                $insert_stmt = $conn->prepare("INSERT INTO post_reactions (post_id, user_id, reaction_type) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("iis", $post_id, $user_id, $reaction_type);
                $insert_stmt->execute();
                
                // Update post counts for the new reaction
                if ($reaction_type == 'upvote') {
                    $update_stmt = $conn->prepare("UPDATE innovation_posts SET upvotes = upvotes + 1 WHERE id = ?");
                } else if ($reaction_type == 'downvote') {
                    $update_stmt = $conn->prepare("UPDATE innovation_posts SET downvotes = downvotes + 1 WHERE id = ?");
                }
                $update_stmt->bind_param("i", $post_id);
                $update_stmt->execute();
            }
        } else {
            // User has no reaction yet, add a new one
            $insert_stmt = $conn->prepare("INSERT INTO post_reactions (post_id, user_id, reaction_type) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("iis", $post_id, $user_id, $reaction_type);
            $insert_stmt->execute();
            
            // Update post counts
            if ($reaction_type == 'upvote') {
                $update_stmt = $conn->prepare("UPDATE innovation_posts SET upvotes = upvotes + 1 WHERE id = ?");
            } else if ($reaction_type == 'downvote') {
                $update_stmt = $conn->prepare("UPDATE innovation_posts SET downvotes = downvotes + 1 WHERE id = ?");
            }
            $update_stmt->bind_param("i", $post_id);
            $update_stmt->execute();
        }
        
        $conn->commit();
    } catch (Exception $e) {
        // Something went wrong, rollback changes
        $conn->rollback();
    }
    
    // Return updated counts for AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        $count_stmt = $conn->prepare("SELECT upvotes, downvotes FROM innovation_posts WHERE id = ?");
        $count_stmt->bind_param("i", $post_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result()->fetch_assoc();
        
        // Get the user's current reaction type (if any) after all operations
        $current_reaction_stmt = $conn->prepare("SELECT reaction_type FROM post_reactions WHERE post_id = ? AND user_id = ?");
        $current_reaction_stmt->bind_param("ii", $post_id, $user_id);
        $current_reaction_stmt->execute();
        $current_reaction_result = $current_reaction_stmt->get_result();
        $current_reaction = $current_reaction_result->num_rows > 0 ? $current_reaction_result->fetch_assoc()['reaction_type'] : null;
        
        echo json_encode([
            'upvotes' => $count_result['upvotes'],
            'downvotes' => $count_result['downvotes'],
            'userReaction' => $current_reaction
        ]);
        exit;
    }
    
    // Redirect if not AJAX
    header("Location: innovation.php");
    exit();
}

// Handle comment submission
if (isset($_POST['submit_comment']) && isset($_SESSION['user_id'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    $comment = $_POST['comment'];
    
    $stmt = $conn->prepare("INSERT INTO post_comments (post_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $comment);
    
    if ($stmt->execute()) {
        // If it's an AJAX request, return the new comment
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $user_data = getUserData($user_id, $conn);
            
            echo json_encode([
                'id' => $stmt->insert_id,
                'user_name' => $user_data['full_name'],
                'profile_pic' => $user_data['profile_pic'] ? $user_data['profile_pic'] : 'default.png',
                'comment' => $comment,
                'created_at' => date('M j, Y g:i A')
            ]);
            exit;
        }
    }
    
    // Redirect if not AJAX
    header("Location: innovation.php");
    exit();
}

// Handle comment deletion
if (isset($_POST['delete_comment']) && isset($_SESSION['user_id'])) {
    header('Content-Type: application/json'); 
    $comment_id = $_POST['comment_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if the user owns this comment
    $stmt = $conn->prepare("SELECT id FROM post_comments WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $comment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User owns the comment, delete it
        $delete_stmt = $conn->prepare("DELETE FROM post_comments WHERE id = ?");
        $delete_stmt->bind_param("i", $comment_id);
        
        if ($delete_stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } else {
        // User doesn't own the comment
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    }
    exit;
}

// Handle post deletion
if (isset($_POST['delete_post']) && isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if the user owns this post
    $stmt = $conn->prepare("SELECT id FROM innovation_posts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User owns the post - first delete all comments and reactions
        $conn->begin_transaction();
        
        try {
            // Delete all comments for this post
            $delete_comments = $conn->prepare("DELETE FROM post_comments WHERE post_id = ?");
            $delete_comments->bind_param("i", $post_id);
            $delete_comments->execute();
            
            // Delete all reactions for this post
            $delete_reactions = $conn->prepare("DELETE FROM post_reactions WHERE post_id = ?");
            $delete_reactions->bind_param("i", $post_id);
            $delete_reactions->execute();
            
            // Delete all tags for this post
            $delete_tags = $conn->prepare("DELETE FROM post_tags WHERE post_id = ?");
            $delete_tags->bind_param("i", $post_id);
            $delete_tags->execute();
            
            // Finally delete the post itself
            $delete_post = $conn->prepare("DELETE FROM innovation_posts WHERE id = ?");
            $delete_post->bind_param("i", $post_id);
            $delete_post->execute();
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        // User doesn't own the post
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    }
    exit;
}

// Get user data if logged in
$userData = null;
if (isset($_SESSION['user_id'])) {
    $user_query = "SELECT * FROM users WHERE id = " . $_SESSION['user_id'];
    $user_result = $conn->query($user_query);
    if ($user_result && $user_result->num_rows > 0) {
        $userData = $user_result->fetch_assoc();
    }
}

// Get posts for display
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$category = isset($_GET['category']) ? $_GET['category'] : null;
$tag = isset($_GET['tag']) ? $_GET['tag'] : null;

$posts_query = "SELECT p.*, u.full_name, u.profile_pic, u.user_type 
                FROM innovation_posts p 
                JOIN users u ON p.user_id = u.id ";

// Apply filters
if ($category) {
    $posts_query .= " JOIN post_tags pt ON p.id = pt.post_id WHERE pt.tag_name = '$category' ";
} elseif ($tag) {
    $posts_query .= " JOIN post_tags pt ON p.id = pt.post_id WHERE pt.tag_name = '$tag' ";
} else {
    $posts_query .= " WHERE 1=1 ";
}

// Sort based on filter
if ($filter == 'trending') {
    $posts_query .= " ORDER BY p.upvotes DESC, p.created_at DESC ";
} elseif ($filter == 'newest') {
    $posts_query .= " ORDER BY p.created_at DESC ";
} elseif ($filter == 'government') {
    $posts_query .= " AND u.user_type = 'govt' ORDER BY p.created_at DESC ";
} elseif ($filter == 'entrepreneur') {
    $posts_query .= " AND u.user_type = 'entrepreneur' ORDER BY p.created_at DESC ";
} else {
    $posts_query .= " ORDER BY p.created_at DESC ";
}

$posts_query .= " LIMIT 20";
$posts_result = $conn->query($posts_query);

// Get trending tags
$trending_tags_query = "SELECT tag_name, COUNT(*) as count 
                        FROM post_tags 
                        GROUP BY tag_name 
                        ORDER BY count DESC 
                        LIMIT 5";
$trending_tags_result = $conn->query($trending_tags_query);
$trending_tags = [];
if ($trending_tags_result && $trending_tags_result->num_rows > 0) {
    while ($tag = $trending_tags_result->fetch_assoc()) {
        $trending_tags[] = $tag;
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user type with default value
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'normal';

// Get user's full name
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_name = $user['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColabX - Innovation Hub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/in.css">
    <style>
        /* Default avatar styling */
        .default-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
            color: #aaa;
        }
        
        .avatar.default-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 24px;
        }
        
        .comment-avatar.default-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 18px;
        }
        
        /* Ensure proper sizing of avatar images */
        .avatar, .comment-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .comment-avatar {
            width: 40px;
            height: 40px;
        }
        
        /* Post actions styling */
        .post-actions {
            margin-left: auto;
        }
        
        .delete-post-btn {
            background-color: transparent;
            color: #dc3545;
            border: none;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        
        .delete-post-btn:hover {
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .post-header {
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <!-- Preloader -->
 <div class="preloader"> 
    <div class="loader">
        <div class="loader-circle"></div>
        <div class="loader-circle"></div>
        <div class="loader-text">Colab<span>X</span></div>
    </div>
</div>  

    <!-- Header -->
<header>
    <nav class="navbar flex">
        <a href="../index.php" class="logo">
            Colab<span>X</span>
        </a>
            <ul class="navlist flex">
                <li><a href="../index.php" class="link">Home</a></li>
                <li><a href="colab.php" class="link">Project</a></li>
                <li><a href="innovation.php" class="link active">Innovation</a></li>
                <li><a href="about.php" class="link">About Us</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo $user_type === 'normal' ? 'dashboard/user_dashboard.php' : 'dashboard/' . $user_type . '_dashboard.php'; ?>" class="link">Dashboard</a></li>
                <?php endif; ?>
            </ul>
            <div class="user-actions">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="notification-badge" data-count="2">
                        <i class="fas fa-bell"></i>
                    </div>
                    <button id="create-post-btn" class="btn primary-btn"><i class="fas fa-plus"></i> New Post</button>
                    <form action="logout.php" method="POST" style="display:inline">
                        <button type="submit" class="btn sign-in">Logout</button>
                    </form>
                <?php else: ?>
                    <a href="register.php" class="btn register">Register</a>
                    <a href="login.php" class="btn sign-in">Sign in</a>
                <?php endif; ?>
                
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

    <!-- Main Content -->
    <div class="innovation-container">
        <!-- Top Section with Filter Options -->
        <div class="innovation-header">
            <h1>Innovation Hub</h1>
            <p>Discover and share innovative ideas connecting governments and entrepreneurs</p>
            
            <div class="filter-container">
                <button class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>" data-filter="all">All Posts</button>
                <button class="filter-btn <?php echo $filter == 'trending' ? 'active' : ''; ?>" data-filter="trending">Trending</button>
                <button class="filter-btn <?php echo $filter == 'newest' ? 'active' : ''; ?>" data-filter="newest">Newest</button>
                <button class="filter-btn <?php echo $filter == 'government' ? 'active' : ''; ?>" data-filter="government">Government</button>
                <button class="filter-btn <?php echo $filter == 'entrepreneur' ? 'active' : ''; ?>" data-filter="entrepreneur">Entrepreneurs</button>
            </div>
            
            <?php if(!empty($trending_tags)): ?>
            <div class="trending-tags">
                <span>Trending:</span>
                <?php foreach($trending_tags as $tag): ?>
                    <a href="?tag=<?php echo urlencode($tag['tag_name']); ?>" class="tag">#<?php echo htmlspecialchars($tag['tag_name']); ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Posts Feed Section -->
        <div class="posts-container">
            <?php if(isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="success-message">Your post has been published successfully!</div>
            <?php endif; ?>
            
            <?php if($posts_result && $posts_result->num_rows > 0): ?>
                <?php while($post = $posts_result->fetch_assoc()): ?>
                    <?php
                    // Get tags for this post
                    $tags_query = "SELECT tag_name FROM post_tags WHERE post_id = " . $post['id'];
                    $tags_result = $conn->query($tags_query);
                    $tags = [];
                    if($tags_result && $tags_result->num_rows > 0) {
                        while($tag = $tags_result->fetch_assoc()) {
                            $tags[] = $tag['tag_name'];
                        }
                    }
                    
                    // Get comments count
                    $comments_query = "SELECT COUNT(*) as count FROM post_comments WHERE post_id = " . $post['id'];
                    $comments_result = $conn->query($comments_query);
                    $comments_count = $comments_result->fetch_assoc()['count'];
                    
                    // Check if user has reacted to this post
                    $user_reaction = null;
                    if(isset($_SESSION['user_id'])) {
                        $reaction_query = "SELECT reaction_type FROM post_reactions 
                                          WHERE post_id = " . $post['id'] . " 
                                          AND user_id = " . $_SESSION['user_id'];
                        $reaction_result = $conn->query($reaction_query);
                        if($reaction_result && $reaction_result->num_rows > 0) {
                            $user_reaction = $reaction_result->fetch_assoc()['reaction_type'];
                        }
                    }
                    ?>
                    <div class="post" data-post-id="<?php echo $post['id']; ?>">
        <div class="post-header">
                            <?php if(!empty($post['profile_pic'])): ?>
                                <img src="/ColabX/<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="<?php echo $post['full_name']; ?>" class="avatar">
                            <?php else: ?>
                                <div class="avatar default-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
            <div class="post-info">
                                <span class="username"><?php echo $post['full_name']; ?></span>
                                <span class="user-badge <?php echo $post['user_type']; ?>">
                                    <?php echo ucfirst($post['user_type']); ?>
                                </span>
                                <span class="timestamp"><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                            </div>
                            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
                                <div class="post-actions">
                                    <button class="delete-post-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </button>
                                </div>
                            <?php endif; ?>
            </div>
                        
                        <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <p class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                        
                        <?php if(!empty($tags)): ?>
                        <div class="post-tags">
                            <?php foreach($tags as $tag): ?>
                                <a href="?tag=<?php echo urlencode($tag); ?>" class="tag">#<?php echo htmlspecialchars($tag); ?></a>
                            <?php endforeach; ?>
        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($post['media_url'])): ?>
        <div class="post-media">
                                <?php if($post['media_type'] == 'image'): ?>
                                    <img src="../uploads/innovation_posts/<?php echo $post['media_url']; ?>" alt="Post Image" class="post-image">
                                <?php elseif($post['media_type'] == 'video'): ?>
                                    <video controls class="post-video">
                                        <source src="../uploads/innovation_posts/<?php echo $post['media_url']; ?>" type="video/mp4">
                                        Your browser does not support video playback.
                                    </video>
                                <?php else: ?>
                                    <a href="../uploads/innovation_posts/<?php echo $post['media_url']; ?>" class="document-link" target="_blank">
                                        <i class="fa-solid fa-file-lines"></i> View Document
                                    </a>
                                <?php endif; ?>
        </div>
                        <?php endif; ?>
                        
        <div class="post-footer">
                            <div class="reactions">
                                <form class="reaction-form" method="post">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <input type="hidden" name="reaction_type" value="upvote">
                                    <button type="submit" name="react_to_post" 
                                            class="upvote-btn <?php echo $user_reaction == 'upvote' ? 'active' : ''; ?>">
                                        <i class="fa-solid fa-arrow-up"></i> 
                                        <span class="upvote-count"><?php echo $post['upvotes']; ?></span>
                                    </button>
                                </form>
                                
                                <form class="reaction-form" method="post">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <input type="hidden" name="reaction_type" value="downvote">
                                    <button type="submit" name="react_to_post" 
                                            class="downvote-btn <?php echo $user_reaction == 'downvote' ? 'active' : ''; ?>">
                                        <i class="fa-solid fa-arrow-down"></i>
                                        <span class="downvote-count"><?php echo $post['downvotes']; ?></span>
                                    </button>
                                </form>
                                
                                <button class="comment-btn">
                                    <i class="fa-solid fa-comment"></i> <span class="comment-count"><?php echo $comments_count; ?></span> Comments
                                </button>
                                
                                <button class="share-btn">
                                    <i class="fa-solid fa-share"></i> Share
                                </button>
                            </div>
        </div>
        
                        <!-- Comments Section (hidden by default) -->
                        <div class="comments-section">
                            <h4>Comments</h4>
                            
                            <!-- Comment Form -->
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <form class="comment-form" method="post">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <textarea name="comment" placeholder="Add a comment..." required></textarea>
                                    <button type="submit" name="submit_comment">Post</button>
                                </form>
                            <?php else: ?>
                                <p class="login-to-comment">Please <a href="login.php">login</a> to comment</p>
                            <?php endif; ?>
                            
                            <!-- Comments List -->
                            <div class="comments-list" data-post-id="<?php echo $post['id']; ?>">
                                <?php
                                $comments_query = "SELECT c.*, u.full_name, u.profile_pic 
                                                  FROM post_comments c 
                                                  JOIN users u ON c.user_id = u.id 
                                                  WHERE c.post_id = " . $post['id'] . " 
                                                  ORDER BY c.created_at DESC";
                                $comments_result = $conn->query($comments_query);
                                ?>
                                
                                <?php if($comments_result && $comments_result->num_rows > 0): ?>
                                    <?php while($comment = $comments_result->fetch_assoc()): ?>
                                        <div class="comment" data-comment-id="<?php echo $comment['id']; ?>">
                                            <?php if(!empty($comment['profile_pic'])): ?>
                                                <img src="/ColabX/<?php echo htmlspecialchars($comment['profile_pic']); ?>" alt="<?php echo $comment['full_name']; ?>" class="comment-avatar">
                                            <?php else: ?>
                                                <div class="comment-avatar default-avatar">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="comment-content">
                                                <div class="comment-header">
                                                    <span class="comment-username"><?php echo $comment['full_name']; ?></span>
                                                    <span class="comment-time"><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></span>
                                                </div>
                                                <p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']): ?>
                                                    <button class="delete-comment-btn" data-comment-id="<?php echo $comment['id']; ?>">
                                                        <i class="fa-solid fa-trash"></i> Delete
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <p class="no-comments">No comments yet. Be the first to comment!</p>
                                <?php endif; ?>
    </div>
            </div>
        </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-posts">
                    <p>No innovations or ideas have been shared yet. Be the first to share!</p>
        </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Post Modal -->
    <div id="create-post-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Share Your Innovation</h2>
            <form action="innovation.php" method="post" enctype="multipart/form-data" class="create-post-form">
                <input type="text" name="post_title" placeholder="Title of your innovation" required>
                <textarea name="post_content" placeholder="Describe your innovation or idea..." required></textarea>
                <div class="form-row">
                    <input type="text" name="post_tags" placeholder="Tags (comma separated e.g. AI, Blockchain, Healthcare)">
            </div>
                <div class="file-upload-container">
                    <label for="post_media">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload Media
                    </label>
                    <input type="file" name="post_media" id="post_media">
                    <span class="selected-file"></span>
        </div>
                <button type="submit" name="submit_post" class="submit-post">Post</button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
        // Preloader
     window.addEventListener('load', function() {
        setTimeout(function() {
            document.querySelector('.preloader').classList.add('hidden');
        }, 1000);
    });
        
        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                window.location.href = `innovation.php?filter=${filter}`;
            });
        });
        
        // Modal functions
        const modal = document.getElementById('create-post-modal');
        const createPostBtn = document.getElementById('create-post-btn');
        const closeModalBtn = document.querySelector('.close-modal');
        
        if (createPostBtn) {
            createPostBtn.addEventListener('click', function() {
                modal.style.display = 'block';
                document.body.classList.add('modal-open');
            });
        }
        
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', function() {
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
            });
        }
        
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
            }
        });
        
        // Toggle comments section
        document.querySelectorAll('.comment-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const post = this.closest('.post');
                const commentsSection = post.querySelector('.comments-section');
                commentsSection.classList.toggle('active');
            });
        });
        
        // File upload display
        document.getElementById('post_media')?.addEventListener('change', function() {
            const fileName = this.files[0]?.name;
            document.querySelector('.selected-file').textContent = fileName || '';
        });
        
        // Post reactions AJAX
        document.querySelectorAll('.reaction-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('react_to_post', '1');
                
                fetch('innovation.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const postId = this.querySelector('[name="post_id"]').value;
                    const post = document.querySelector(`.post[data-post-id="${postId}"]`);
                    
                    post.querySelector('.upvote-count').textContent = data.upvotes;
                    post.querySelector('.downvote-count').textContent = data.downvotes;
                    
                    const reactionType = this.querySelector('[name="reaction_type"]').value;
                    const upvoteBtn = post.querySelector('.upvote-btn');
                    const downvoteBtn = post.querySelector('.downvote-btn');
                    
                    // Remove active class from both buttons
                    upvoteBtn.classList.remove('active');
                    downvoteBtn.classList.remove('active');
                    
                    // Set active class based on the server's response
                    if (data.userReaction === 'upvote') {
                        upvoteBtn.classList.add('active');
                    } else if (data.userReaction === 'downvote') {
                        downvoteBtn.classList.add('active');
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });
        
        // Comment submission AJAX
        document.querySelectorAll('.comment-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('submit_comment', '1');
                
                fetch('innovation.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const postId = this.querySelector('[name="post_id"]').value;
                    const commentsContainer = document.querySelector(`.comments-list[data-post-id="${postId}"]`);
                    const post = document.querySelector(`.post[data-post-id="${postId}"]`);
                    
                    // Create new comment element
                    const commentElement = document.createElement('div');
                    commentElement.className = 'comment';
                    commentElement.dataset.commentId = data.id;
                    commentElement.innerHTML = `
                        ${data.profile_pic ? 
                        `<img src="/ColabX/${data.profile_pic}" alt="${data.user_name}" class="comment-avatar">` : 
                        `<div class="comment-avatar default-avatar"><i class="fas fa-user"></i></div>`}
                        <div class="comment-content">
                            <div class="comment-header">
                                <span class="comment-username">${data.user_name}</span>
                                <span class="comment-time">${data.created_at}</span>
                            </div>
                            <p class="comment-text">${data.comment.replace(/\n/g, '<br>')}</p>
                            <button class="delete-comment-btn" data-comment-id="${data.id}">
                                <i class="fa-solid fa-trash"></i> Delete
                            </button>
                        </div>
                    `;
                    
                    // Remove "no comments" message if it exists
                    const noComments = commentsContainer.querySelector('.no-comments');
                    if (noComments) {
                        noComments.remove();
                    }
                    
                    // Add new comment to the beginning of the comments list
                    commentsContainer.insertBefore(commentElement, commentsContainer.firstChild);
                    
                    // Update comment count
                    const commentCountEl = post.querySelector('.comment-count');
                    const currentCount = parseInt(commentCountEl.textContent);
                    commentCountEl.textContent = currentCount + 1;
                    
                    // Clear the form
                    this.reset();

                    // Attach delete event handler to the new comment
                    attachDeleteHandler(commentElement.querySelector('.delete-comment-btn'));
                })
                .catch(error => console.error('Error:', error));
            });
        });
        
        // Comment deletion handler
        function attachDeleteHandler(button) {
            if (!button) return;
            
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this comment?')) {
                    const commentId = this.getAttribute('data-comment-id');
                    const commentElement = this.closest('.comment');
                    const postId = commentElement.closest('.comments-list').getAttribute('data-post-id');
                    const post = document.querySelector(`.post[data-post-id="${postId}"]`);
                    
                    const formData = new FormData();
                    formData.append('delete_comment', '1');
                    formData.append('comment_id', commentId);
                    
                    fetch('innovation.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Remove the comment element
                            commentElement.remove();
                            
                            // Update comment count
                            const commentCountEl = post.querySelector('.comment-count');
                            const currentCount = parseInt(commentCountEl.textContent);
                            commentCountEl.textContent = Math.max(0, currentCount - 1);
                            
                            // Add "no comments" message if this was the last comment
                            const commentsContainer = document.querySelector(`.comments-list[data-post-id="${postId}"]`);
                            if (!commentsContainer.querySelector('.comment')) {
                                commentsContainer.innerHTML = '<p class="no-comments">No comments yet. Be the first to comment!</p>';
                            }
                        } else {
                            console.error('Error:', data.error);
                            alert('Error: ' + (data.error || 'Unable to delete comment'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Don't show alert since the comment was likely deleted successfully
                        commentElement.remove();
                            
                        // Update comment count
                        const commentCountEl = post.querySelector('.comment-count');
                        const currentCount = parseInt(commentCountEl.textContent);
                        commentCountEl.textContent = Math.max(0, currentCount - 1);
                    });
                }
            });
        }
        
        // Attach delete handlers to all existing delete buttons
        document.querySelectorAll('.delete-comment-btn').forEach(button => {
            attachDeleteHandler(button);
        });
        
        // Post deletion handler
        document.querySelectorAll('.delete-post-btn').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
                    const postId = this.getAttribute('data-post-id');
                    const postElement = this.closest('.post');
                    
                    const formData = new FormData();
                    formData.append('delete_post', '1');
                    formData.append('post_id', postId);
                    
                    fetch('innovation.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Remove the post element with a fade-out effect
                            postElement.style.opacity = '0';
                            postElement.style.transition = 'opacity 0.5s';
                            
                            setTimeout(() => {
                                postElement.remove();
                                
                                // Check if this was the last post
                                const postsContainer = document.querySelector('.posts-container');
                                if (!postsContainer.querySelector('.post')) {
                                    postsContainer.innerHTML = '<div class="no-posts"><p>No innovations or ideas have been shared yet. Be the first to share!</p></div>';
                                }
                            }, 500);
                        } else {
                            console.error('Error:', data.error);
                            alert('Error: ' + (data.error || 'Unable to delete post'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while trying to delete the post');
                    });
                }
            });
        });
</script>
</body>
</html>
