<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

// Start cart session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $product_id = $_POST['product_id'];
    
    if ($action === 'add') {
        // Get product details
        $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if ($product) {
            // Check if already in cart
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity']++;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'image' => $product['image'],
                    'quantity' => 1
                ];
            }
        }
    }
    
    if ($action === 'update') {
        $quantity = (int)$_POST['quantity'];
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    
    if ($action === 'remove') {
        unset($_SESSION['cart'][$product_id]);
    }
    
    header('Location: cart.php');
    exit();
}

// Calculate totals
$cart_total = 0;
$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
    $cart_count += $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Rahama's Scents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header h1 { color: #d97528; }
        .header a {
            color: #d97528;
            text-decoration: none;
            font-weight: bold;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .page-title {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        /* Cart Items */
        .cart-item {
            display: flex;
            align-items: center;
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            gap: 20px;
        }
        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .item-price {
            color: #d97528;
            font-weight: bold;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .quantity-controls input {
            width: 60px;
            padding: 8px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
        .btn-update {
            padding: 8px 15px;
            background: #d97528;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-remove {
            padding: 8px 15px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .item-subtotal {
            font-weight: bold;
            color: #333;
            min-width: 100px;
            text-align: right;
        }
        
        /* Cart Summary */
        .cart-summary {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .summary-total {
            font-size: 1.3rem;
            font-weight: bold;
            color: #d97528;
            border-bottom: none;
        }
        .btn-checkout {
            width: 100%;
            padding: 15px;
            background: #d97528;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px;
        }
        .btn-checkout:hover {
            background: #b8651f;
        }
        .btn-continue {
            display: block;
            text-align: center;
            color: #d97528;
            text-decoration: none;
            margin-top: 15px;
            font-weight: bold;
        }
        
        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 10px;
        }
        .empty-cart i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        .empty-cart p {
            color: #888;
            margin-bottom: 20px;
        }
        .btn-shop {
            display: inline-block;
            padding: 12px 30px;
            background: #d97528;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <h1><i class="fas fa-shopping-cart"></i> Shopping Cart</h1>
    <a href="shop.php"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
</div>

<div class="container">
    <h2 class="page-title">Your Cart (<?= $cart_count ?> items)</h2>
    
    <?php if (empty($_SESSION['cart'])): ?>
        <!-- Empty Cart -->
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <p>Your cart is empty!</p>
            <a href="shop.php" class="btn-shop">Start Shopping</a>
        </div>
    <?php else: ?>
        <!-- Cart Items -->
        <?php foreach ($_SESSION['cart'] as $item): ?>
            <div class="cart-item">
                <img src="<?= $item['image'] ?: 'placeholder.jpg' ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                
                <div class="item-details">
                    <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                    <div class="item-price">GHC <?= number_format($item['price'], 2) ?></div>
                </div>
                
                <form method="POST" class="quantity-controls">
                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1">
                    <button type="submit" class="btn-update">Update</button>
                </form>
                
                <form method="POST">
                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                    <input type="hidden" name="action" value="remove">
                    <button type="submit" class="btn-remove"><i class="fas fa-trash"></i></button>
                </form>
                
                <div class="item-subtotal">
                    GHS <?= number_format($item['price'] * $item['quantity'], 2) ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Cart Summary -->
        <div class="cart-summary">
            <div class="summary-row">
                <span>Subtotal (<?= $cart_count ?> items)</span>
                <span>GHS <?= number_format($cart_total, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Delivery</span>
                <span>GHS</span>
            </div>
            <div class="summary-row summary-total">
                <span>Total</span>
                <span>GHC <?= number_format($cart_total, 2) ?></span>
            </div>
            
            <?php if (isLoggedIn()): ?>
                <a href="checkout.php"><button class="btn-checkout">Proceed to Checkout</button></a>
            <?php else: ?>
                <a href="login.php"><button class="btn-checkout">Login to Checkout</button></a>
            <?php endif; ?>
            
            <a href="shop.php" class="btn-continue">← Continue Shopping</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>