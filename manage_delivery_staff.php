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

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = $_POST['staff_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE delivery_staff SET status = 'approved' WHERE id = ?");
        $stmt->execute([$staff_id]);
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE delivery_staff SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$staff_id]);
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM delivery_staff WHERE id = ?");
        $stmt->execute([$staff_id]);
    }
    header("Location: manage_delivery_staff.php");
    exit();
}

// Fetch all delivery staff
$pending = $pdo->query("SELECT * FROM delivery_staff WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$approved = $pdo->query("SELECT * FROM delivery_staff WHERE status = 'approved' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$rejected = $pdo->query("SELECT * FROM delivery_staff WHERE status = 'rejected' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'pending' => count($pending),
    'approved' => count($approved),
    'rejected' => count($rejected),
];
$unread_messages = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Delivery Staff - Rahama's Scents</title>
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
        .stat-card .value { font-size: 2rem; font-weight: bold; }
        .stat-card .label { color: #666; margin-top: 5px; }
        .stat-card.pending .value { color: #f59e0b; }
        .stat-card.approved .value { color: #10b981; }
        .stat-card.rejected .value { color: #ef4444; }

        .section-title {
            background: white;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0;
            font-weight: 600;
            color: #1a1a2e;
            border-bottom: 2px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-title.pending { border-left: 4px solid #f59e0b; }
        .section-title.approved { border-left: 4px solid #10b981; }
        .section-title.rejected { border-left: 4px solid #ef4444; }

        .table-container { background: white; border-radius: 0 0 12px 12px; padding: 0; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow-x: auto; margin-bottom: 2rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        tr:hover { background: #fafafa; }

        .btn { padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s; }
        .btn-approve { background: #10b981; color: white; }
        .btn-approve:hover { background: #059669; }
        .btn-reject { background: #ef4444; color: white; }
        .btn-reject:hover { background: #dc2626; }
        .btn-delete { background: #666; color: white; }
        .btn-delete:hover { background: #555; }

        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.approved { background: #d1fae5; color: #065f46; }
        .status-badge.rejected { background: #fee2e2; color: #991b1b; }

        .empty-message { padding: 30px; text-align: center; color: #666; }
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
            <h1><i class="fas fa-user-check"></i> Manage Delivery Staff</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card pending">
                <div class="value"><?= $stats['pending'] ?></div>
                <div class="label">Pending Approval</div>
            </div>
            <div class="stat-card approved">
                <div class="value"><?= $stats['approved'] ?></div>
                <div class="label">Approved</div>
            </div>
            <div class="stat-card rejected">
                <div class="value"><?= $stats['rejected'] ?></div>
                <div class="label">Rejected</div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="section-title pending">
            <i class="fas fa-clock"></i> Pending Approvals (<?= $stats['pending'] ?>)
        </div>
        <div class="table-container">
            <?php if (empty($pending)): ?>
                <div class="empty-message">No pending applications</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Applied</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $staff): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($staff['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($staff['email']) ?></td>
                        <td><?= htmlspecialchars($staff['phone']) ?></td>
                        <td><?= date('M d, Y H:i', strtotime($staff['created_at'])) ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-approve">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                <button type="submit" name="action" value="reject" class="btn btn-reject">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Approved Staff -->
        <div class="section-title approved">
            <i class="fas fa-check-circle"></i> Approved Staff (<?= $stats['approved'] ?>)
        </div>
        <div class="table-container">
            <?php if (empty($approved)): ?>
                <div class="empty-message">No approved staff yet</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approved as $staff): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($staff['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($staff['email']) ?></td>
                        <td><?= htmlspecialchars($staff['phone']) ?></td>
                        <td><span class="status-badge approved">Approved</span></td>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this staff member?')">
                                <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                <button type="submit" name="action" value="reject" class="btn btn-reject">
                                    <i class="fas fa-ban"></i> Revoke
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Rejected Staff -->
        <div class="section-title rejected">
            <i class="fas fa-times-circle"></i> Rejected (<?= $stats['rejected'] ?>)
        </div>
        <div class="table-container">
            <?php if (empty($rejected)): ?>
                <div class="empty-message">No rejected applications</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rejected as $staff): ?>
                    <tr>
                        <td><?= htmlspecialchars($staff['name']) ?></td>
                        <td><?= htmlspecialchars($staff['email']) ?></td>
                        <td><?= htmlspecialchars($staff['phone']) ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-approve">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this application?')">
                                <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                <button type="submit" name="action" value="delete" class="btn btn-delete">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>