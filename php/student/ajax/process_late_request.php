<?php
session_start();
include '../../config/db_connect.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../student_late_request.php");
    exit();
}

$student_id = intval($_POST['student_id']);
$reason = trim($_POST['reason']);

if (empty($reason)) {
    $_SESSION['error'] = "Vui lòng nhập lý do về muộn.";
    header("Location: ../student_late_request.php");
    exit();
}

// Gửi yêu cầu -> INSERT vào LateRequests
$sql = "INSERT INTO LateRequests (student_id, reason, status) VALUES (?, ?, 'pending')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $student_id, $reason);
if ($stmt->execute()) {
    $_SESSION['success'] = "Gửi yêu cầu thành công.";
} else {
    $_SESSION['error'] = "Lỗi khi gửi yêu cầu: " . $stmt->error;
}
$stmt->close();
$conn->close();

header("Location: ../student_late_request.php");
exit();
?>
