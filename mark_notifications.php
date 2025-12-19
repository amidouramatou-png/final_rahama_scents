<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

$delivery_person_id = $_SESSION['delivery_person_id'];

$update_query = "UPDATE notifications SET is_read = TRUE WHERE delivery_person_id = ? AND is_read = FALSE";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("i", $delivery_person_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Notifications marked as read']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notifications as read']);
}
?>