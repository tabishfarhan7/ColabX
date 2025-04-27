<?php
// Include database connection and functions
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

$error = "";
$success = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get email from form
    $email = sanitize_input($_POST['email']);
    
    // Check if email exists in database
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        // User exists, generate reset token
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $token = bin2hex(random_bytes(32)); // Generate a secure random token
        
        // Set token expiration (1 hour from now)
        $expires = date('Y-m-d H:i:s', time() + 3600);
        
        // Store token in database
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $token, $expires);
        
        if ($stmt->execute()) {
            // Create reset link
            $reset_link = "http://{$_SERVER['HTTP_HOST']}/ColabX/pages/reset_password.php?token=" . $token;
            
            // In a production environment, send an actual email
            // This is a placeholder for the email sending logic
            /*
            $to = $email;
            $subject = "ColabX - Password Reset";
            $message = "
            <html>
            <head>
                <title>Password Reset</title>
            </head>
            <body>
                <h2>Password Reset Request</h2>
                <p>Dear {$user['full_name']},</p>
                <p>We received a request to reset your password. Click the link below to set a new password:</p>
                <p><a href='{$reset_link}'>Reset Password</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn't request this, please ignore this email.</p>
                <p>Regards,<br>ColabX Team</p>
            </body>
            </html>
            ";
            
            // Headers for HTML email
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: ColabX <noreply@colabx.com>' . "\r\n";
            
            mail($to, $subject, $message, $headers);
            */
            
            // Success message
            $success = "Password reset link has been sent to your email. <br>For development purposes, here's the link: <a href='$reset_link'>Reset Password</a>";
            
        } else {
            $error = "Error generating reset token. Please try again.";
        }
    } else {
        // Provide same message for security (don't reveal if email exists or not)
        $success = "If your email exists in our system, you will receive a password reset link.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColabX - Forgot Password</title>
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
        
        .info-text {
            color: #666;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 25px;
            line-height: 1.5;
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

        .input-box {
            margin-bottom: 25px;
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
                <h2 style="margin-bottom: 20px;">Forgot Password</h2>
                <p class="info-text">Enter your email address below and we'll send you a link to reset your password.</p>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php else: ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="forgot-password-form">
                    <div class="input-box">
                        <img src="../images/email.png" alt="Email Icon" class="icon">
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>
        
                    <button type="submit" class="login-btn">Send Reset Link</button>
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