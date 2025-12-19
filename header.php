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
    <header class="header">
        <div class="container">
            <div class="logo">
                <a href="RahamaScents.php">
                    <img src="Logo2.jpg" alt="Rahama's Scents" height="200">
                </a>
            </div>
            <nav class="nav">
                <a href="index.php">Home</a>
                <a href="shop.php">Shop</a>
                <a href="contact.php">Contact</a>
                <a href="about.php">About Us</a>
                
                <?php if (isLoggedIn()): ?>
                    <?php if (getUserRole() == 'customer'): ?>
                        <a href="my_orders.php">My Orders</a>
                        <a href="cart.php"><i class="fas fa-shopping-cart"></i> (<?= $cart_count ?>)</a>
                    <?php elseif (getUserRole() == 'admin'): ?>
                        <a href="admin.php">Admin Panel</a>
                    <?php elseif (getUserRole() == 'delivery'): ?>
                        <a href="delivery_dashboard.php">Delivery Panel</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn-login">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn-login">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>