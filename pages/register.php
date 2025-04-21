<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColabX - Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/register.css">
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
        
        /* Home link styles */
        .home-link {
            position: relative;
            display: inline-block;
            margin-top: 0;
            padding: 6px 16px;
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
    </style>
</head>

<body>
    <?php
    // Include database connection
    require_once('../includes/db_connect.php');
    require_once('../includes/functions.php');
    
    $error = "";
    $success = "";
    
    // Process registration form
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get form data
        $fullName = sanitize_input($_POST['full_name']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $userType = sanitize_input($_POST['user_type']);
        
        // Optional fields based on user type
        $govtId = isset($_POST['govt_id']) ? sanitize_input($_POST['govt_id']) : null;
        $companyName = isset($_POST['company_name']) ? sanitize_input($_POST['company_name']) : null;
        $businessType = isset($_POST['business_type']) ? sanitize_input($_POST['business_type']) : null;
        
        // Validate input
        if (empty($fullName) || empty($email) || empty($password)) {
            $error = "All required fields must be filled out";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already exists. Please use a different email or log in.";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user into database
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, user_type, govt_id, company_name, business_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $fullName, $email, $hashedPassword, $userType, $govtId, $companyName, $businessType);
                
                if ($stmt->execute()) {
                    $success = "Registration successful! You can now log in.";
                    
                    // Redirect to login page after 2 seconds
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "login.php";
                        }, 2000);
                    </script>';
                } else {
                    $error = "Error: " . $stmt->error;
                }
            }
        }
    }
    ?>

    <!-- Preloader  -->
    <div class="preloader"> 
        <div class="loader">
            <div class="loader-circle"></div>
            <div class="loader-circle"></div>
            <div class="loader-text">Colab<span>X</span></div>
        </div>
    </div>
    
    <div class="register-container">
        <div class="video-background">
            <video src="../videos/colab.mp4" loop autoplay muted playsinline></video>
        </div>

        <div class="register-box">
            <h2>Create an Account</h2>
            <p>Already have an account? <a href="login.php" class="login-link"> <button class="login">&nbspLog in</button></a></p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form id="registerForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <select id="userType" name="user_type" class="user-type">
                    <option value="normal">Normal User</option>
                    <option value="govt">Government Employee</option>
                    <option value="entrepreneur">Entrepreneur</option>
                </select>

                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="full_name" placeholder="Full Name" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <div class="input-group extra-field" id="govtField" style="display: none;">
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" name="govt_id" placeholder="Government ID Number">
                </div>

                <div class="input-group extra-field" id="entrepreneurFields" style="display: none;">
                    <i class="fa-solid fa-building"></i>
                    <input type="text" name="company_name" placeholder="Company Name">
                </div>

                <div class="input-group extra-field" id="businessField" style="display: none;">
                    <i class="fa-solid fa-briefcase"></i>
                    <input type="text" name="business_type" placeholder="Business Type">
                </div>

                <button type="submit" class="register-btn">Register</button>
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
            
            <!-- Back to Homepage Link -->
            <div style="text-align: center; margin-top: 10px;">
                <a href="../index.php" class="home-link">
                    <i class="fas fa-home"></i> Back to Homepage
                </a>
            </div>
        </div>
    </div>

    <script src="../js/register.js"></script>
</body>
</html>
