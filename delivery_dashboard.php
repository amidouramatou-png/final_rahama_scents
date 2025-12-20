<?php
session_start();

// Check if delivery staff is logged in
if (!isset($_SESSION['delivery_id'])) {
    header("Location: delivery_login.php");
    exit();
}

$host = 'sql207.infinityfree.com';
$dbname = 'if0_40722542_rahama_scents';
$username = 'if0_40722542';
$password = 'qpmME4f74uIj';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$staff_id = $_SESSION['delivery_id'];

// Fetch staff info
$stmt = $pdo->prepare("SELECT * FROM delivery_staff WHERE id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept_order'])) {
        $order_id = $_POST['order_id'];
        $stmt = $pdo->prepare("UPDATE orders SET status = 'shipped', delivery_staff_id = ? WHERE id = ?");
        $stmt->execute([$staff_id, $order_id]);
    } elseif (isset($_POST['complete_order'])) {
        $order_id = $_POST['order_id'];
        $stmt = $pdo->prepare("UPDATE orders SET status = 'delivered' WHERE id = ? AND delivery_staff_id = ?");
        $stmt->execute([$order_id, $staff_id]);
    }
    header("Location: delivery_dashboard.php");
    exit();
}

// Get statistics
$to_pickup = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'processing')")->fetchColumn();

$in_transit = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'shipped' AND delivery_staff_id = ?");
$in_transit->execute([$staff_id]);
$in_transit = $in_transit->fetchColumn();

$delivered_today = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'delivered' AND delivery_staff_id = ? AND DATE(order_date) = CURDATE()");
$delivered_today->execute([$staff_id]);
$delivered_today = $delivered_today->fetchColumn();

