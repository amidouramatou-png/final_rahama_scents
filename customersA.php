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

// Fetch customers with order stats
$customers = $pdo->query("
    SELECT u.*, 
           COUNT(o.id) as total_orders,
           COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY total_spent DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Stats
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
    'new_this_month' => $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer' AND MONTH(created_at) = MONTH(NOW())")->fetchColumn(),
    'with_orders' => $pdo->query("SELECT COUNT(DISTINCT user_id) FROM orders")->fetchColumn(),
];
$unread_messages = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Rahama's Scents</title>
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
        .header { margin-bottom: 2rem; }
        .header h1 { color: #1a1a2e; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card {
            background: white; padding: 1.5rem; border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center;
        }
        .stat-card .value { font-size: 2rem; font-weight: bold; color: #d97528; }
        .stat-card .label { color: #666; margin-top: 5px; }

        .table-container { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        tr:hover { background: #fafafa; }

        .customer-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: #d97528; color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 1.2rem;
        }
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
            <h1><i class="fas fa-users"></i> Customer Management</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?= $stats['total'] ?></div>
                <div class="label">Total Customers</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $stats['new_this_month'] ?></div>
                <div class="label">New This Month</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $stats['with_orders'] ?></div>
                <div class="label">With Orders</div>
            </div>
        </div>

        <div class="table-container">
            <h3 style="margin-bottom: 1rem;">All Customers</h3>
            <table>
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Joined</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                    <tr><td colspan="6" style="text-align: center; color: #666;">No customers yet</td></tr>
                    <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td>
                            <div class="customer-avatar">
                                <?= strtoupper(substr($c['username'], 0, 1)) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($c['full_name']) ?></td>
                        <td><?= isset($c['created_at']) ? date('M d, Y', strtotime($c['created_at'])) : 'N/A' ?></td>
                        <td><?= $c['total_orders'] ?></td>
                        <td>GHC<?= number_format($c['total_spent'], 2) ?></td>
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