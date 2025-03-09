<?php
// get_notifications.php
header('Content-Type: application/json');
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

include '../../config/db_connect.php';

// Lấy user_id từ bảng Users dựa trên username 
$username = $_SESSION['username'];
$sql = "SELECT user_id FROM Users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy người dùng']);
    exit();
}
$user = $result->fetch_assoc();
$user_id = $user['user_id'];
$stmt->close();

// Lấy TẤT CẢ thông báo (đã đọc + chưa đọc) cho user này
$sql = "
    SELECT notification_id, title, message, created_at, is_read
    FROM Notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    // $row['notification_id'], $row['title'], ...
    $notifications[] = $row;
}
$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'notifications' => $notifications
]);
?>
