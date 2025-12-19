<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

if (!isLoggedIn()) { header('Location: login.php'); exit(); }

$user_id = $_SESSION['user_id'];

// Fetch customer's orders with items
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch order items for each order
foreach ($orders as &$order) {
    $stmt = $conn->prepare("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt->bind_param("i", $order['id']);
    $stmt->execute();
    $order['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
unset($order); // Break the reference
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Orders - Rahama's Scents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: auto; }
        h1 { color: #d97528; margin-bottom: 20px; }
        a { color: #d97528; text-decoration: none; font-weight: bold; }
        
        /* Filter Buttons */
        .filters { display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap; }
        .filter-btn { padding: 8px 16px; border: 2px solid #d97528; background: white; color: #d97528; border-radius: 20px; cursor: pointer; font-weight: bold; }
        .filter-btn:hover, .filter-btn.active { background: #d97528; color: white; }
        
        /* Order Card */
        .order-card { background: white; border-radius: 10px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .order-header { padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .order-header:hover { background: #fafafa; }
        .order-info { display: flex; gap: 20px; align-items: center; }
        .order-id { font-weight: bold; color: #333; }
        .order-date { color: #888; font-size: 0.9rem; }
        .order-total { font-weight: bold; color: #d97528; }
        .status { padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .status.pending { background: #fee2e2; color: #991b1b; }
        .status.processing { background: #fef3c7; color: #92400e; }
        .status.shipped { background: #dbeafe; color: #1e40af; }
        .status.delivered { background: #d1fae5; color: #065f46; }
        .toggle-icon { transition: transform 0.3s; }
        .order-card.open .toggle-icon { transform: rotate(180deg); }
        
        /* Order Details (hidden by default) */
        .order-details { display: none; padding: 0 20px 20px; border-top: 1px solid #eee; }
        .order-card.open .order-details { display: block; }
        .order-item { display: flex; align-items: center; gap: 15px; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .order-item:last-child { border-bottom: none; }
        .order-item img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; }
        .item-name { flex: 1; font-weight: bold; }
        .item-qty { color: #888; }
        .item-price { color: #d97528; font-weight: bold; }
        
        .empty { text-align: center; padding: 50px; background: white; border-radius: 10px; color: #888; }
        .no-results { display: none; text-align: center; padding: 30px; color: #888; }
        
        /* Delivery Info */
        .delivery-info { background: #f0f9ff; padding: 15px; border-radius: 10px; margin-top: 15px; }
        .delivery-info h4 { color: #0369a1; margin-bottom: 10px; }
        .delivery-info .driver-details { display: flex; align-items: center; gap: 15px; }
        .delivery-info .driver-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
        .delivery-info .driver-placeholder { width: 50px; height: 50px; border-radius: 50%; background: #d97528; display: flex; align-items: center; justify-content: center; color: white; }
        .delivery-info p { margin: 3px 0; }
        .delivery-info .driver-name { font-weight: bold; }
        .delivery-info .driver-contact { color: #666; }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-box"></i> My Orders</h1>
    <a href="shop.php">← Back to Shop</a>
    
    <?php if (empty($orders)): ?>
        <div class="empty">
            <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <p>You haven't placed any orders yet.</p>
            <br>
            <a href="shop.php">Start Shopping</a>
        </div>
    <?php else: ?>
        <!-- Filter Buttons -->
        <div class="filters">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="pending">Pending</button>
            <button class="filter-btn" data-filter="processing">Processing</button>
            <button class="filter-btn" data-filter="shipped">Shipped</button>
            <button class="filter-btn" data-filter="delivered">Delivered</button>
        </div>
        
        <div id="ordersList">
            <?php foreach ($orders as $order): ?>
            <div class="order-card" data-status="<?= $order['status'] ?>">
                <div class="order-header" onclick="toggleOrder(this)">
                    <div class="order-info">
                        <span class="order-id">Order #<?= $order['id'] ?></span>
                        <span class="order-date"><?= date('M d, Y', strtotime($order['order_date'])) ?></span>
                        <span class="order-total">GHC <?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span class="status <?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                </div>
                <div class="order-details">
                    <?php foreach ($order['items'] as $item): ?>
                    <div class="order-item">
                        <img src="<?= $item['image'] ?: 'placeholder.jpg' ?>" alt="">
                        <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                        <span class="item-qty">× <?= $item['quantity'] ?></span>
                        <span class="item-price">GHC <?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if ($order['status'] === 'shipped' || $order['status'] === 'delivered'): 
                        $del_stmt = $conn->prepare("SELECT full_name, phone, picture, vehicle_type FROM delivery_staff WHERE id = ?");
                        $del_stmt->bind_param("i", $order['delivery_staff_id']);
                        $del_stmt->execute();
                        $delivery_person = $del_stmt->get_result()->fetch_assoc();
                    ?>
                        <?php if ($delivery_person): ?>
                        <div class="delivery-info">
                            <h4><i class="fas fa-truck"></i> Your Delivery Person</h4>
                            <div class="driver-details">
                                <?php if (!empty($delivery_person['picture'])): ?>
                                    <img src="<?= htmlspecialchars($delivery_person['picture']) ?>" class="driver-avatar" alt="">
                                <?php else: ?>
                                    <div class="driver-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p class="driver-name"><?= htmlspecialchars($delivery_person['full_name']) ?></p>
                                    <p class="driver-contact"><i class="fas fa-phone"></i> <?= htmlspecialchars($delivery_person['phone']) ?></p>
                                    <p class="driver-contact"><i class="fas fa-<?= $delivery_person['vehicle_type'] === 'motorcycle' ? 'motorcycle' : 'car' ?>"></i> <?= ucfirst($delivery_person['vehicle_type']) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="no-results" id="noResults">
            <i class="fas fa-search"></i> No orders found with this status.
        </div>
    <?php endif; ?>
</div>

<script>
// Toggle order details
function toggleOrder(header) {
    const card = header.parentElement;
    card.classList.toggle('open');
}

// Filter orders by status
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        const filter = btn.dataset.filter;
        const orders = document.querySelectorAll('.order-card');
        let visibleCount = 0;
        
        orders.forEach(order => {
            if (filter === 'all' || order.dataset.status === filter) {
                order.style.display = 'block';
                visibleCount++;
            } else {
                order.style.display = 'none';
            }
        });
        
        document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
    });
});

// Auto-open first order
document.querySelector('.order-card')?.classList.add('open');
</script>

</body>
</html>