<?php
// Include database connection and functions
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

$error = "";
$success = "";
$token_valid = false;
$token = "";

// Check if token is provided in URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token exists and is not expired
    $stmt = $conn->prepare("SELECT pr.*, u.email 
                           FROM password_resets pr 
                           JOIN users u ON pr.user_id = u.id 
                           WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $token_valid = true;
        $reset_data = $result->fetch_assoc();
    } else {
        $error = "Invalid or expired token. Please request a new password reset link.";
    }
} else {
    $error = "No reset token provided. Please request a password reset from the login page.";
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid) {
    $password = sanitize_input($_POST['password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);
    
    // Validate password
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user's password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $reset_data['user_id']);
        
        if ($stmt->execute()) {
            // Mark token as used
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            $success = "Your password has been reset successfully. You can now <a href='login.php'>login</a> with your new password.";
            $token_valid = false; // Prevent form from showing again
        } else {
            $error = "Error updating password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColabX - Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/login.css">
    <style>
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            text-align: center;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Home link styles */
        .home-link {
            position: relative;
            display: inline-block;
            margin-top: 15px;
            padding: 8px 20px;
            color: var(--black-clr);
            font-size: 0.85rem;
            text-decoration: none;
            border-radius: 5px;
            background: rgba(255, 229, 53, 0.7);
            backdrop-filter: blur(5px);
            overflow: hidden;
            transition: all 0.3s ease;
            z-index: 1;
        }

        .home-link:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: 0.5s;
            z-index: -1;
        }

        .home-link:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .home-link:hover:before {
            left: 100%;
        }

        .home-link i {
            margin-right: 5px;
        }
        
        .back-to-login {
            display: block;
            text-align: center;
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .back-to-login a {
            color: #555;
            text-decoration: none;
            transition: color 0.3s;
            padding: 5px 10px;
        }
        
        .back-to-login a:hover {
            color: #FFE535;
            text-decoration: underline;
        }
        
        .password-requirements {
            margin-top: 15px;
            margin-bottom: 20px;
            font-size: 0.8rem;
            color: #666;
            text-align: center;
        }

        .input-box {
            margin-bottom: 20px;
        }

        .login-btn {
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <!-- Preloader  -->
    <div class="preloader"> 
        <div class="loader">
            <div class="loader-circle"></div>
            <div class="loader-circle"></div>
            <div class="loader-text">Colab<span>X</span></div>
        </div>
    </div>  
    
    <!-- Home -->
     <div class="home">
        <div class="content">
            <div class="video">
                <video src="../videos/colab.mp4" loop autoplay muted playsinline></video>
            </div>
            <div class="login-box" style="padding: 35px 30px 40px; min-height: 450px;">
                <h2 style="margin-bottom: 20px;">Reset Password</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($token_valid): ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?token=" . $token; ?>" method="POST" id="reset-password-form">
                    <div class="input-box">
                        <img src="../images/unlock.png" alt="Password Icon" class="icon">
                        <input type="password" name="password" placeholder="New Password" required>
                    </div>
                    
                    <div class="input-box">
                        <img src="../images/unlock.png" alt="Password Icon" class="icon">
                        <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                    </div>
                    
                    <div class="password-requirements">
                        Password must be at least 8 characters long.
                    </div>
        
                    <button type="submit" class="login-btn">Reset Password</button>
                </form>
                <?php endif; ?>
                
                <div class="back-to-login">
                    <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
                </div>
                
                <!-- Back to Homepage Link -->
                <div style="text-align: center; margin-top: 20px; margin-bottom: 15px;">
                    <a href="../index.php" class="home-link">
                        <i class="fas fa-home"></i> Back to Homepage
                    </a>
                </div>
            </div>
        </div>
     </div>
     <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.querySelector('.preloader').classList.add('hidden');
            }, 1000);
        });
     </script>
</body>
</html> 