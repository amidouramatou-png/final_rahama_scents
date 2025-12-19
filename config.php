<?php


// Database connection
$conn = new mysqli("localhost", "root", "", "rahama_scents");

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
