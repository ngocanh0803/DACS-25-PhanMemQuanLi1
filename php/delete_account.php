<?php
header('Content-Type: application/json');

session_start();
// Kiểm tra vai trò admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền']);
    exit();
}

include 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit();
}

$user_id = intval($data['user_id']);

// Chuẩn bị truy vấn xóa
$sql = "DELETE FROM Users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Tài khoản đã được xóa']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa tài khoản']);
}

$stmt->close();
$conn->close();
?>