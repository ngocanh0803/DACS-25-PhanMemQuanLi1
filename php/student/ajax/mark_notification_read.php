<?php
// mark_notification_read.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
    exit();
}

$notification_id = intval($_POST['notification_id']);
include '../../config/db_connect.php';

$sql = "UPDATE Notifications SET is_read = 1 WHERE notification_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $notification_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Thông báo đã được đánh dấu đã đọc']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật thông báo']);
}
$stmt->close();
$conn->close();
?>
