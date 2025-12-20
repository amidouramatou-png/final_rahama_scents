<?php


// Database connection
$host = 'sql207.infinityfree.com';
$dbname = 'if0_40722542_rahama_scents';
$username = 'if0_40722542';
$password = 'qpmME4f74uIj';
$conn = new mysqli($host, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get user role
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// Redirect based on role
function redirectBasedOnRole() {
    if (!isset($_SESSION['role'])) {
        header('Location: login.php');
        exit();
    }
    
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin.php');
            exit();
        case 'customer':
            header('Location: customer_dashboard.php');
            exit();
        case 'delivery':
            header('Location: delivery_dashboard.php');
            exit();
        default:
            header('Location: shop.php');
            exit();
    }
}
?>
