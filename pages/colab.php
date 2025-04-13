<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ColabX - Project</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/colab.css">
    
    
    
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


    <section class="idea-upload">
        <h2>Submit Your Idea</h2>
        <form id="ideaForm">
            <label for="ideaTitle">Idea Title:</label>
            <input type="text" id="ideaTitle" name="ideaTitle" placeholder="Enter your idea title" required>
    
            <label for="ideaDescription">Description:</label>
            <textarea id="ideaDescription" name="ideaDescription" placeholder="Describe your idea..." required></textarea>
    
            <label for="problemAddressed">Problem Addressed:</label>
            <textarea id="problemAddressed" name="problemAddressed" placeholder="What problem does your idea solve?" required></textarea>
    
            <label for="objectives">Objectives:</label>
            <textarea id="objectives" name="objectives" placeholder="List the main objectives of your idea" required></textarea>
    
            <label for="stateOfArt">State of the Art/Research Gap:</label>
            <textarea id="stateOfArt" name="stateOfArt" placeholder="Describe the novelty and research gap your idea addresses" required></textarea>
    
            <label for="detailedDescription">Detailed Description:</label>
            <textarea id="detailedDescription" name="detailedDescription" placeholder="Provide a complete technical description" required></textarea>
    
            <label for="resultsAdvantages">Results and Advantages:</label>
            <textarea id="resultsAdvantages" name="resultsAdvantages" placeholder="List key advantages over existing solutions" required></textarea>
    
            <label for="expansion">Future Expansion:</label>
            <textarea id="expansion" name="expansion" placeholder="How can this idea be expanded in the future?" required></textarea>
    
            <label for="existingData">Existing Data/Comparative Analysis:</label>
            <textarea id="existingData" name="existingData" placeholder="Any existing data supporting your idea" required></textarea>
    
            <label for="useDisclosure">Use and Disclosure:</label>
            <select id="useDisclosure" name="useDisclosure" required>
                <option value="no">No, this idea has not been disclosed.</option>
                <option value="yes">Yes, this idea has been disclosed.</option>
            </select>
    
            <label for="commercialization">Potential for Commercialization:</label>
            <select id="commercialization" name="commercialization" required>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
            </select>
    
            <label for="filingOptions">Filing Options:</label>
            <select id="filingOptions" name="filingOptions" required>
                <option value="provisional">Provisional Patent Filing</option>
                <option value="complete">Complete Patent Filing</option>
                <option value="pct">PCT (International) Filing</option>
            </select>
    
            <label for="ideaFile">Upload Supporting Files (Optional):</label>
            <input type="file" id="ideaFile" name="ideaFile" accept=".png, .jpg, .jpeg, .pdf">
    
            <button class="submit" type="submit">Submit Idea</button>
        </form>
    </section>
    <script src="../js/colab.js"></script>
    
    
</body>
</html>