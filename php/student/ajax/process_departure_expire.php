<?php
header('Content-Type: application/json'); // Đặt header để báo là JSON response
session_start();
include '../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Không phải POST request, trả về lỗi JSON
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'errors' => ['Yêu cầu không hợp lệ.']]);
    exit();
}

$student_id = intval($_POST['student_id']);
$reason = htmlspecialchars(trim($_POST['reason']));
$contract_id = intval($_POST['contract_id']);

$response = ['success' => false, 'errors' => [], 'message' => '']; // Mảng response để trả về JSON

// Kiểm tra xem hợp đồng có đang active không
$sql_check = "SELECT contract_id FROM Contracts WHERE contract_id = ? AND status = 'active' LIMIT 1";
$stmt = $conn->prepare($sql_check);
if ($stmt === false) {
    http_response_code(500); // Internal Server Error
    $response['errors'][] = "Lỗi server: " . $conn->error;
    echo json_encode($response);
    $conn->close();
    exit();
}
$stmt->bind_param("i", $contract_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $response['errors'][] = "Hợp đồng không còn hiệu lực hoặc không tồn tại.";
    echo json_encode($response);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// Insert vào bảng Departure_Requests
$sql_insert = "INSERT INTO Departure_Requests (student_id, contract_id, reason, status) VALUES (?, ?, ?, 'pending')";
$stmt_insert = $conn->prepare($sql_insert);
if ($stmt_insert === false) {
    http_response_code(500); // Internal Server Error
    $response['errors'][] = "Lỗi server: " . $conn->error;
    echo json_encode($response);
    $conn->close();
    exit();
}
$stmt_insert->bind_param("iis", $student_id, $contract_id, $reason);

if ($stmt_insert->execute()) {
    $response['success'] = true;
    $response['message'] = "Đơn xin rời phòng do hết hạn HĐ đã được gửi thành công.";
    $response['messages'] = ["Đơn xin rời phòng do hết hạn HĐ đã được gửi thành công."]; // Thêm messages để thống nhất format
} else {
    $response['errors'][] = "Có lỗi xảy ra, vui lòng thử lại.";
}
$stmt_insert->close();
$conn->close();

echo json_encode($response); // Trả về JSON response
exit();
?>