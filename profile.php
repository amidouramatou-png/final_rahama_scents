<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

if (!isLoggedIn()) { header('Location: login.php'); exit(); }

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    
    // Check if updating password
    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            $error = "Passwords do not match!";
        } else {
            $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, address=?, password=? WHERE id=?");
            $stmt->bind_param("ssssi", $full_name, $phone, $address, $password, $user_id);
            $stmt->execute();
            $success = "Profile updated with new password!";
        }
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, address=? WHERE id=?");
        $stmt->bind_param("sssi", $full_name, $phone, $address, $user_id);
        $stmt->execute();
        $success = "Profile updated successfully!";
    }
}

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile - Rahama's Scents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        h1 { color: #d97528; margin-bottom: 20px; }
        a { color: #d97528; text-decoration: none; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        input, textarea { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; }
        input:focus, textarea:focus { outline: none; border-color: #d97528; }
        .btn { width: 100%; padding: 14px; background: #d97528; color: white; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; }
        .btn:hover { background: #b8651f; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        .section-title { margin: 25px 0 15px; padding-top: 20px; border-top: 1px solid #eee; color: #333; }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-user-cog"></i> My Profile</h1>
    <a href="customer_dashboard.php">← Back to Dashboard</a>
    
    <div class="card" style="margin-top: 20px;">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background: #f5f5f5;">
            </div>
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" placeholder="Enter your full name">
            </div>
            
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Enter your phone number">
            </div>
            
            <div class="form-group">
                <label>Default Delivery Address</label>
                <textarea name="address" rows="3" placeholder="Enter your delivery address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>
            
            <h3 class="section-title"><i class="fas fa-lock"></i> Change Password</h3>
            
            <div class="form-group">
                <label>New Password (leave blank to keep current)</label>
                <input type="password" name="new_password" placeholder="Enter new password">
            </div>
            
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm new password">
            </div>
            
            <button type="submit" class="btn"><i class="fas fa-save"></i> Save Changes</button>
        </form>
    </div>
</div>
</body>
</html>