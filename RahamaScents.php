<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';
$page_title = "Luxury Fragrances";


// Handle contact form submission
$contact_success = '';
$contact_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;

    if (empty($name) || empty($email) || empty($message)) {
        $contact_error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contact_error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("INSERT INTO messages (user_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            $contact_success = "Your message has been sent successfully!";
        } else {
            $contact_error = "Failed to send message. Please try again.";
        }
    }
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.6;
        color: #333;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    /* Header */
    .header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        padding: 15px 0;
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
        box-shadow: 0 2px 20px rgba(0,0,0,0.3);
    }
    
    .header .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .logo img {
        border-radius: 50%;
        transition: transform 0.3s;
    }
    
    .logo img:hover {
        transform: scale(1.05);
    }
    
    .nav {
        display: flex;
        align-items: center;
        gap: 30px;
    }
    
    .nav a {
        color: white;
        text-decoration: none;
        font-weight: 500;
        padding: 10px 0;
        position: relative;
        transition: color 0.3s;
    }
    
    .nav a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: #d97528;
        transition: width 0.3s;
    }
    
    .nav a:hover::after,
    .nav a.active::after {
        width: 100%;
    }
    
    .nav a:hover,
    .nav a.active {
        color: #d97528;
    }
    
    .btn-login {
        background: #d97528 !important;
        padding: 10px 25px !important;
        border-radius: 25px;
    }
    
    .btn-login:hover {
        background: #b8611f !important;
        transform: translateY(-2px);
    }
    
    .btn-login::after {
        display: none !important;
    }
    
    /* Hero Section */
    .hero {
        background: linear-gradient(rgba(26, 26, 46, 0.8), rgba(26, 26, 46, 0.9)), url('hero-bg.jpg') center/cover;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: white;
        padding-top: 100px;
    }
    
    .hero h1 {
        font-size: 4rem;
        margin-bottom: 20px;
        animation: fadeInUp 1s ease;
    }
    
    .hero p {
        font-size: 1.5rem;
        margin-bottom: 40px;
        opacity: 0.9;
        animation: fadeInUp 1s ease 0.2s backwards;
    }
    
    .btn-primary {
        display: inline-block;
        background: linear-gradient(135deg, #d97528 0%, #b8611f 100%);
        color: white;
        padding: 18px 50px;
        border-radius: 50px;
        text-decoration: none;
        font-size: 1.2rem;
        font-weight: 600;
        transition: all 0.3s;
        animation: fadeInUp 1s ease 0.4s backwards;
        box-shadow: 0 5px 20px rgba(217, 117, 40, 0.4);
    }
    
    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(217, 117, 40, 0.5);
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Features */
    .features {
        padding: 80px 0;
        background: #f8f9fa;
    }
    
    .features .container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
    }
    
    .feature {
        background: white;
        padding: 40px 30px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
    }
    
    .feature::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(135deg, #d97528 0%, #b8611f 100%);
        transform: scaleX(0);
        transition: transform 0.3s;
    }
    
    .feature:hover::before {
        transform: scaleX(1);
    }
    
    .feature:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    }
    
    .feature i {
        font-size: 3rem;
        color: #d97528;
        margin-bottom: 20px;
    }
    
    .feature h3 {
        color: #1a1a2e;
        margin-bottom: 10px;
        font-size: 1.3rem;
    }
    
    .feature p {
        color: #666;
    }
    
    /* Sections */
    .section {
        padding: 100px 0;
    }
    
    .section-title {
        text-align: center;
        font-size: 2.5rem;
        color: #1a1a2e;
        margin-bottom: 60px;
        position: relative;
    }
    
    .section-title::after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(135deg, #d97528 0%, #b8611f 100%);
        border-radius: 2px;
    }
    
    /* About Section */
    .about-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
    }
    
    .about-section h3 {
        font-size: 2rem;
        color: #1a1a2e;
        margin-bottom: 20px;
    }
    
    .about-section p {
        color: #666;
        margin-bottom: 20px;
        font-size: 1.1rem;
    }
    
    .about-stats {
        display: flex;
        gap: 40px;
        margin-top: 40px;
    }
    
    .stat {
        text-align: center;
    }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: bold;
        color: #d97528;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }
    
    .about-image-placeholder img {
        width: 100%;
        border-radius: 20px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.2);
    }
    
    /* Contact Section */
    #contact {
        background: #f8f9fa;
    }
    
    .contact-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
    }
    
    .contact-info h3,
    .form-container h3 {
        font-size: 1.8rem;
        color: #1a1a2e;
        margin-bottom: 30px;
    }
    
    .contact-item {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .contact-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #d97528 0%, #b8611f 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    
    .contact-item h4 {
        color: #1a1a2e;
        margin-bottom: 5px;
    }
    
    .contact-item p {
        color: #666;
    }
    
    .form-container {
        background: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    
    .form-control {
        width: 100%;
        padding: 14px;
        border: 2px solid #eee;
        border-radius: 10px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #d97528;
    }
    
    textarea.form-control {
        resize: vertical;
        min-height: 120px;
    }
    
    .btn {
        width: 100%;
        padding: 15px 30px;
        background: linear-gradient(135deg, #d97528 0%, #b8611f 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(217, 117, 40, 0.4);
    }
    
    /* Alert Messages */
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .alert-success {
        background: #d1fae5;
        color: #065f46;
    }
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
    }
    
    /* Scroll to top button */
    .scroll-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #d97528 0%, #b8611f 100%);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.2rem;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
        z-index: 999;
        box-shadow: 0 5px 20px rgba(217, 117, 40, 0.4);
    }
    
    .scroll-top.show {
        opacity: 1;
        visibility: visible;
    }
    
    .scroll-top:hover {
        transform: translateY(-5px);
    }
    
    /* Mobile Menu */
    .menu-toggle {
        display: none;
        flex-direction: column;
        gap: 5px;
        cursor: pointer;
        padding: 10px;
    }
    
    .menu-toggle span {
        width: 25px;
        height: 3px;
        background: white;
        transition: all 0.3s;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .features .container,
        .about-section,
        .contact-section {
            grid-template-columns: 1fr;
        }
        
        .about-image-placeholder {
            order: -1;
        }
    }
    
    @media (max-width: 768px) {
        .menu-toggle {
            display: flex;
        }
        
        .nav {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100vh;
            background: #1a1a2e;
            flex-direction: column;
            padding: 80px 40px;
            transition: right 0.3s;
            gap: 20px;
        }
        
        .nav.active {
            right: 0;
        }
        
        .hero h1 {
            font-size: 2.5rem;
        }
        
        .hero p {
            font-size: 1.1rem;
        }
        
        .about-stats {
            flex-direction: column;
            gap: 20px;
        }
    }
</style>

<!-- Header -->
<header class="header">
    <div class="container">
        <div class="logo">
            <img src="Logo2.jpg" alt="Rahama's Scents" height="80" onerror="this.style.display='none'">
        </div>
        <nav class="nav" id="mainNav">
            <a href="RahamaScents.php" class="active">Home</a>
            <a href="shop.php">Shop</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
            <a href="login.php" class="btn-login"><i class="fas fa-user"></i> Login</a>
        </nav>
        <div class="menu-toggle" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</header>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1>Luxury Fragrances</h1>
        <p>Discover our premium collection of scents</p>
        <a href="shop.php" class="btn-primary"><i class="fas fa-shopping-bag"></i> Shop Now</a>
    </div>
</section>

<!-- Features -->
<section class="features">
    <div class="container">
        <div class="feature" data-aos="fade-up">
            <i class="fas fa-spray-can"></i>
            <h3>Shop Fragrances</h3>
            <p>Browse our exclusive collection of premium scents</p>
        </div>
        <div class="feature" data-aos="fade-up" data-aos-delay="100">
            <i class="fas fa-truck"></i>
            <h3>Track Orders</h3>
            <p>Real-time delivery tracking for peace of mind</p>
        </div>
        <div class="feature" data-aos="fade-up" data-aos-delay="200">
            <i class="fas fa-headset"></i>
            <h3>24/7 Support</h3>
            <p>Our team is always here to help you</p>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="section" id="about">
    <div class="container">
        <h2 class="section-title">Our Story</h2>
        <div class="about-section">
            <div>
                <h3>Crafting Memories Through Scents</h3>
                <p>Rahama's Scents was born from a passion for creating unforgettable olfactory experiences. Our founder, Rahama, grew up surrounded by the rich aromas of West African spices, French perfumeries, and Middle Eastern incense.</p>
                <p>Each fragrance in our collection tells a story – from the vibrant markets of Marrakech to the serene lavender fields of Provence. We source only the finest ingredients from around the world and blend them with artisanal craftsmanship.</p>
                
                <div class="about-stats">
                    <div class="stat">
                        <div class="stat-value" id="countScents">0</div>
                        <div class="stat-label">Premium Scents</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value" id="countCountries">0</div>
                        <div class="stat-label">Countries</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value" id="countCustomers">0</div>
                        <div class="stat-label">Happy Customers</div>
                    </div>
                </div>
            </div>
            <div class="about-image-placeholder">
                <img src="about.jpg" alt="Rahama's Scents" onerror="this.parentElement.innerHTML='<div style=\'background:linear-gradient(135deg,#d97528,#b8611f);height:400px;border-radius:20px;display:flex;align-items:center;justify-content:center;color:white;font-size:3rem;\'><i class=\'fas fa-spa\'></i></div>'">
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="section" id="contact">
    <div class="container">
        <h2 class="section-title">Contact Us</h2>
        <div class="contact-section">
            <div class="contact-info">
                <h3>Get in Touch</h3>
                
                <div class="contact-item">
                    <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div>
                        <h4>Address</h4>
                        <p>Bobiel<br>Niamey, Niger</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon"><i class="fas fa-phone"></i></div>
                    <div>
                        <h4>Phone</h4>
                        <p>+227 85 90 28 57</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                    <div>
                        <h4>Email</h4>
                        <p>rahamascents@gmail.com</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon"><i class="fas fa-clock"></i></div>
                    <div>
                        <h4>Business Hours</h4>
                        <p>Monday - Friday: 9am - 6pm<br>Saturday: 10am - 4pm</p>
                    </div>
                </div>
                
                <!-- Social Media -->
                <div style="margin-top: 30px;">
                    <h4 style="margin-bottom: 15px;">Follow Us</h4>
                    <div style="display: flex; gap: 15px;">
                        <a href="https://www.facebook.com/profile.php?id=61584750787493" style="width: 45px; height: 45px; background: #d97528; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.3s;">
                            <i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/rahmascents?igsh=eHc4dmJucWZ3Znhq" style="width: 45px; height: 45px; background: #d97528; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.3s;">
                            <i class="fab fa-instagram"></i></a>
                        <a href="https://www.tiktok.com/@rahamascents?_r=1&_t=ZM-927ifpcGiZY" class="social-icon" style="width: 45px; height: 45px; background: #d97528; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.3s;">
                            <i class="fab fa-tiktok"></i></a>
                        <a href="https://www.snapchat.com/add/ramatouamidou22?share_id=0M93ZtAxSuI&locale=fr-BJ" style="width: 45px; height: 45px; background: #d97528; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.3s;">
                            <i class="fab fa-snapchat"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="form-container">
                <h3>Send us a Message</h3>
                
                <?php if ($contact_success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= $contact_success ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($contact_error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?= $contact_error ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="RahamaScents.php#contact">
                    <input type="hidden" name="contact_submit" value="1">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Your name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Your email" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" name="subject" id="subject" class="form-control" placeholder="Message subject">
                    </div>
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea name="message" id="message" class="form-control" rows="5" placeholder="Your message..." required></textarea>
                    </div>
                    <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Send Message</button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Scroll to Top Button -->
<button class="scroll-top" id="scrollTop" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
// Mobile Menu Toggle
function toggleMenu() {
    document.getElementById('mainNav').classList.toggle('active');
}

// Scroll to Top
const scrollTopBtn = document.getElementById('scrollTop');

window.addEventListener('scroll', function() {
    if (window.scrollY > 500) {
        scrollTopBtn.classList.add('show');
    } else {
        scrollTopBtn.classList.remove('show');
    }
    
    // Header shadow on scroll
    const header = document.querySelector('.header');
    if (window.scrollY > 100) {
        header.style.boxShadow = '0 5px 30px rgba(0,0,0,0.3)';
    } else {
        header.style.boxShadow = '0 2px 20px rgba(0,0,0,0.3)';
    }
});

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            // Close mobile menu
            document.getElementById('mainNav').classList.remove('active');
        }
    });
});

// Counter Animation
function animateCounter(elementId, target, suffix = '') {
    const element = document.getElementById(elementId);
    let current = 0;
    const increment = target / 50;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current) + suffix;
    }, 30);
}

// Start counter animation when section is visible
const aboutSection = document.getElementById('about');
let counterStarted = false;

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !counterStarted) {
            counterStarted = true;
            animateCounter('countScents', 8);
            animateCounter('countCountries', 50, '+');
            animateCounter('countCustomers', 10, 'K+');
        }
    });
}, { threshold: 0.5 });

observer.observe(aboutSection);

// Add hover effect to social icons
document.querySelectorAll('a[style*="border-radius: 50%"]').forEach(icon => {
    icon.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-3px) scale(1.1)';
    });
    icon.addEventListener('mouseleave', function() {
        this.style.transform = '';
    });
});
</script>

<?php

require_once 'footer.php';
?>