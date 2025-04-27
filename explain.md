# PHP Implementation in ColabX Project

## Core PHP Structure

### Database Connectivity
- Uses a traditional MySQL connection via `mysqli` in `includes/db_connect.php`
- Sets timezone to UTC for consistent timestamp handling worldwide
- Contains basic error handling for connection failures
- Automatically starts a PHP session for user state management

### Session Management
- Sessions are initialized with `session_start()` in multiple files to maintain user state
- User authentication state is stored in `$_SESSION['user_id']`
- Session checks are performed at the beginning of PHP files requiring authentication

## Function Organization

The `includes/functions.php` file serves as a utility library with:

- Input sanitization: `sanitize_input()` for security
- UI message generators: `success_alert()` and `error_alert()`
- Authentication helpers: `is_logged_in()`, `redirect_to_dashboard()`
- User data retrieval: `get_user_data()`
- Activity logging: `record_activity()`, `get_user_activities()`, `log_activity()`
- Password management: `generatePasswordResetToken()`, `verifyPasswordResetToken()`, `resetPassword()`

## Action Handling

PHP scripts in the `actions/` directory process specific user interactions:

### Like System Implementation
- `like_initiative.php`: Handles liking/unliking government initiatives
  - Validates user authentication via session
  - Uses prepared statements for SQL injection prevention
  - Creates database tables if they don't exist
  - Supports both production and mock data
  - Returns JSON responses for AJAX requests

### Status Checking
- `check_initiative_like.php`: Checks if a user has liked a specific initiative
  - Validates input parameters
  - Returns JSON with like status

## Security Practices

- Prepared statements to prevent SQL injection
- Input sanitization for user-provided data
- Session-based authentication
- Proper error handling with informative messages
- Table existence checks before operations

## Data Flow Pattern

1. Client requests trigger PHP scripts
2. Scripts validate session/input
3. Database operations are performed via prepared statements
4. JSON responses are returned to the client
5. Activity logging maintains audit trail of user actions

## Database Schema Management

- Tables are created automatically if they don't exist
- Foreign key relationships maintain data integrity
- Timestamps are used for activity tracking 

# ColabX Innovation Page: Posts, Comments, and Reactions System

This document explains the PHP implementation of posts, comments, and reaction features on the innovation page.

## Database Structure

The system uses four main database tables:

1. **innovation_posts**: Stores the main post content
   - `id`: Primary key
   - `user_id`: The user who created the post
   - `title`: Post title
   - `content`: Post body text
   - `media_url`: Optional file attachment (image/video/document)
   - `media_type`: Type of media (enum: 'image', 'video', 'document')
   - `upvotes`, `downvotes`: Counters for post reactions
   - `created_at`, `updated_at`: Timestamps

2. **post_comments**: Stores comments on posts
   - `id`: Primary key
   - `post_id`: The post being commented on
   - `user_id`: The user who wrote the comment
   - `comment`: The comment text
   - `created_at`, `updated_at`: Timestamps

3. **post_reactions**: Tracks user reactions to posts
   - `id`: Primary key
   - `post_id`: The post being reacted to
   - `user_id`: The user who reacted
   - `reaction_type`: Type of reaction (enum: 'upvote', 'downvote', 'like')
   - `created_at`: Timestamp
   - Has a unique key on (post_id, user_id, reaction_type) to prevent duplicates

4. **post_tags**: Stores tags for each post
   - `id`: Primary key
   - `post_id`: The post being tagged
   - `tag_name`: The tag text

## Creating Posts

The post creation process is handled by this PHP code:

```php
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
```

Key points about post creation:
- Only logged-in users can create posts (checked with `isset($_SESSION['user_id'])`)
- File uploads are supported (images, videos, documents) with type detection
- Uses `uniqid()` to generate unique filenames to prevent overwrites
- Tags are split by commas and processed individually
- Uses prepared statements with `bind_param()` to prevent SQL injection
- Redirects to prevent duplicate submissions on page refresh

## Handling Post Reactions (Upvotes/Downvotes)

The reaction system implements upvotes and downvotes with toggle functionality:

```php
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
}
```

Key points about reactions:
- Uses database transactions to ensure data consistency
- Implements a toggle behavior:
  - If user clicks the same reaction type again, it removes the reaction
  - If user clicks a different reaction type, it switches to the new type
- Maintains separate counters in the post table for efficiency
- Returns JSON response for AJAX requests with updated counts
- Uses MySQL's counter update feature (`upvotes = upvotes + 1`) for atomic operations

## Comment System

### Comment Submission

```php
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
}
```

### Comment Deletion

```php
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
```

Key points about comments:
- Only logged-in users can comment
- Comments are tied to both the post and the user who made them
- AJAX support allows for dynamic comment addition without page reload
- Comment deletion ensures users can only delete their own comments
- Uses prepared statements with `bind_param()` for security

## Post Deletion

Post deletion includes cleaning up all associated data:

```php
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
```

Key points about post deletion:
- Users can only delete their own posts
- Uses a transaction to ensure all related data is deleted atomically
- Deletes comments, reactions, and tags before the post itself
- Returns JSON response for AJAX requests

## Security Features

The code includes several security measures:

1. **SQL Injection Prevention**: Uses prepared statements with `bind_param()` throughout
2. **Authentication Checks**: Verifies user is logged in with `isset($_SESSION['user_id'])`
3. **Authorization Checks**: Ensures users can only modify their own content
4. **File Upload Security**: Validates file types and uses unique filenames
5. **AJAX Verification**: Checks for `X-Requested-With` header to verify AJAX requests
6. **Transaction Management**: Uses transactions to maintain data integrity

## Frontend Integration

The PHP code works with JavaScript to create a dynamic user experience:

- AJAX requests allow for reactions, comments, and deletions without page reloads
- JSON responses from PHP update the UI dynamically
- The system tracks and displays the current user's reaction status
- Comment counts are updated in real-time

This implementation provides a complete social interaction system with posts, comments, reactions, and proper security measures. 