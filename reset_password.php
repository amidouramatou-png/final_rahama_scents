<?php
require_once 'config.php';

$message = '';
$error = '';
$valid_token = false;

// Check if token exists and is valid
if (isset($_GET['token'])) {
    $token = $conn->real_escape_string($_GET['token']);
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $valid_token = true;
    } else {
        $error = "Invalid or expired reset link!";
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = $conn->real_escape_string($_POST['token']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if ($password !== $confirm) {
        $error = "Passwords do not match!";
        $valid_token = true;
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password and clear token
        $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
        $update->bind_param("ss", $hashed, $token);
        
        if ($update->execute()) {
            $message = "Password reset successful! <a href='login.php'>Login now</a>";
        } else {
            $error = "Error resetting password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Rahama's Scents</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #d97528;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h2 {
            text-align: center;
            color: #d97528;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #d97528;
        }
        button {
            width: 100%;
            padding: 14px;
            background: #d97528;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background: #b8651f;
        }
        .alert {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
        }
        .alert-danger { background: #ffe0e0; color: #c00; }
        .alert-success { background: #e0ffe0; color: #060; }
        .alert-success a { color: #060; font-weight: bold; }
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            color: #d97528;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>Reset Password</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($valid_token): ?>
    <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">
        
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="password" required placeholder="Enter new password">
        </div>
        
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required placeholder="Confirm new password">
        </div>
        
        <button type="submit">Reset Password</button>
    </form>
    <?php endif; ?>
    
    <div class="links">
        <p><a href="login.php">← Back to Login</a></p>
    </div>
</div>

</body>
</html>