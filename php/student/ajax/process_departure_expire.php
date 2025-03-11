<?php
session_start();
include '../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: departure_expire.php");
    exit();
}

$student_id = intval($_POST['student_id']);
$reason = htmlspecialchars(trim($_POST['reason']));
$contract_id = intval($_POST['contract_id']);

// Kiểm tra xem hợp đồng có đang active không
$sql_check = "SELECT contract_id FROM Contracts WHERE contract_id = ? AND status = 'active' LIMIT 1";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("i", $contract_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['error'] = "Hợp đồng không còn hiệu lực hoặc không tồn tại.";
    header("Location: departure_expire.php");
    exit();
}
$stmt->close();

// Insert vào bảng Departure_Requests
$sql_insert = "INSERT INTO Departure_Requests (student_id, contract_id, reason, status) VALUES (?, ?, ?, 'pending')";
$stmt_insert = $conn->prepare($sql_insert);
$stmt_insert->bind_param("iis", $student_id, $contract_id, $reason);

if ($stmt_insert->execute()) {
    $_SESSION['success'] = "Đơn xin rời phòng do hết hạn HĐ đã được gửi thành công.";
} else {
    $_SESSION['error'] = "Có lỗi xảy ra, vui lòng thử lại.";
}
$stmt_insert->close();
$conn->close();

// Chuyển hướng sang trang xem trạng thái
header("Location: ../departure_status2.php");

exit();
?>
