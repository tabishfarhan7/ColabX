<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColabX - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/login.css">
    <style>
        .alert {
            padding: 10px;
            margin: 10px 0;
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
    </style>
</head>

<body>
    <?php
    // Include database connection and functions
    require_once('../includes/db_connect.php');
    require_once('../includes/functions.php');
    
    $error = "";
    $success = "";
    
    // Check if user is already logged in
    if (isset($_SESSION['user_id'])) {
        // Redirect to appropriate dashboard based on user type
        redirect_to_dashboard($_SESSION['user_type']);
    }
    
    // Process login form
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get form data
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        
        // Validate input
        if (empty($email) || empty($password)) {
            $error = "Email and password are required";
        } else {
            // Check if user exists
            $stmt = $conn->prepare("SELECT id, email, password, user_type FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Password is correct, start a new session
                    session_start();
                    
                    // Get user's full name
                    $nameStmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
                    $nameStmt->bind_param("i", $user['id']);
                    $nameStmt->execute();
                    $nameResult = $nameStmt->get_result();
                    if ($nameResult->num_rows == 1) {
                        $nameData = $nameResult->fetch_assoc();
                        $_SESSION['name'] = $nameData['full_name'];
                    } else {
                        $_SESSION['name'] = ''; // Set empty name if not found
                    }
                    
                    // Store user data in session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_type'] = $user['user_type'];
                    
                    // Store login timestamp for accurate "Just now" display on dashboard
                    $_SESSION['login_time'] = time();
                    
                    // Record login activity
                    record_activity($conn, $user['id'], 'login', 'You logged in to your account');
                    
                    $success = "Login successful! Redirecting to dashboard...";
                    
                    // Redirect to appropriate dashboard based on user type
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "';
                            
                            // Use the correct dashboard path based on user type
                            if ($user['user_type'] === 'normal') {
                                echo 'dashboard/user_dashboard.php';
                            } else {
                                echo 'dashboard/' . $user['user_type'] . '_dashboard.php';
                            }
                            
                            echo '";
                        }, 1500);
                    </script>';
                } else {
                    $error = "Invalid password";
                }
            } else {
                $error = "No account found with that email";
            }
        }
    }
    ?>

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
            <li><a href="login.php" class="link">Login</a></li>
        </ul>
        <ul class="user-logo">
            <li class="nav-icons flex">
                <a href="#" class="icon"><i class="fa-solid fa-user"></i></a>
            </li>
        </ul>
    </nav>
</header>

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
            <div class="login-box">
                <h2>Welcome Back</h2>
                <p>Don't have an account yet? <a href="register.php" class="signup-link"> <button class="signup">&nbspSign up</button></a></p>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="login-form">
                    <div class="input-box">
                        <img src="../images/email.png" alt="Email Icon" class="icon">
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>
        
                    <div class="input-box">
                        <img src="../images/unlock.png" alt="Password Icon" class="icon">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
        
                    <button type="submit" class="login-btn">Login</button>
                </form>
                <div class="separator-container">
                    <div class="line"></div>
                    <span class="separator">OR</span>
                    <div class="line"></div>
                </div>
    
                <div class="social-login">
                    <a href="#"><img src="../images/x (1).png" alt="X Login"></a>
                    <a href="#"><img src="../images/x (2).png" alt="Google Login"></a>
                    <a href="#"><img src="../images/x (3).png" alt="Apple Login"></a>
                    
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
       