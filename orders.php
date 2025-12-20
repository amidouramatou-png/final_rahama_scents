<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    header("Location: orders.php");
    exit();
}

// Fetch all orders
$orders = $pdo->query("
    SELECT o.*, u.full_name ,u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.order_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Stats
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn(),
    'processing' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='processing'")->fetchColumn(),
    'delivered' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='delivered'")->fetchColumn(),
];
$unread_messages = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Rahama's Scents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        .dashboard { display: flex; min-height: 100vh; }
        
        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
        }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .sidebar-header h2 { color: #d97528; margin-bottom: 5px; }
        .sidebar-header p { font-size: 0.9rem; opacity: 0.8; }
        .sidebar-menu { display: flex; flex-direction: column; }
        .sidebar-menu a {
            color: white; text-decoration: none; padding: 15px 25px;
            display: flex; align-items: center; gap: 12px;
            transition: all 0.3s; border-left: 3px solid transparent;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(217, 117, 40, 0.2); border-left-color: #d97528; }
        .sidebar-menu a.logout { margin-top: auto; color: #ff6b6b; }

        .main-content { flex: 1; margin-left: 260px; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .header h1 { color: #1a1a2e; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card {
            background: white; padding: 1.5rem; border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center;
        }
        .stat-card .value { font-size: 2rem; font-weight: bold; color: #d97528; }
        .stat-card .label { color: #666; margin-top: 5px; }
        .stat-card.pending .value { color: #f59e0b; }
        .stat-card.processing .value { color: #3b82f6; }
        .stat-card.delivered .value { color: #10b981; }

        .table-container { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        tr:hover { background: #fafafa; }

        .status { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .status.pending { background: #fee2e2; color: #991b1b; }
        .status.processing { background: #fef3c7; color: #92400e; }
        .status.shipped { background: #dbeafe; color: #1e40af; }
        .status.delivered { background: #d1fae5; color: #065f46; }

        select.status-select { padding: 6px 10px; border-radius: 6px; border: 1px solid #ddd; cursor: pointer; }
        .btn-update { background: #d97528; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; }
        .btn-update:hover { background: #c46820; }
    </style>
</head>
<body>

<div class="dashboard">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Rahama's Scents</h2>
            <p>Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></p>
        </div>
        <nav class="sidebar-menu">
            <a href="admin.php"><i class="fas fa-tachometer-alt"></i> Overview</a>
            <a href="admin.php#products"><i class="fas fa-wine-bottle"></i> Products</a>
            <a href="orders.php"><i class="fas fa-shopping-bag"></i> Orders</a>
            <a href="admin_messages.php">
                <i class="fas fa-envelope"></i> Messages
                <?php if ($unread_messages > 0): ?>
                    <span class="badge"><?php echo $unread_messages; ?></span>
                <?php endif; ?>
            </a>
            <a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
            <a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a>
            <a href="customersA.php"><i class="fas fa-users"></i> Customers</a>
            <a href="manage_delivery_staff.php"><i class="fas fa-user-check"></i> Delivery Staff</a>
            <a href="delivery_traking.php"><i class="fas fa-shipping-fast"></i> Delivery Tracking</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1><i class="fas fa-shopping-bag"></i> Orders Management</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?= $stats['total'] ?></div>
                <div class="label">Total Orders</div>
            </div>
            <div class="stat-card pending">
                <div class="value"><?= $stats['pending'] ?></div>
                <div class="label">Pending</div>
            </div>
            <div class="stat-card processing">
                <div class="value"><?= $stats['processing'] ?></div>
                <div class="label">Processing</div>
            </div>
            <div class="stat-card delivered">
                <div class="value"><?= $stats['delivered'] ?></div>
                <div class="label">Delivered</div>
            </div>
        </div>

        <div class="table-container">
            <h3 style="margin-bottom: 1rem;">All Orders</h3>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr><td colspan="7" style="text-align: center; color: #666;">No orders yet</td></tr>
                    <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['full_name']) ?></td>
                        <td><?= htmlspecialchars($order['email']) ?></td>
                        <td><?= date('M d, Y H:i', strtotime($order['order_date'])) ?></td>
                        <td>GHC<?= number_format($order['total_amount'], 2) ?></td>
                        <td><span class="status <?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td>
                        <td>
                            <form method="POST" style="display: flex; gap: 5px;">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="status" class="status-select">
                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                </select>
                                <button type="submit" name="update_status" class="btn-update">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>