// Get available orders
$available_orders = $pdo->query("
    SELECT o.*, u.full_name, u.phone, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.status IN ('pending', 'processing') 
    ORDER BY o.order_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get my active orders
$my_orders = $pdo->prepare("
    SELECT o.*, u.full_name, u.phone, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.status = 'shipped' AND o.delivery_staff_id = ?
    ORDER BY o.order_date DESC
");
$my_orders->execute([$staff_id]);
$my_orders = $my_orders->fetchAll(PDO::FETCH_ASSOC);

// Get notifications
$notifications = $pdo->query("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$unread_count = count($notifications);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard - Rahama's Scents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header h1 { color: #d97528; font-size: 1.5rem; }
        .header-right { display: flex; align-items: center; gap: 20px; }
        
        /* Notification Bell */
        .notification-bell {
            position: relative;
            color: white;
            font-size: 1.3rem;
            cursor: pointer;
            padding: 10px;
        }
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: bold;
        }
        
        /* Notification Dropdown */
        .notification-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            width: 350px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            z-index: 1000;
            margin-top: 10px;
            overflow: hidden;
        }
        .notification-dropdown.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: linear-gradient(135deg, #d97528 0%, #b8611f 100%);
            color: white;
        }
        .notification-header h4 { margin: 0; }
        .notification-header span {
            background: white;
            color: #d97528;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .notification-list {
            max-height: 350px;
            overflow-y: auto;
        }
        .notification-item {
            display: flex;
            gap: 12px;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            transition: background 0.3s;
            cursor: pointer;
        }
        .notification-item:hover { background: #f8f9fa; }
        .notification-icon {
            width: 45px;
            height: 45px;
            background: #fff5eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d97528;
            flex-shrink: 0;
            font-size: 1.1rem;
        }
        .notification-content { flex: 1; }
        .notification-content p {
            color: #333;
            font-size: 0.9rem;
            margin: 0 0 5px 0;
            line-height: 1.4;
        }
        .notification-time {
            color: #999;
            font-size: 0.75rem;
        }
        .notification-empty {
            padding: 40px 20px;
            text-align: center;
            color: #999;
        }
        .notification-empty i {
            font-size: 3rem;
            margin-bottom: 10px;
            color: #ddd;
        }
        
        /* Profile Dropdown */
        .profile-dropdown { position: relative; }
        .profile-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 25px;
            transition: background 0.3s;
        }
        .profile-btn:hover { background: rgba(255,255,255,0.1); }
        .profile-btn img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #d97528;
        }
        .profile-btn .default-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #d97528;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            min-width: 200px;
            z-index: 100;
            overflow: hidden;
            margin-top: 10px;
        }
        .dropdown-menu.show { display: block; }
        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            color: #333;
            text-decoration: none;
            transition: background 0.3s;
        }
        .dropdown-menu a:hover { background: #f5f5f5; }
        .dropdown-menu a.logout { color: #ef4444; border-top: 1px solid #eee; }
        
        /* Main Content */
        .main-content { padding: 30px; max-width: 1200px; margin: auto; }
        
        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, #d97528 0%, #b8611f 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 25px;
            box-shadow: 0 10px 30px rgba(217, 117, 40, 0.3);
        }
        .welcome-card img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
        }
        .welcome-card .default-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }
        .welcome-card h2 { font-size: 1.6rem; margin-bottom: 8px; }
        .welcome-card p { opacity: 0.9; font-size: 1rem; }
        
        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card i { font-size: 2.5rem; margin-bottom: 15px; }
        .stat-card .number { font-size: 2.5rem; font-weight: bold; color: #1a1a2e; }
        .stat-card .label { color: #666; margin-top: 5px; }
        .stat-card.pickup i { color: #f59e0b; }
        .stat-card.transit i { color: #3b82f6; }
        .stat-card.delivered i { color: #10b981; }
        
        /* Section Title */
        .section-title {
            font-size: 1.4rem;
            color: #1a1a2e;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title i { color: #d97528; }
        
        /* Orders Container */
        .orders-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .order-card {
            border: 2px solid #eee;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .order-card:hover {
            border-color: #d97528;
            box-shadow: 0 5px 20px rgba(217, 117, 40, 0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .order-id {
            font-weight: bold;
            color: #d97528;
            font-size: 1.1rem;
        }
        .order-amount {
            font-weight: bold;
            color: #1a1a2e;
            font-size: 1.2rem;
        }
        .order-details { color: #666; font-size: 0.95rem; }
        .order-details p {
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .order-details i { color: #d97528; width: 20px; }
        .order-actions { display: flex; gap: 10px; margin-top: 15px; }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        .btn-accept {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }
        .btn-complete {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
        .btn-complete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #999;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 15px;
            color: #ddd;
        }
        .empty-state p { font-size: 1.1rem; }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .welcome-card { flex-direction: column; text-align: center; }
            .header { flex-wrap: wrap; gap: 15px; }
            .notification-dropdown { width: 300px; right: -50px; }
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <h1><i class="fas fa-truck"></i> Delivery Dashboard</h1>
    <div class="header-right">
        <!-- Notification Bell -->
        <div class="notification-bell" onclick="toggleNotifications(event)">
            <i class="fas fa-bell"></i>
            <?php if ($unread_count > 0): ?>
                <span class="notification-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
            
            <div class="notification-dropdown" id="notificationDropdown">
                <div class="notification-header">
                    <h4><i class="fas fa-bell"></i> Notifications</h4>
                    <?php if ($unread_count > 0): ?>
                        <span><?php echo $unread_count; ?> new</span>
                    <?php endif; ?>
                </div>
                <div class="notification-list">
                    <?php if (empty($notifications)): ?>
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>No new notifications</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item">
                            <div class="notification-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="notification-content">
                                <p><?php echo htmlspecialchars($notif['message']); ?></p>
                                <span class="notification-time">
                                    <i class="fas fa-clock"></i> <?php echo date('M d, h:i A', strtotime($notif['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Profile Dropdown -->
        <div class="profile-dropdown">
            <button class="profile-btn" onclick="toggleDropdown(event)">
                <?php if (!empty($staff['picture'])): ?>
                    <img src="<?php echo htmlspecialchars($staff['picture']); ?>" alt="Profile">
                <?php else: ?>
                    <div class="default-avatar"><i class="fas fa-user"></i></div>
                <?php endif; ?>
                <span><?php echo htmlspecialchars($staff['full_name']); ?></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="dropdown-menu" id="dropdownMenu">
                <a href="delivery_profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
                <a href="delivery_logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</header>

<!-- Main Content -->
<main class="main-content">
    <!-- Welcome Card -->
    <div class="welcome-card">
        <?php if (!empty($staff['picture'])): ?>
            <img src="<?php echo htmlspecialchars($staff['picture']); ?>" alt="Profile">
        <?php else: ?>
            <div class="default-avatar"><i class="fas fa-user"></i></div>
        <?php endif; ?>
        <div>
            <h2>Welcome back, <?php echo htmlspecialchars($staff['full_name']); ?>!</h2>
            <p>
                <i class="fas fa-<?php echo $staff['vehicle_type'] === 'motorcycle' ? 'motorcycle' : 'car'; ?>"></i> 
                <?php echo ucfirst($staff['vehicle_type']); ?> Driver | 
                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($staff['phone']); ?>
            </p>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card pickup">
            <i class="fas fa-box"></i>
            <div class="number"><?php echo $to_pickup; ?></div>
            <div class="label">Available to Pick Up</div>
        </div>
        <div class="stat-card transit">
            <i class="fas fa-shipping-fast"></i>
            <div class="number"><?php echo $in_transit; ?></div>
            <div class="label">In Transit</div>
        </div>
        <div class="stat-card delivered">
            <i class="fas fa-check-circle"></i>
            <div class="number"><?php echo $delivered_today; ?></div>
            <div class="label">Delivered Today</div>
        </div>
    </div>

    <!-- My Active Orders -->
    <?php if (!empty($my_orders)): ?>
    <h3 class="section-title"><i class="fas fa-truck"></i> My Active Deliveries</h3>
    <div class="orders-container">
        <?php foreach ($my_orders as $order): ?>
        <div class="order-card">
            <div class="order-header">
                <span class="order-id"><i class="fas fa-receipt"></i> Order #<?php echo $order['id']; ?></span>
                <span class="order-amount">GHC <?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
            <div class="order-details">
                <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($order['full_name']); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['phone']); ?></p>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
            </div>
            <div class="order-actions">
                <form method="POST">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <button type="submit" name="complete_order" class="btn btn-complete">
                        <i class="fas fa-check"></i> Mark as Delivered
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Available Orders -->
    <h3 class="section-title"><i class="fas fa-box"></i> Available Orders</h3>
    <div class="orders-container">
        <?php if (empty($available_orders)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>No available orders right now!</p>
            </div>
        <?php else: ?>
            <?php foreach ($available_orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <span class="order-id"><i class="fas fa-receipt"></i> Order #<?php echo $order['id']; ?></span>
                    <span class="order-amount">GHC <?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
                <div class="order-details">
                    <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($order['full_name'] ?? 'N/A'); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($order['shipping_address'] ?? 'N/A'); ?></p>
                </div>
                <div class="order-actions">
                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <button type="submit" name="accept_order" class="btn btn-accept">
                            <i class="fas fa-truck"></i> Accept & Pick Up
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script>
function toggleNotifications(event) {
    event.stopPropagation();
    document.getElementById('notificationDropdown').classList.toggle('show');
    document.getElementById('dropdownMenu').classList.remove('show');
}

function toggleDropdown(event) {
    event.stopPropagation();
    document.getElementById('dropdownMenu').classList.toggle('show');
    document.getElementById('notificationDropdown').classList.remove('show');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.notification-bell')) {
        document.getElementById('notificationDropdown').classList.remove('show');
    }
    if (!e.target.closest('.profile-dropdown')) {
        document.getElementById('dropdownMenu').classList.remove('show');
    }
});
</script>

</body>
</html>