<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

// Redirect if not logged in or cart empty
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// Calculate totals
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $qty = isset($item['qty']) ? $item['qty'] : (isset($item['quantity']) ? $item['quantity'] : 1);
    $subtotal += $item['price'] * $qty;
}

$delivery_fee = 15.00;
$total = $subtotal + $delivery_fee;

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $full_name = $conn->real_escape_string(trim($_POST['full_name']));
    $address = $conn->real_escape_string(trim($_POST['address']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $payment_method = $conn->real_escape_string(trim($_POST['payment']));
    
    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("idss", $user_id, $total, $address, $payment_method);
    $stmt->execute();
    $order_id = $conn->insert_id;

    // Notify admin
    $notification_msg = "New order #$order_id from $full_name - GHC " . number_format($total, 2) . " ($payment_method)";
    $stmt = $conn->prepare("INSERT INTO notifications (type, message, order_id) VALUES ('new_order', ?, ?)");
    $stmt->bind_param("si", $notification_msg, $order_id);
    $stmt->execute();
    
    // Insert order items
    foreach ($_SESSION['cart'] as $item) {
        $qty = isset($item['qty']) ? $item['qty'] : (isset($item['quantity']) ? $item['quantity'] : 1);
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $order_id, $item['id'], $qty, $item['price']);
        $stmt->execute();
        $conn->query("UPDATE products SET stock = stock - $qty WHERE id = {$item['id']}");
    }
    
    // Clear cart
    $_SESSION['cart'] = [];
    header('Location: order_success.php?id=' . $order_id);
    exit();
}

$total_formatted = number_format($total, 2);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout - Rahama's Scents</title>
    <link rel="stylesheet" href="checkout.css">
</head>
<body>
<div class="container">
    <h1><i class="fas fa-credit-card"></i> Checkout</h1>
    <a href="cart.php"><i class="fas fa-arrow-left"></i> Back to Cart</a>
    <form method="POST" action="" id="checkoutForm">
        <div class="card">
            <!-- Shipping Details -->
            <h2><i class="fas fa-truck"></i> Shipping Details</h2>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" required>
            </div>
            <div class="form-group">
                <label for="address">Delivery Address</label>
                <textarea id="address" name="address" rows="3" placeholder="Enter your full address" required></textarea>
            </div>
        </div>

        <div class="card">
            <!-- Order Summary -->
            <h2><i class="fas fa-receipt"></i> Order Summary</h2>
            <?php foreach ($_SESSION['cart'] as $item): ?>
                <div class="order-item">
                    <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo $qty; ?></span>
                    <span>GHC <?php echo number_format($item['price'] * $qty, 2); ?></span>
                </div>
            <?php endforeach; ?>
            <div class="summary-row">
                <span>Subtotal</span>
                <span>GHC <?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Delivery Fee</span>
                <span>GHC <?php echo number_format($delivery_fee, 2); ?></span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span>GHC <?php echo $total_formatted; ?></span>
            </div>
        </div>

        <div class="card">
            <!-- Payment Methods -->
            <h2><i class="fas fa-wallet"></i> Payment Method</h2>
            <div class="payment-methods">
                <label class="payment-option selected" onclick="selectPayment('cod', this)">
                    <input type="radio" name="payment" value="Cash on Delivery" checked>
                    <span class="payment-radio"></span>
                    <div class="payment-icon cod"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="payment-details">
                        <h4>Cash on Delivery</h4>
                        <p>Pay when you receive your order</p>
                    </div>
                </label>
                <label class="payment-option" onclick="selectPayment('visa', this)">
                    <input type="radio" name="payment" value="Visa/Card">
                    <span class="payment-radio"></span>
                    <div class="payment-icon visa">VISA</div>
                    <div class="payment-details">
                        <h4>Credit/Debit Card</h4>
                        <p>Visa, Mastercard, Verve accepted</p>
                    </div>
                </label>
                <label class="payment-option" onclick="selectPayment('momo', this)">
                    <input type="radio" name="payment" value="MoMo Ghana">
                    <span class="payment-radio"></span>
                    <div class="payment-icon momo">MTN<br>MoMo</div>
                    <div class="payment-details">
                        <h4>Mobile Money (MoMo)</h4>
                        <p>MTN, Vodafone Cash, AirtelTigo Money</p>
                    </div>
                </label>
            </div>
        </div>

        <button type="submit" class="btn-order" id="submitBtn" data-total="<?php echo $total_formatted; ?>">
            <i class="fas fa-check"></i> Place Order - GHC<?php echo $total_formatted; ?>
        </button>
    </form>
</div>
<script src="checkout.js"></script>
</body>
</html>