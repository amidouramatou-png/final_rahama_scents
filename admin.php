<?php
session_start();

// Redirect if not admin
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

// Handle Export Report
if (isset($_GET['export']) && $_GET['export'] === 'report') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order ID', 'Customer', 'Email', 'Phone', 'Amount (GHC)', 'Status', 'Payment Method', 'Date']);

    $orders = $pdo->query("
        SELECT o.id, u.full_name, u.email, u.phone, o.total_amount, o.status, o.payment_method, o.order_date
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.order_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as $order) {
        fputcsv($output, [
            '#' . $order['id'],
            $order['full_name'] ?? 'N/A',
            $order['email'] ?? 'N/A',
            $order['phone'] ?? 'N/A',
            number_format($order['total_amount'], 2),
            ucfirst($order['status']),
            $order['payment_method'] ?? 'Cash on Delivery',
            date('M d, Y H:i', strtotime($order['order_date']))
        ]);
    }
    fclose($output);
    exit();
}

// Create uploads folder
if (!is_dir('uploads')) mkdir('uploads', 0777, true);

// Handle POST actions (Add/Edit/Delete Product)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_product' || $action === 'edit_product') {
        $id = $_POST['id'] ?? null;
        $name = trim($_POST['name']);
        $category = $_POST['category'];
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $description = trim($_POST['description'] ?? '');

        $imagePath = $_POST['existing_image'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = mime_content_type($_FILES['image']['tmp_name']);

            if (in_array($file_type, $allowed_types)) {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $imageName = uniqid('prod_') . '.' . strtolower($ext);
                $imagePath = 'uploads/' . $imageName;
                move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
            }
        }

        if ($action === 'add_product') {
            $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock, description, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category, $price, $stock, $description, $imagePath]);
        } else {
            $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, stock=?, description=?, image=? WHERE id=?");
            $stmt->execute([$name, $category, $price, $stock, $description, $imagePath, $id]);
        }
        header("Location: admin.php#products");
        exit();
    }

    if ($action === 'delete_product') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $oldImage = $stmt->fetchColumn();
        if ($oldImage && file_exists($oldImage)) unlink($oldImage);

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin.php#products");
        exit();
    }
}

// Fetch Data
$stats = [
    'revenue' => $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status='delivered'")->fetchColumn(),
    'orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'customers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
    'low_stock' => $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 10")->fetchColumn(),
];

// Fetch unread messages count
$unread_messages = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
$total_messages = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();

