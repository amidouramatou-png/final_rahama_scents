<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

// Must be logged in as customer
if (!isLoggedIn() || getUserRole() !== 'customer') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get order stats
$total_orders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE user_id = $user_id")->fetch_assoc()['c'];
$pending_orders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE user_id = $user_id AND status IN ('pending', 'processing', 'shipped')")->fetch_assoc()['c'];

// Get recent orders
$recent_orders = $conn->query("SELECT * FROM orders WHERE user_id = $user_id ORDER BY order_date DESC LIMIT 3")->fetch_all(MYSQLI_ASSOC);

// Cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) $cart_count += $item['quantity'];
}

// Get featured products
$featured = $conn->query("SELECT * FROM products WHERE stock > 0 ORDER BY RAND() LIMIT 4")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Dashboard - Rahama's Scents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="customer_dashboard.css">
</head>
<body>

<!-- Header -->
<div class="header">
    <h1><i class="fas fa-spa"></i> Rahama's Scents</h1>
    <div class="header-links">
        <a href="shop.php"><i class="fas fa-store"></i> Shop</a>
        <a href="my_orders.php"><i class="fas fa-box"></i> Orders</a>
        <a href="cart.php" class="cart-link"><i class="fas fa-shopping-cart"></i> Cart (<?= $cart_count ?>)</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="container">
    <!-- Welcome Section -->
    <div class="welcome-card">
        <div class="welcome-info">
            <h2>Welcome back, <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?>! 👋</h2>
            <p>Ready to discover your perfect scent today?</p>
        </div>
        <div class="welcome-stats">
            <div class="stat">
                <div class="stat-value"><?= $total_orders ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?= $pending_orders ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?= $cart_count ?></div>
                <div class="stat-label">Cart Items</div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="shop.php" class="action-card">
            <i class="fas fa-shopping-bag"></i>
            <h3>Browse Shop</h3>
            <p>Explore our scents</p>
        </a>
        <a href="cart.php" class="action-card">
            <i class="fas fa-shopping-cart"></i>
            <h3>My Cart</h3>
            <p><?= $cart_count ?> items</p>
        </a>
        <a href="my_orders.php" class="action-card">
            <i class="fas fa-truck"></i>
            <h3>Track Orders</h3>
            <p>View delivery status</p>
        </a>
        <a href="profile.php" class="action-card">
            <i class="fas fa-user-cog"></i>
            <h3>My Profile</h3>
            <p>Account settings</p>
        </a>
    </div>
    
    <!-- Two Columns -->
    <div class="two-columns">
        <!-- Recent Orders -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-clock"></i> Recent Orders</h3>
                <a href="my_orders.php">View All →</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No orders yet</p>
                        <a href="shop.php" style="color: #d97528;">Start shopping →</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                    <div class="order-item">
                        <div>
                            <div class="order-id">Order #<?= $order['id'] ?></div>
                            <div class="order-date"><?= date('M d, Y', strtotime($order['order_date'])) ?></div>
                        </div>
                        <div class="order-amount">GHC <?= number_format($order['total_amount'], 2) ?></div>
                        <span class="status <?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Featured Products -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-star"></i> Featured Scents</h3>
                <a href="shop.php">See All →</a>
            </div>
            <div class="card-body">
                <div class="product-grid">
                    <?php foreach ($featured as $product): ?>
                    <div class="product-mini">
                        <img src="<?= $product['image'] ?: 'placeholder.jpg' ?>" alt="">
                        <div class="product-mini-info">
                            <div class="product-mini-name"><?= htmlspecialchars($product['name']) ?></div>
                            <div class="product-mini-price">GHC <?= number_format($product['price'], 2) ?></div>
                            <form action="cart.php" method="POST" style="margin-top: 5px;">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <input type="hidden" name="action" value="add">
                                <button type="submit" class="btn-add"><i class="fas fa-plus"></i> Add</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="customer_dashboard.js"></script>
</body>
</html>