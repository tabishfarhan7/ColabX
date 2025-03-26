<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColabX - Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/register.css">
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
    
    <!-- Header -->
    <header>
        <nav class="navbar flex">
            <a href="../index.php" class="logo">
                Colab<span>X</span>
            </a>
            <ul class="navlist flex">
                <li><a href="../index.php" class="link" data-key="home">Home</a></li>
                <li><a href="colab.php" class="link" data-key="project">Project</a></li>
                <li><a href="innovation.php" class="link" data-key="innovation">Innovation</a></li>
                <li><a href="about.php" class="link" data-key="community">About Us</a></li>
                <li><a href="login.php" class="link" data-key="login">Login</a></li>
            </ul>
            <div class="user-actions">
                <button class="btn register" data-key="register">Register</button>
                <button class="btn sign-in" data-key="signIn"><a href="login.php">Sign in</a></button>

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

    <div class="register-container">
        <div class="video-background">
            <video src="../videos/colab.mp4" loop autoplay muted playsinline></video>
        </div>

        <div class="register-box">
            <h2>Create an Account</h2>
            <p>Already have an account? <a href="login.php" class="login-link"> <button class="login">&nbspLog in</button></a></p>

            <select id="userType" class="user-type">
                <option value="normal">Normal User</option>
                <option value="govt">Government Employee</option>
                <option value="entrepreneur">Entrepreneur</option>
            </select>

            <form id="registerForm">
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" placeholder="Full Name" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" placeholder="Email Address" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" placeholder="Password" required>
                </div>

                <div class="input-group extra-field" id="govtField" style="display: none;">
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" placeholder="Government ID Number">
                </div>

                <div class="input-group extra-field" id="entrepreneurFields" style="display: none;">
                    <i class="fa-solid fa-building"></i>
                    <input type="text" placeholder="Company Name">
                </div>

                <div class="input-group extra-field" id="businessField" style="display: none;">
                    <i class="fa-solid fa-briefcase"></i>
                    <input type="text" placeholder="Business Type">
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
        </div>
    </div>

    <script src="../js/register.js"></script>
</body>
</html>
