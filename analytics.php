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

// Get monthly sales data
$monthly_sales = $pdo->query("
    SELECT DATE_FORMAT(order_date, '%Y-%m') as month, 
           SUM(total_amount) as total,
           COUNT(*) as orders
    FROM orders 
    WHERE status = 'delivered'
    GROUP BY DATE_FORMAT(order_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
")->fetchAll(PDO::FETCH_ASSOC);

// Top selling products
$top_products = $pdo->query("
    SELECT p.name, SUM(oi.quantity) as sold, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'delivered'
    GROUP BY p.id
    ORDER BY sold DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Sales by category
$category_sales = $pdo->query("
    SELECT p.category, SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'delivered'
    GROUP BY p.category
    ORDER BY revenue DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Overall stats
$stats = [
    'total_revenue' => $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status='delivered'")->fetchColumn(),
    'total_orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'avg_order' => $pdo->query("SELECT COALESCE(AVG(total_amount), 0) FROM orders WHERE status='delivered'")->fetchColumn(),
    'total_customers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
];
$unread_messages = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Rahama's Scents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card {
            background: white; padding: 1.5rem; border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex; align-items: center; gap: 1rem;
        }
        .stat-card .icon { font-size: 2rem; color: #d97528; width: 60px; height: 60px; background: rgba(217, 117, 40, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .stat-card .value { font-size: 1.5rem; font-weight: bold; color: #1a1a2e; }
        .stat-card .label { color: #666; font-size: 0.9rem; }

        .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .chart-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .chart-card h3 { margin-bottom: 1rem; color: #1a1a2e; }

        .table-container { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .table-container h3 { margin-bottom: 1rem; color: #1a1a2e; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
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
            <h1><i class="fas fa-chart-line"></i> Sales Analytics</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                <div>
                    <div class="value">GHs<?= number_format($stats['total_revenue'], 2) ?></div>
                    <div class="label">Total Revenue</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                <div>
                    <div class="value"><?= $stats['total_orders'] ?></div>
                    <div class="label">Total Orders</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-receipt"></i></div>
                <div>
                    <div class="value">GHs<?= number_format($stats['avg_order'], 2) ?></div>
                    <div class="label">Avg Order Value</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-users"></i></div>
                <div>
                    <div class="value"><?= $stats['total_customers'] ?></div>
                    <div class="label">Total Customers</div>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3>Monthly Sales</h3>
                <canvas id="salesChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>Sales by Category</h3>
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <div class="table-container">
            <h3><i class="fas fa-trophy"></i> Top Selling Products</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_products)): ?>
                    <tr><td colspan="3" style="text-align: center; color: #666;">No sales data yet</td></tr>
                    <?php else: ?>
                    <?php foreach ($top_products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= $product['sold'] ?></td>
                        <td>$<?= number_format($product['revenue'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
// Monthly Sales Chart
const salesData = <?= json_encode(array_reverse($monthly_sales)) ?>;
new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: salesData.map(d => d.month),
        datasets: [{
            label: 'Revenue ($)',
            data: salesData.map(d => d.total),
            borderColor: '#d97528',
            backgroundColor: 'rgba(217, 117, 40, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

// Category Chart
const categoryData = <?= json_encode($category_sales) ?>;
new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: categoryData.map(d => d.category.charAt(0).toUpperCase() + d.category.slice(1)),
        datasets: [{
            data: categoryData.map(d => d.revenue),
            backgroundColor: ['#d97528', '#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ef4444']
        }]
    },
    options: { responsive: true }
});
</script>

</body>
</html>