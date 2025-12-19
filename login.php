<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectBasedOnRole();
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, full_name, email, phone, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['role'] = $user['role'];
            
            redirectBasedOnRole();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rahama's Scents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-container {
            width: 100%;
            max-width: 420px;
        }
        
        .auth-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo img {
            max-width: 150px;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(217, 117, 40, 0.3);
        }
        
        h2 {
            text-align: center;
            color: #d97528;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i.icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .input-wrapper input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #eee;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .input-wrapper input:focus {
            outline: none;
            border-color: #d97528;
            box-shadow: 0 0 0 3px rgba(217, 117, 40, 0.1);
        }
        
        .input-wrapper .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
            padding: 5px;
        }
        
        .input-wrapper .toggle-password:hover {
            color: #d97528;
        }
        
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #d97528 0%, #b8611f 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(217, 117, 40, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .auth-links {
            margin-top: 25px;
            text-align: center;
        }
        
        .auth-links p {
            margin: 10px 0;
            color: #666;
            font-size: 0.95rem;
        }
        
        .auth-links a {
            color: #d97528;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .auth-links a:hover {
            color: #b8611f;
            text-decoration: underline;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: #999;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #eee;
        }
        
        .divider span {
            padding: 0 15px;
            font-size: 0.9rem;
        }
        
        /* Loading animation */
        .btn-primary.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .btn-primary.loading::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid white;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Remember me checkbox */
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .remember-me input {
            width: 18px;
            height: 18px;
            accent-color: #d97528;
        }
        
        .forgot-link {
            color: #d97528;
            text-decoration: none;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <div class="logo">
            <img src="Logo2.jpg" alt="Rahama's Scents" onerror="this.style.display='none'">
        </div>
        <h2><i class="fas fa-sign-in-alt"></i> Welcome Back</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope icon"></i>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock icon"></i>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                </div>
            </div>
            
            <div class="remember-forgot">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    Remember me
                </label>
                <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
            </div>
            
            <button type="submit" class="btn-primary" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i>
                Login
            </button>
        </form>
        
        <div class="divider">
            <span>or</span>
        </div>
        
        <div class="auth-links">
            <p>Don't have an account? <a href="signup.php">Sign up</a></p>
            <p>Delivery staff? <a href="delivery_login.php">Login here</a></p>
            <p><a href="RahamaScents.php"><i class="fas fa-home"></i> Back to Home</a></p>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
    
    // Form submission with loading
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    
    loginForm.addEventListener('submit', function() {
        loginBtn.classList.add('loading');
        loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
    });
    
    // Input focus animation
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.querySelector('.icon').style.color = '#d97528';
        });
        input.addEventListener('blur', function() {
            this.parentElement.querySelector('.icon').style.color = '#999';
        });
    });
</script>

</body>
</html>