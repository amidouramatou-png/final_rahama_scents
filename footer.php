<?php
require_once 'config.php';

// Cart count from session
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['qty'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?>Rahama's Scents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="RahamaScents.css">
</head>
<body>

<!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-container">
                <div>
                    <div class="footer-logo">Rahama's Scents</div>
                    <p class="footer-description">Luxury fragrances crafted with passion and precision. Experience the essence of elegance.</p>
                    <div class="social-icons">
                        <a href="https://www.facebook.com/profile.php?id=61584750787493" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/rahmascents?igsh=eHc4dmJucWZ3Znhq" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.tiktok.com/@rahamascents?_r=1&_t=ZM-927ifpcGiZY" class="social-icon"><i class="fab fa-tiktok"></i></a>
                        <a href="https://www.snapchat.com/add/ramatouamidou22?share_id=0M93ZtAxSuI&locale=fr-BJ" class="social-icon"><i class="fab fa-snapchat"></i></a>
                    </div>
                </div>
                
                <div>
                    <div class="footer-title">Quick Links</div>
                    <ul class="footer-links">
                        <li><a href="RahamaScents.php">Home</a></li>
                        <li><a href="shop.php">Shop</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <div class="footer-title">Contact Info</div>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> Bobiel/ Niamey Niger </li>
                        <li><i class="fas fa-phone"></i> +227 85 90 28 57</li>
                        <li><i class="fas fa-envelope"></i> rahamascents@gmail.com</li>
                        <li><i class="fas fa-clock"></i> Mon-Fri: 9am-6pm</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>© <?php echo date('Y'); ?> Rahama's Scents. All rights reserved. Designed with <i class="fas fa-heart" style="color: #ff6b6b;"></i> for fragrance lovers worldwide.</p>
            </div>
        </div>
    </footer>
</body>
</html>