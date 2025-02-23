<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền']);
    exit();
}

include 'db_connect.php';
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['password']) || !isset($data['role']) || !isset($data['is_approved'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit();
}

$username = trim($data['username']);
// $password = password_hash($data['password'], PASSWORD_DEFAULT); // Hash password
$password = trim($data['password']); // Hash password
$role = $data['role'];
$is_approved = intval($data['is_approved']);

$sql = "INSERT INTO Users (username, password, role, is_approved) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $username, $password, $role, $is_approved);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Tài khoản đã được thêm thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm tài khoản']);
}
$stmt->close();
$conn->close();
?>