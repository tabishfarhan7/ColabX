<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColabX - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/login.css">
    
    
    
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
            <f class="login-box">
                <h2>Welcome Back</h2>
                <p>Don't have an account yet? <a href="register.php" class="signup-link"> <button class="signup">&nbspSign up</button></a></p>
    <form action="" id="login-form"></form>
                <div class="input-box">
                    <img src="../images/email.png" alt="Email Icon" class="icon">
                    <input type="email" placeholder="Email Address" required>
                </div>
    
                <div class="input-box">
                    <img src="../images/unlock.png" alt="Password Icon" class="icon">
                    <input type="password" placeholder="Password" required>
                </div>
    
                <button class="login-btn">Login</button>
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
       