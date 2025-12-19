<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$host = 'localhost';
$dbname = 'rahama_scents';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $product_id = $_POST['product_id'];
    $new_stock = intval($_POST['stock']);
    $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
    $stmt->execute([$new_stock, $product_id]);
    header("Location: inventory.php");
    exit();
}

// Fetch all products with stock info
$products = $pdo->query("SELECT * FROM products ORDER BY stock ASC")->fetchAll(PDO::FETCH_ASSOC);

// Stats
$stats = [
    'total_products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'low_stock' => $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 10")->fetchColumn(),
    'out_of_stock' => $pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn(),
    'total_units' => $pdo->query("SELECT COALESCE(SUM(stock), 0) FROM products")->fetchColumn(),
];
$unread_messages = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Rahama's Scents</title>
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
        .stat-card.warning .value { color: #f59e0b; }
        .stat-card.danger .value { color: #ef4444; }

        .table-container { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        tr:hover { background: #fafafa; }

        .stock-level { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .stock-level.good { background: #d1fae5; color: #065f46; }
        .stock-level.low { background: #fef3c7; color: #92400e; }
        .stock-level.out { background: #fee2e2; color: #991b1b; }

        .stock-input { width: 80px; padding: 6px; border: 1px solid #ddd; border-radius: 6px; text-align: center; }
        .btn-update { background: #d97528; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; }
        .btn-update:hover { background: #c46820; }
        .product-img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; }
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
            <h1><i class="fas fa-boxes"></i> Inventory Management</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?= $stats['total_products'] ?></div>
                <div class="label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $stats['total_units'] ?></div>
                <div class="label">Total Units</div>
            </div>
            <div class="stat-card warning">
                <div class="value"><?= $stats['low_stock'] ?></div>
                <div class="label">Low Stock (&lt;10)</div>
            </div>
            <div class="stat-card danger">
                <div class="value"><?= $stats['out_of_stock'] ?></div>
                <div class="label">Out of Stock</div>
            </div>
        </div>

        <div class="table-container">
            <h3 style="margin-bottom: 1rem;">Stock Levels</h3>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Status</th>
                        <th>Update Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                    <tr><td colspan="6" style="text-align: center; color: #666;">No products found</td></tr>
                    <?php else: ?>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><img src="<?= $p['image'] ?: 'placeholder.jpg' ?>" class="product-img" alt=""></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= ucfirst($p['category']) ?></td>
                        <td><strong><?= $p['stock'] ?></strong></td>
                        <td>
                            <?php if ($p['stock'] == 0): ?>
                                <span class="stock-level out">Out of Stock</span>
                            <?php elseif ($p['stock'] < 10): ?>
                                <span class="stock-level low">Low Stock</span>
                            <?php else: ?>
                                <span class="stock-level good">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display: flex; gap: 5px; align-items: center;">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="number" name="stock" value="<?= $p['stock'] ?>" class="stock-input" min="0">
                                <button type="submit" name="update_stock" class="btn-update">Update</button>
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