<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectBasedOnRole();
}

$page_title = "Sign Up";

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $role = 'customer';  // ✅ HARDCODED - customers can only sign up as customers!
    
    // Check if user exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $error = "User already exists with this email!";
    } else {
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $email, $password, $full_name, $phone, $role);
        
        if ($stmt->execute()) {
            $success = "Account created successfully! Please login.";
        } else {
            $error = "Error creating account: " . $conn->error;
        }
        $stmt->close();
    }
    $check_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Rahama's Scents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            max-width: 500px;
        }
        .auth-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .auth-card h2 {
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
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #d97528;
        }
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: #d97528;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: #c46820;
        }
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        .auth-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .auth-footer a {
            color: #d97528;
            text-decoration: none;
            font-weight: 600;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }
        .requirements-list {
            margin-top: 10px;
            font-size: 0.85rem;
        }
        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 5px 0;
            color: #666;
        }
        .requirement-icon {
            font-size: 0.8rem;
        }
        .password-strength {
            height: 5px;
            background: #eee;
            border-radius: 5px;
            margin-top: 10px;
            overflow: hidden;
        }
        .strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }
        .password-feedback, #confirm-feedback {
            font-size: 0.85rem;
            margin-top: 5px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        @media (max-width: 500px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <h2><i class="fas fa-user-plus"></i> Create Account</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="signupForm">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Enter your full name" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="Your phone number" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Create Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Create a strong password" required>
                
                <div class="requirements-list">
                    <div class="requirement">
                        <span id="length-icon" class="requirement-icon">❌</span>
                        <span>At least 8 characters</span>
                    </div>
                    <div class="requirement">
                        <span id="uppercase-icon" class="requirement-icon">❌</span>
                        <span>One uppercase letter</span>
                    </div>
                    <div class="requirement">
                        <span id="number-icon" class="requirement-icon">❌</span>
                        <span>One number</span>
                    </div>
                    <div class="requirement">
                        <span id="special-icon" class="requirement-icon">❌</span>
                        <span>One special character (!@#$%^&*)</span>
                    </div>
                </div>
                
                <div class="password-strength">
                    <div id="strength-bar" class="strength-bar"></div>
                </div>
                <div id="password-feedback" class="password-feedback"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter your password" required>
                <div id="confirm-feedback" class="password-feedback"></div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Login here</a></p>
            <p style="margin-top: 10px;">Want to deliver? <a href="delivery_register.php">Register as Delivery Staff</a></p>
        </div>
    </div>
</div>

<script>
// Password validation
const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirm_password');
const strengthBar = document.getElementById('strength-bar');
const passwordFeedback = document.getElementById('password-feedback');
const confirmFeedback = document.getElementById('confirm-feedback');

password.addEventListener('input', function() {
    const val = this.value;
    let strength = 0;
    
    // Check requirements
    const hasLength = val.length >= 8;
    const hasUppercase = /[A-Z]/.test(val);
    const hasNumber = /[0-9]/.test(val);
    const hasSpecial = /[!@#$%^&*]/.test(val);
    
    document.getElementById('length-icon').textContent = hasLength ? '✅' : '❌';
    document.getElementById('uppercase-icon').textContent = hasUppercase ? '✅' : '❌';
    document.getElementById('number-icon').textContent = hasNumber ? '✅' : '❌';
    document.getElementById('special-icon').textContent = hasSpecial ? '✅' : '❌';
    
    if (hasLength) strength += 25;
    if (hasUppercase) strength += 25;
    if (hasNumber) strength += 25;
    if (hasSpecial) strength += 25;
    
    strengthBar.style.width = strength + '%';
    
    if (strength <= 25) {
        strengthBar.style.background = '#ef4444';
        passwordFeedback.textContent = 'Weak password';
        passwordFeedback.style.color = '#ef4444';
    } else if (strength <= 50) {
        strengthBar.style.background = '#f59e0b';
        passwordFeedback.textContent = 'Fair password';
        passwordFeedback.style.color = '#f59e0b';
    } else if (strength <= 75) {
        strengthBar.style.background = '#3b82f6';
        passwordFeedback.textContent = 'Good password';
        passwordFeedback.style.color = '#3b82f6';
    } else {
        strengthBar.style.background = '#10b981';
        passwordFeedback.textContent = 'Strong password!';
        passwordFeedback.style.color = '#10b981';
    }
    
    checkPasswordMatch();
});

confirmPassword.addEventListener('input', checkPasswordMatch);

function checkPasswordMatch() {
    if (confirmPassword.value === '') {
        confirmFeedback.textContent = '';
    } else if (password.value === confirmPassword.value) {
        confirmFeedback.textContent = '✅ Passwords match';
        confirmFeedback.style.color = '#10b981';
    } else {
        confirmFeedback.textContent = '❌ Passwords do not match';
        confirmFeedback.style.color = '#ef4444';
    }
}

// Form validation
document.getElementById('signupForm').addEventListener('submit', function(e) {
    if (password.value !== confirmPassword.value) {
        e.preventDefault();
        alert('Passwords do not match!');
    }
});
</script>

</body>
</html>