<?php
header('Content-Type: application/json');
session_start();

// Verify student login and role (optional, but recommended for security)
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

include '../../config/db_connect.php'; // Adjust path as needed

$departureId = intval($_POST['departure_id']);

if ($departureId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid departure request ID.']);
    exit();
}

// Verify that the departure request belongs to the logged-in student (important security check)
$studentCode = $_SESSION['username'];
$sqlCheckOwnership = "SELECT dr.departure_id FROM Departure_Requests dr
                      JOIN Students s ON dr.student_id = s.student_id
                      WHERE dr.departure_id = ? AND s.student_code = ?";
$stmtCheckOwnership = $conn->prepare($sqlCheckOwnership);
$stmtCheckOwnership->bind_param("is", $departureId, $studentCode);
$stmtCheckOwnership->execute();
$stmtCheckOwnership->store_result();

if ($stmtCheckOwnership->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Departure request not found or does not belong to you.']);
    $stmtCheckOwnership->close();
    $conn->close();
    exit();
}
$stmtCheckOwnership->close();


// Update deposit_refund_status to 'refund_confirmed_student'
$sqlUpdateRefundStatus = "UPDATE Departure_Requests SET deposit_refund_status = 'refund_confirmed_student' WHERE departure_id = ?";
$stmtUpdateRefundStatus = $conn->prepare($sqlUpdateRefundStatus);

if ($stmtUpdateRefundStatus === false) {
    echo json_encode(['success' => false, 'message' => 'Tuyên bố chuẩn bị lỗi cơ sở dữ liệu: ' . $conn->error]);
    $conn->close();
    exit();
}

$stmtUpdateRefundStatus->bind_param("i", $departureId);

if ($stmtUpdateRefundStatus->execute()) {
    echo json_encode(['success' => true, 'message' => 'Đã xác nhận hoàn tiền thành công.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật trạng thái hoàn tiền: ' . $stmtUpdateRefundStatus->error]);
}

$stmtUpdateRefundStatus->close();
$conn->close();

?>