<?php
require_once 'config.php';

$error = '';
$success = '';

// Create uploads folder if not exists
if (!is_dir('uploads/delivery')) {
    mkdir('uploads/delivery', 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $vehicle_type = $_POST['vehicle_type'];

    // Validation
    if (empty($full_name) || empty($email) || empty($phone) || empty($password) || empty($vehicle_type)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif ($vehicle_type !== 'motorcycle' && $vehicle_type !== 'car') {
        $error = "Please select a valid vehicle type!";
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM delivery_staff WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            // Handle picture upload
            $picture = '';
            if (isset($_FILES['picture']) && $_FILES['picture']['error'] === 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = mime_content_type($_FILES['picture']['tmp_name']);
                
                if (in_array($file_type, $allowed_types)) {
                    $ext = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
                    $picture = 'uploads/delivery/' . uniqid('staff_') . '.' . strtolower($ext);
                    move_uploaded_file($_FILES['picture']['tmp_name'], $picture);
                } else {
                    $error = "Invalid image format! Use JPG, PNG, GIF or WEBP.";
                }
            }

            if (empty($error)) {
                // Insert new delivery staff
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO delivery_staff (full_name, email, phone, password, vehicle_type, picture, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->bind_param("ssssss", $full_name, $email, $phone, $hashed_password, $vehicle_type, $picture);

                if ($stmt->execute()) {
                    $success = "Registration successful! Please wait for admin approval.";
                } else {
                    $error = "Something went wrong. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Staff Registration - Rahama's Scents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-box {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
        }
        .register-box h1 {
            color: #d97528;
            text-align: center;
            margin-bottom: 10px;
        }
        .register-box p.subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 16px;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #d97528;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .password-match {
            font-size: 12px;
            margin-top: 5px;
        }
        .match-yes { color: green; }
        .match-no { color: red; }
        .btn {
            width: 100%;
            padding: 14px;
            background: #d97528;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn:hover { background: #b8611f; }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .login-link a {
            color: #d97528;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover { text-decoration: underline; }
        .info-box {
            background: #fef3c7;
            color: #92400e;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .info-box i { margin-right: 8px; }
        
        /* Picture Upload */
        .picture-upload {
            text-align: center;
            margin-bottom: 20px;
        }
        .picture-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #f5f5f5;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px dashed #ddd;
            overflow: hidden;
        }
        .picture-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .picture-preview i {
            font-size: 40px;
            color: #ccc;
        }
        .picture-upload input[type="file"] {
            display: none;
        }
        .picture-upload label {
            display: inline-block;
            padding: 10px 20px;
            background: #f5f5f5;
            color: #333;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .picture-upload label:hover {
            background: #eee;
        }
        
        /* Vehicle Selection */
        .vehicle-options {
            display: flex;
            gap: 15px;
        }
        .vehicle-option {
            flex: 1;
            text-align: center;
            padding: 20px;
            border: 2px solid #eee;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .vehicle-option:hover {
            border-color: #d97528;
        }
        .vehicle-option.selected {
            border-color: #d97528;
            background: #fff5eb;
        }
        .vehicle-option i {
            font-size: 30px;
            color: #d97528;
            margin-bottom: 10px;
        }
        .vehicle-option input {
            display: none;
        }
        
        @media (max-width: 500px) {
            .form-row { grid-template-columns: 1fr; }
            .vehicle-options { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="register-box">
    <h1><i class="fas fa-motorcycle"></i> Delivery Staff</h1>
    <p class="subtitle">Register to join our delivery team</p>

    <div class="info-box">
        <i class="fas fa-info-circle"></i>
        Your account will need admin approval before you can start delivering.
    </div>

    <?php if ($error): ?>
        <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <!-- Profile Picture -->
        <div class="picture-upload">
            <div class="picture-preview" id="picturePreview">
                <i class="fas fa-user"></i>
            </div>
            <label for="picture">
                <i class="fas fa-camera"></i> Upload Photo
            </label>
            <input type="file" name="picture" id="picture" accept="image/*">
        </div>

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" placeholder="Enter your full name" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="you@example.com" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" placeholder="Your phone number" required>
            </div>
        </div>

        <!-- Vehicle Selection -->
        <div class="form-group">
            <label>Vehicle Type</label>
            <div class="vehicle-options">
                <div class="vehicle-option" onclick="selectVehicle('motorcycle', this)">
                    <i class="fas fa-motorcycle"></i>
                    <p>Motorcycle</p>
                    <input type="radio" name="vehicle_type" value="motorcycle">
                </div>
                <div class="vehicle-option" onclick="selectVehicle('car', this)">
                    <i class="fas fa-car"></i>
                    <p>Car</p>
                    <input type="radio" name="vehicle_type" value="car">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" id="password" placeholder="Create a password (min 6 characters)" required>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required>
            <div class="password-match" id="match-message"></div>
        </div>

        <button type="submit" class="btn">
            <i class="fas fa-user-plus"></i> Register
        </button>
    </form>

    <div class="login-link">
        <p>Already registered? <a href="delivery_login.php">Login here</a></p>
        <p style="margin-top: 10px;">Customer? <a href="signup.php">Sign up here</a></p>
    </div>
</div>

<script>
    // Password match check
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const matchMessage = document.getElementById('match-message');

    function checkMatch() {
        if (confirmPassword.value === '') {
            matchMessage.textContent = '';
        } else if (password.value === confirmPassword.value) {
            matchMessage.textContent = '✓ Passwords match';
            matchMessage.className = 'password-match match-yes';
        } else {
            matchMessage.textContent = '✗ Passwords do not match';
            matchMessage.className = 'password-match match-no';
        }
    }

    password.addEventListener('input', checkMatch);
    confirmPassword.addEventListener('input', checkMatch);

    // Vehicle selection
    function selectVehicle(type, element) {
        // Remove selected from all
        document.querySelectorAll('.vehicle-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        // Add selected to clicked
        element.classList.add('selected');
        // Check the radio button
        element.querySelector('input').checked = true;
    }

    // Picture preview
    document.getElementById('picture').addEventListener('change', function(e) {
        if (e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('picturePreview').innerHTML = 
                    '<img src="' + e.target.result + '" alt="Preview">';
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });
</script>

</body>
</html>