// Fetch recent messages for dashboard
$recent_messages = $pdo->query("
    SELECT * FROM messages 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$recent_orders = $pdo->query("
    SELECT o.id, u.full_name as customer, o.order_date, o.total_amount, o.status
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.order_date DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get admin name
$admin_name = $_SESSION['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Rahama's Scents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        /* Messages Badge Styles */
        .badge {
            background: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            margin-left: 8px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .stat-card.messages {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }
        .stat-card.messages .icon {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .stat-card.messages .label,
        .stat-card.messages .value {
            color: white;
        }
        .messages-preview {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-top: 20px;
        }
        .messages-preview h3 {
            margin-bottom: 15px;
            color: #1a1a2e;
        }
        .message-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }
        .message-item:hover {
            background: #f9f9f9;
        }
        .message-item:last-child {
            border-bottom: none;
        }
        .message-item.unread {
            background: #fef3c7;
        }
        .message-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #d97528;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            flex-shrink: 0;
        }
        .message-content {
            flex: 1;
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .message-sender {
            font-weight: bold;
            color: #333;
        }
        .message-date {
            font-size: 0.8rem;
            color: #888;
        }
        .message-subject {
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        .message-preview-text {
            color: #888;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 400px;
        }
        .view-all-link {
            display: block;
            text-align: center;
            padding: 15px;
            color: #d97528;
            font-weight: bold;
            text-decoration: none;
            border-top: 1px solid #eee;
            margin-top: 10px;
        }
        .view-all-link:hover {
            background: #fff8f0;
        }
        .empty-messages {
            text-align: center;
            padding: 30px;
            color: #888;
        }
        .empty-messages i {
            font-size: 2rem;
            color: #ddd;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Rahama's Scents</h2>
            <p>Welcome, <?php echo htmlspecialchars($admin_name); ?></p>
        </div>
        <nav class="sidebar-menu">
            <a href="#overview" class="active"><i class="fas fa-tachometer-alt"></i> Overview</a>
            <a href="#products"><i class="fas fa-wine-bottle"></i> Products</a>
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

    <!-- Main Content -->
    <main class="main-content">

        <!-- Overview Section -->
        <section id="overview" class="dashboard-section active">
            <div class="header">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h1>
                <a href="admin.php?export=report" class="btn-outline">
                    <i class="fas fa-download"></i> Export Report
                </a>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                    <div>
                        <div class="label">Total Revenue</div>
                        <div class="value">GHC <?php echo number_format($stats['revenue'], 2); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                    <div>
                        <div class="label">Total Orders</div>
                        <div class="value"><?php echo $stats['orders']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="label">Customers</div>
                        <div class="value"><?php echo $stats['customers']; ?></div>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div>
                        <div class="label">Low Stock Items</div>
                        <div class="value"><?php echo $stats['low_stock']; ?></div>
                    </div>
                </div>
                <!-- Messages Stat Card -->
                <div class="stat-card messages">
                    <div class="icon"><i class="fas fa-envelope"></i></div>
                    <div>
                        <div class="label">Unread Messages</div>
                        <div class="value"><?php echo $unread_messages; ?></div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <h3 style="margin-bottom: 1rem; color: #1a1a2e;"><i class="fas fa-bolt"></i> Quick Actions</h3>
            <div class="quick-actions">
                <a href="#products" class="quick-action" onclick="showSection('products')">
                    <i class="fas fa-plus-circle"></i>
                    <h4>Add Product</h4>
                    <p>Add new perfume</p>
                </a>
                <a href="orders.php" class="quick-action">
                    <i class="fas fa-clipboard-list"></i>
                    <h4>View Orders</h4>
                    <p>Manage all orders</p>
                </a>
                <a href="admin_messages.php" class="quick-action" style="<?php echo $unread_messages > 0 ? 'border: 2px solid #d97528;' : ''; ?>">
                    <i class="fas fa-envelope" style="<?php echo $unread_messages > 0 ? 'color: #d97528;' : ''; ?>"></i>
                    <h4>Messages <?php if ($unread_messages > 0): ?><span class="badge"><?php echo $unread_messages; ?></span><?php endif; ?></h4>
                    <p>Customer inquiries</p>
                </a>
                <a href="inventory.php" class="quick-action">
                    <i class="fas fa-boxes"></i>
                    <h4>Inventory</h4>
                    <p>Check stock levels</p>
                </a>
            </div>

            <!-- Recent Messages Preview -->
            <div class="messages-preview">
                <h3><i class="fas fa-envelope"></i> Recent Messages</h3>
                <?php if (empty($recent_messages)): ?>
                    <div class="empty-messages">
                        <i class="fas fa-inbox"></i>
                        <p>No messages yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_messages as $msg): ?>
                    <div class="message-item <?php echo $msg['is_read'] ? '' : 'unread'; ?>">
                        <div class="message-avatar">
                            <?php echo strtoupper(substr($msg['name'], 0, 1)); ?>
                        </div>
                        <div class="message-content">
                            <div class="message-header">
                                <span class="message-sender">
                                    <?php echo htmlspecialchars($msg['name']); ?>
                                    <?php if (!$msg['is_read']): ?>
                                        <span style="background: #d97528; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-left: 5px;">NEW</span>
                                    <?php endif; ?>
                                </span>
                                <span class="message-date"><?php echo date('M d, g:i A', strtotime($msg['created_at'])); ?></span>
                            </div>
                            <div class="message-subject"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($msg['subject'] ?: 'No Subject'); ?></div>
                            <div class="message-preview-text"><?php echo htmlspecialchars(substr($msg['message'], 0, 100)) . '...'; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <a href="admin_messages.php" class="view-all-link">
                        <i class="fas fa-arrow-right"></i> View All Messages (<?php echo $total_messages; ?>)
                    </a>
                <?php endif; ?>
            </div>

            <!-- Recent Orders -->
            <div class="table-container" style="margin-top: 20px;">
                <h3><i class="fas fa-clock"></i> Recent Orders</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666; padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 2rem; color: #ddd; display: block; margin-bottom: 10px;"></i>
                                No orders yet
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recent_orders as $o): ?>
                        <tr>
                            <td><strong>#<?php echo $o['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($o['customer'] ?? 'N/A'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($o['order_date'])); ?></td>
                            <td><strong>GHC <?php echo number_format($o['total_amount'], 2); ?></strong></td>
                            <td><span class="status <?php echo $o['status']; ?>"><?php echo ucfirst($o['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Products Section -->
        <section id="products" class="dashboard-section">
            <div class="header">
                <h1><i class="fas fa-wine-bottle"></i> Manage Products</h1>
                <button onclick="openModal()" class="btn-primary">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #666; padding: 40px;">
                                <i class="fas fa-box-open" style="font-size: 2rem; color: #ddd; display: block; margin-bottom: 10px;"></i>
                                No products yet. Click "Add Product" to get started.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <img src="<?php echo $p['image'] ? htmlspecialchars($p['image']) : 'https://via.placeholder.com/60'; ?>"
                                     class="product-img-preview"
                                     alt="<?php echo htmlspecialchars($p['name']); ?>"
                                     onerror="this.src='https://via.placeholder.com/60'">
                            </td>
                            <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                            <td><?php echo ucfirst($p['category']); ?></td>
                            <td><strong>GHC <?php echo number_format($p['price'], 2); ?></strong></td>
                            <td>
                                <span style="color: <?php echo $p['stock'] < 10 ? '#ef4444' : '#10b981'; ?>; font-weight: bold;">
                                    <?php echo $p['stock']; ?>
                                    <?php if ($p['stock'] < 10): ?>
                                        <i class="fas fa-exclamation-circle"></i>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <button onclick='openModal(<?php echo json_encode($p); ?>)' class="btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this product?')">
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn-delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>
</div>

<!-- Product Modal -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle"><i class="fas fa-plus-circle"></i> Add New Product</h2>
        <form method="POST" enctype="multipart/form-data" id="productForm">
            <input type="hidden" name="action" id="formAction" value="add_product">
            <input type="hidden" name="id" id="editId">
            <input type="hidden" name="existing_image" id="existingImage">

            <div class="form-group">
                <label><i class="fas fa-tag"></i> Product Name</label>
                <input type="text" name="name" id="name" required placeholder="Enter product name">
            </div>
            <div class="form-group">
                <label><i class="fas fa-list"></i> Category</label>
                <select name="category" id="category" required>
                    <option value="">Select a category</option>
                    <option value="floral">Floral</option>
                    <option value="fresh">Fresh</option>
                    <option value="woody">Woody</option>
                    <option value="citrus">Citrus</option>
                    <option value="oriental">Oriental</option>
                    <option value="gourmand">Gourmand</option>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-money-bill"></i> Price (GHC)</label>
                <input type="number" step="0.01" name="price" id="price" required placeholder="0.00" min="0">
            </div>
            <div class="form-group">
                <label><i class="fas fa-cubes"></i> Stock Quantity</label>
                <input type="number" name="stock" id="stock" required placeholder="0" min="0">
            </div>
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Description</label>
                <textarea name="description" id="description" rows="3" placeholder="Enter product description"></textarea>
            </div>
            <div class="form-group">
                <label><i class="fas fa-image"></i> Product Image</label>
                <input type="file" name="image" id="image" accept="image/*">
                <div id="imagePreview" style="margin-top:10px;"></div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Product</button>
                <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="admin.js"></script>

</body>
</html>