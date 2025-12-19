<?php
require_once 'config.php';
session_start();

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Mark message as read
if (isset($_GET['mark_read'])) {
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $_GET['mark_read']);
    $stmt->execute();
    header('Location: admin_messages.php');
    exit();
}

// Delete message
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    header('Location: admin_messages.php');
    exit();
}

// Fetch all messages
$filter = $_GET['filter'] ?? 'all';
if ($filter === 'unread') {
    $messages = $conn->query("SELECT * FROM messages WHERE is_read = 0 ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
} elseif ($filter === 'read') {
    $messages = $conn->query("SELECT * FROM messages WHERE is_read = 1 ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
} else {
    $messages = $conn->query("SELECT * FROM messages ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

// Count unread
$unread_count = $conn->query("SELECT COUNT(*) as count FROM messages WHERE is_read = 0")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Messages - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: auto; }
        h1 { color: #d97528; margin-bottom: 5px; }
        .subtitle { color: #888; margin-bottom: 20px; }
        a { color: #d97528; text-decoration: none; font-weight: bold; }
        
        /* Stats */
        .stats { display: flex; gap: 15px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); flex: 1; text-align: center; }
        .stat-card i { font-size: 2rem; color: #d97528; }
        .stat-card h3 { font-size: 2rem; color: #333; margin: 10px 0 5px; }
        .stat-card p { color: #888; }
        
        /* Filters */
        .filters { display: flex; gap: 10px; margin: 20px 0; }
        .filter-btn { padding: 8px 20px; border: 2px solid #d97528; background: white; color: #d97528; border-radius: 20px; cursor: pointer; font-weight: bold; text-decoration: none; }
        .filter-btn:hover, .filter-btn.active { background: #d97528; color: white; }
        
        /* Message Cards */
        .message-card { background: white; border-radius: 10px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .message-card.unread { border-left: 4px solid #d97528; }
        .message-header { padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .message-header:hover { background: #fafafa; }
        .sender-info { display: flex; align-items: center; gap: 15px; }
        .sender-avatar { width: 45px; height: 45px; border-radius: 50%; background: #d97528; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .sender-name { font-weight: bold; color: #333; }
        .sender-email { color: #888; font-size: 0.9rem; }
        .message-subject { color: #555; margin-top: 3px; }
        .message-date { color: #888; font-size: 0.85rem; }
        .unread-badge { background: #d97528; color: white; padding: 3px 10px; border-radius: 10px; font-size: 0.75rem; }
        
        /* Message Body */
        .message-body { display: none; padding: 0 20px 20px; border-top: 1px solid #eee; }
        .message-card.open .message-body { display: block; }
        .message-content { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 15px 0; line-height: 1.6; color: #444; }
        .message-actions { display: flex; gap: 10px; }
        .btn { padding: 8px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.9rem; }
        .btn-success { background: #d1fae5; color: #065f46; }
        .btn-danger { background: #fee2e2; color: #991b1b; }
        .btn-primary { background: #d97528; color: white; }
        .btn:hover { opacity: 0.8; }
        
        .toggle-icon { transition: transform 0.3s; }
        .message-card.open .toggle-icon { transform: rotate(180deg); }
        
        .empty { text-align: center; padding: 50px; background: white; border-radius: 10px; color: #888; }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-envelope"></i> Customer Messages</h1>
    <p class="subtitle">View and manage messages from your customers</p>
    <a href="admin.php">← Back to Dashboard</a>
    
    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <i class="fas fa-envelope"></i>
            <h3><?= count($messages) ?></h3>
            <p>Total Messages</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-envelope-open"></i>
            <h3><?= $unread_count ?></h3>
            <p>Unread Messages</p>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="filters">
        <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All</a>
        <a href="?filter=unread" class="filter-btn <?= $filter === 'unread' ? 'active' : '' ?>">Unread (<?= $unread_count ?>)</a>
        <a href="?filter=read" class="filter-btn <?= $filter === 'read' ? 'active' : '' ?>">Read</a>
    </div>
    
    <?php if (empty($messages)): ?>
        <div class="empty">
            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <p>No messages found.</p>
        </div>
    <?php else: ?>
        <?php foreach ($messages as $msg): ?>
        <div class="message-card <?= $msg['is_read'] ? '' : 'unread' ?>">
            <div class="message-header" onclick="toggleMessage(this)">
                <div class="sender-info">
                    <div class="sender-avatar"><?= strtoupper(substr($msg['name'], 0, 1)) ?></div>
                    <div>
                        <div class="sender-name">
                            <?= htmlspecialchars($msg['name']) ?>
                            <?php if (!$msg['is_read']): ?>
                                <span class="unread-badge">NEW</span>
                            <?php endif; ?>
                        </div>
                        <div class="sender-email"><?= htmlspecialchars($msg['email']) ?></div>
                        <div class="message-subject"><i class="fas fa-tag"></i> <?= htmlspecialchars($msg['subject']) ?></div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div class="message-date"><?= date('M d, Y', strtotime($msg['created_at'])) ?></div>
                    <div class="message-date"><?= date('h:i A', strtotime($msg['created_at'])) ?></div>
                    <i class="fas fa-chevron-down toggle-icon" style="margin-top: 10px; color: #888;"></i>
                </div>
            </div>
            <div class="message-body">
                <div class="message-content">
                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                </div>
                <div class="message-actions">
                    <a href="mailto:<?= htmlspecialchars($msg['email']) ?>?subject=Re: <?= htmlspecialchars($msg['subject']) ?>" class="btn btn-primary">
                        <i class="fas fa-reply"></i> Reply via Email
                    </a>
                    <?php if (!$msg['is_read']): ?>
                        <a href="?mark_read=<?= $msg['id'] ?>" class="btn btn-success">
                            <i class="fas fa-check"></i> Mark as Read
                        </a>
                    <?php endif; ?>
                    <a href="?delete=<?= $msg['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this message?')">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function toggleMessage(header) {
    const card = header.parentElement;
    card.classList.toggle('open');
}

// Auto-open first unread message
document.querySelector('.message-card.unread')?.classList.add('open');
</script>
</body>
</html>