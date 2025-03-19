document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("ideaForm");

    form.addEventListener("submit", function (event) {
        event.preventDefault(); // Prevent page reload

        // Get form values
        const ideaTitle = document.getElementById("ideaTitle").value.trim();
        const ideaDescription = document.getElementById("ideaDescription").value.trim();
        const ideaFile = document.getElementById("ideaFile").files[0];

        // Basic Validation
        if (ideaTitle === "" || ideaDescription === "") {
            alert("Please fill out both the title and description.");
            return;
        }

        // Create a FormData object (for file upload)
        const formData = new FormData();
        formData.append("ideaTitle", ideaTitle);
        formData.append("ideaDescription", ideaDescription);
        if (ideaFile) {
            formData.append("ideaFile", ideaFile);
        }

        // Simulating form submission
        setTimeout(() => {
            alert("🎉 Your idea has been successfully submitted!");
            form.reset(); // Clear form after submission
        }, 1000);
    });
});
window.addEventListener('load', function() {
    setTimeout(function() {
        document.querySelector('.preloader').classList.add('hidden');
    }, 1000);
});


// dropdown
document.addEventListener("DOMContentLoaded", function () {
const langDropdown = document.querySelector(".language-dropdown");
const langBtn = document.querySelector(".lang-btn");

langBtn.addEventListener("click", function () {
    langDropdown.classList.toggle("active");
});

// Close dropdown when clicking outside
document.addEventListener("click", function (event) {
    if (!langDropdown.contains(event.target)) {
        langDropdown.classList.remove("active");
    }
});
});



// language changer
document.addEventListener("DOMContentLoaded", function () {
const langBtn = document.querySelector(".lang-btn");
const langDropdown = document.querySelector(".language-dropdown");
const langOptions = document.querySelectorAll(".language-list li");

// Object containing translations
const translations = {
    en: {
        home: "Home",
        project: "Project",
        innovation: "Innovation",
        community: "Community",
        login: "Login",
        register: "Register",
        signIn: "Sign in",
    },
    ar: {
        home: "الرئيسية",
        project: "مشروع",
        innovation: "ابتكار",
        community: "مجتمع",
        login: "تسجيل الدخول",
        register: "تسجيل",
        signIn: "تسجيل الدخول",
    },
};

// Set current language from localStorage
function setLanguage(lang) {
    localStorage.setItem("selectedLanguage", lang);
    document.documentElement.lang = lang; // Update HTML language attribute

    // Apply translations
    document.querySelector(".navlist .link:nth-child(1)").textContent = translations[lang].home;
    document.querySelector(".navlist .link:nth-child(2)").textContent = translations[lang].project;
    document.querySelector(".navlist .link:nth-child(3)").textContent = translations[lang].innovation;
    document.querySelector(".navlist .link:nth-child(4)").textContent = translations[lang].community;
    document.querySelector(".navlist .link:nth-child(5)").textContent = translations[lang].login;
    document.querySelector(".register").textContent = translations[lang].register;
    document.querySelector(".sign-in").textContent = translations[lang].signIn;

    // Adjust for RTL languages
    if (lang === "ar") {
        document.body.style.direction = "rtl"; // Right-to-left
        document.body.style.textAlign = "right";
    } else {
        document.body.style.direction = "ltr"; // Left-to-right
        document.body.style.textAlign = "left";
    }
}

// Detect saved language
const savedLang = localStorage.getItem("selectedLanguage") || "en";
setLanguage(savedLang);

// Handle language selection
langOptions.forEach((option) => {
    option.addEventListener("click", function () {
        const selectedLang = this.textContent === "العربية" ? "ar" : "en"; // Add more languages here
        setLanguage(selectedLang);
    });
});

// Toggle dropdown
langBtn.addEventListener("click", function () {
    langDropdown.classList.toggle("active");
});

document.addEventListener("click", function (e) {
    if (!langDropdown.contains(e.target) && !langBtn.contains(e.target)) {
        langDropdown.classList.remove("active");
    }
});
});