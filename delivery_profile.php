<?php
session_start();

if (!isset($_SESSION['delivery_id'])) {
    header("Location: delivery_login.php");
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

$staff_id = $_SESSION['delivery_id'];
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $vehicle_type = $_POST['vehicle_type'];
    
    // Handle picture upload
    $picture = $_POST['existing_picture'] ?? '';
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($_FILES['picture']['tmp_name']);
        
        if (in_array($file_type, $allowed_types)) {
            if (!is_dir('uploads/delivery')) mkdir('uploads/delivery', 0777, true);
            $ext = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
            $picture = 'uploads/delivery/' . uniqid('staff_') . '.' . strtolower($ext);
            move_uploaded_file($_FILES['picture']['tmp_name'], $picture);
        }
    }
    
    $stmt = $pdo->prepare("UPDATE delivery_staff SET full_name = ?, phone = ?, vehicle_type = ?, picture = ? WHERE id = ?");
    if ($stmt->execute([$full_name, $phone, $vehicle_type, $picture, $staff_id])) {
        $_SESSION['delivery_name'] = $full_name;
        $success = "Profile updated successfully!";
    } else {
        $error = "Failed to update profile.";
    }
}

// Fetch staff info
$stmt = $pdo->prepare("SELECT * FROM delivery_staff WHERE id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

// Get delivery stats
$total_deliveries = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE delivery_staff_id = ? AND status = 'delivered'");
$total_deliveries->execute([$staff_id]);
$total_deliveries = $total_deliveries->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Delivery Staff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; min-height: 100vh; }
        
        .header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { color: #d97528; font-size: 1.5rem; }
        .header a { color: white; text-decoration: none; }
        .header a:hover { color: #d97528; }
        
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            background: linear-gradient(135deg, #d97528 0%, #b8611f 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            margin-bottom: 15px;
        }
        
        .default-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 3rem;
        }
        
        .profile-header h2 { margin-bottom: 5px; }
        .profile-header p { opacity: 0.9; }
        
        .stats-row {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 20px;
        }
        .stat-item { text-align: center; }
        .stat-item .number { font-size: 1.5rem; font-weight: bold; }
        .stat-item .label { font-size: 0.9rem; opacity: 0.9; }
        
        .profile-body { padding: 30px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #d97528;
        }
        .form-group input[readonly] {
            background: #f5f5f5;
            color: #666;
        }
        
        .picture-upload {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .picture-upload img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }
        .picture-upload .upload-btn {
            padding: 10px 20px;
            background: #f5f5f5;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .picture-upload input[type="file"] { display: none; }
        
        .vehicle-options {
            display: flex;
            gap: 15px;
        }
        .vehicle-option {
            flex: 1;
            text-align: center;
            padding: 20px;
            border: 2px solid #eee;
            border-radius: 10px;
            cursor: pointer;
        }
        .vehicle-option.selected {
            border-color: #d97528;
            background: #fff5eb;
        }
        .vehicle-option i { font-size: 2rem; color: #d97528; }
        .vehicle-option input { display: none; }
        
        .btn-save {
            width: 100%;
            padding: 14px;
            background: #d97528;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-save:hover { background: #b8611f; }
        
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            margin-top: 10px;
        }
        .status-badge.approved { background: rgba(255,255,255,0.2); }
    </style>
</head>
<body>

<header class="header">
    <h1><i class="fas fa-user"></i> My Profile</h1>
    <a href="delivery_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</header>

<div class="container">
    <div class="profile-card">
        <div class="profile-header">
            <?php if (!empty($staff['picture'])): ?>
                <img src="<?php echo htmlspecialchars($staff['picture']); ?>" class="profile-picture" alt="Profile">
            <?php else: ?>
                <div class="default-avatar"><i class="fas fa-user"></i></div>
            <?php endif; ?>
            <h2><?php echo htmlspecialchars($staff['full_name']); ?></h2>
            <p><i class="fas fa-<?php echo $staff['vehicle_type'] === 'motorcycle' ? 'motorcycle' : 'car'; ?>"></i> <?php echo ucfirst($staff['vehicle_type']); ?> Driver</p>
            <span class="status-badge approved"><i class="fas fa-check-circle"></i> <?php echo ucfirst($staff['status']); ?></span>
            
            <div class="stats-row">
                <div class="stat-item">
                    <div class="number"><?php echo $total_deliveries; ?></div>
                    <div class="label">Total Deliveries</div>
                </div>
                <div class="stat-item">
                    <div class="number"><?php echo date('M Y', strtotime($staff['created_at'])); ?></div>
                    <div class="label">Member Since</div>
                </div>
            </div>
        </div>
        
        <div class="profile-body">
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="existing_picture" value="<?php echo htmlspecialchars($staff['picture'] ?? ''); ?>">
                
                <div class="form-group">
                    <label>Profile Picture</label>
                    <div class="picture-upload">
                        <?php if (!empty($staff['picture'])): ?>
                            <img src="<?php echo htmlspecialchars($staff['picture']); ?>" id="previewImg" alt="Profile">
                        <?php else: ?>
                            <img src="" id="previewImg" alt="Profile" style="display:none;">
                        <?php endif; ?>
                        <label for="picture" class="upload-btn">
                            <i class="fas fa-camera"></i> Change Photo
                        </label>
                        <input type="file" name="picture" id="picture" accept="image/*">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($staff['full_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email (cannot be changed)</label>
                    <input type="email" value="<?php echo htmlspecialchars($staff['email']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($staff['phone']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Vehicle Type</label>
                    <div class="vehicle-options">
                        <div class="vehicle-option <?php echo $staff['vehicle_type'] === 'motorcycle' ? 'selected' : ''; ?>" onclick="selectVehicle('motorcycle', this)">
                            <i class="fas fa-motorcycle"></i>
                            <p>Motorcycle</p>
                            <input type="radio" name="vehicle_type" value="motorcycle" <?php echo $staff['vehicle_type'] === 'motorcycle' ? 'checked' : ''; ?>>
                        </div>
                        <div class="vehicle-option <?php echo $staff['vehicle_type'] === 'car' ? 'selected' : ''; ?>" onclick="selectVehicle('car', this)">
                            <i class="fas fa-car"></i>
                            <p>Car</p>
                            <input type="radio" name="vehicle_type" value="car" <?php echo $staff['vehicle_type'] === 'car' ? 'checked' : ''; ?>>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function selectVehicle(type, element) {
    document.querySelectorAll('.vehicle-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    element.querySelector('input').checked = true;
}

document.getElementById('picture').addEventListener('change', function(e) {
    if (e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('previewImg');
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});
</script>

</body>
</html>