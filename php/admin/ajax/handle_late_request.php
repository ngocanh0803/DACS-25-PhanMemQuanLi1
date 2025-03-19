<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin','manager','student_manager','accountant'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập.']);
    exit();
}
include '../../config/db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['late_request_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit();
}

$late_request_id = intval($data['late_request_id']);
$action = $data['action'];

$sql = "SELECT * FROM LateRequests WHERE late_request_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $late_request_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy yêu cầu.']);
    exit();
}
$request = $result->fetch_assoc();
$stmt->close();

if ($action === 'approve') {
    // Duyệt => update status=approved, processed_time=NOW()
    $sqlU = "UPDATE LateRequests SET status='approved', processed_time=NOW() WHERE late_request_id=?";
    $stmtU = $conn->prepare($sqlU);
    $stmtU->bind_param("i", $late_request_id);
    if ($stmtU->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đã duyệt yêu cầu (hỗ trợ mở cửa).']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi duyệt yêu cầu.']);
    }
    $stmtU->close();
}
elseif ($action === 'reject') {
    // Từ chối => update status=rejected, processed_time=NOW(), note=reason
    $reason = $data['reject_reason'] ?? '';
    $sqlU = "UPDATE LateRequests SET status='rejected', processed_time=NOW(), note=? WHERE late_request_id=?";
    $stmtU = $conn->prepare($sqlU);
    $stmtU->bind_param("si", $reason, $late_request_id);
    if ($stmtU->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đã từ chối yêu cầu.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi từ chối yêu cầu.']);
    }
    $stmtU->close();
}
elseif ($action === 'mark_violation') {
    // Đánh dấu is_violation=1, note="Đã ghi vi phạm"
    $sqlU = "UPDATE LateRequests SET is_violation=1, note='Đã ghi vi phạm' WHERE late_request_id=?";
    $stmtU = $conn->prepare($sqlU);
    $stmtU->bind_param("i", $late_request_id);
    if ($stmtU->execute()) {
        echo json_encode(['success' => true, 'message' => 'Đã đánh dấu vi phạm cho yêu cầu này.' ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi đánh dấu vi phạm.']);
    }
    $stmtU->close();
}
else {
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
}

$conn->close();
?>
