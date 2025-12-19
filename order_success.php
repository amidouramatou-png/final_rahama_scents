<?php
require_once 'config.php';

$order_id = $_GET['id'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order Success - Rahama's Scents</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #d97528; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: white; padding: 50px; border-radius: 15px; text-align: center; max-width: 400px; }
        .icon { font-size: 4rem; color: #27ae60; margin-bottom: 20px; }
        h1 { color: #333; margin-bottom: 10px; }
        p { color: #666; margin-bottom: 20px; }
        .order-id { background: #f5f5f5; padding: 10px 20px; border-radius: 8px; font-weight: bold; color: #d97528; margin-bottom: 20px; display: inline-block; }
        .btn { display: inline-block; padding: 12px 30px; background: #d97528; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">✓</div>
    <h1>Order Placed!</h1>
    <p>Thank you for your order.</p>
    <div class="order-id">Order<?= $order_id ?></div>
    <p>We will deliver your items soon.</p>
    <a href="shop.php" class="btn">Continue Shopping</a>
</div>
</body>
</html>