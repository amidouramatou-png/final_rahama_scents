<?php
require_once 'config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        // Create reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Save token to database
        $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $update->bind_param("sss", $token, $expires, $email);
        $update->execute();
        
        // Create reset link
        $reset_link = "http://localhost/webtech/final/reset_password.php?token=" . $token;
        
        // For testing - show the link (In production, send via email)
        $message = "Reset link created! <br><a href='$reset_link'>Click here to reset password</a>";
        
    } else {
        $error = "Email not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Rahama's Scents</title>
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
            margin-bottom: 10px;
        }
        p.subtitle {
            text-align: center;
            color: #666;
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
        .alert-success a { color: #060; }
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
    <h2>Forgot Password?</h2>
    <p class="subtitle">Enter your email to reset your password</p>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="Enter your email">
        </div>
        
        <button type="submit">Send Reset Link</button>
    </form>
    
    <div class="links">
        <p><a href="login.php">← Back to Login</a></p>
    </div>
</div>

</body>
</html>