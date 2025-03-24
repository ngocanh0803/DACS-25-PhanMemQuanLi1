<?php
header('Content-Type: application/json');
session_start();

include '../../config/db_connect.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager', 'student_manager', 'accountant'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập.']);
    exit();
}

// Nhận dữ liệu JSON
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['departure_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit();
}

$departure_id = intval($data['departure_id']);
$action = $data['action'];

// Lấy thông tin đơn rời phòng
$sql = "SELECT dr.*, c.deposit 
        FROM Departure_Requests dr
        JOIN Contracts c ON dr.contract_id = c.contract_id
        WHERE dr.departure_id = ?"; // Lấy cả deposit từ bảng Contracts
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $departure_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Đơn rời phòng không tồn tại.']);
    exit();
}
$departure = $result->fetch_assoc();
$stmt->close();

$student_id = $departure['student_id'];
$contract_id = $departure['contract_id'];
$deposit_amount_original = $departure['deposit']; // Lấy tiền cọc gốc từ hợp đồng
$deposit_amount_refund = $deposit_amount_original; // Mặc định số tiền hoàn trả = tiền cọc gốc
$refund_reduction_reason = null;


// Lấy user_id cho sinh viên này
$sqlUser = "SELECT user_id FROM Users WHERE username = (SELECT student_code FROM Students WHERE student_id = ?)";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $student_id);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user_id = null;
if ($resultUser->num_rows > 0) {
    $u = $resultUser->fetch_assoc();
    $user_id = $u['user_id'];
}
$stmtUser->close();

if ($action === 'approve') {
    // **Nhận giá trị initiate_refund và refund_reduction_reason từ dữ liệu POST**
    $initiateRefund = isset($data['initiate_refund']) ? intval($data['initiate_refund']) : 0;
    $refund_reduction_reason = isset($data['refund_reduction_reason']) ? trim($data['refund_reduction_reason']) : null;
    // Nếu có lý do giảm cọc, thì số tiền hoàn trả sẽ là 0 (hoặc có thể tính toán khác)
     if ($refund_reduction_reason !== null && $refund_reduction_reason !== "") {
         $deposit_amount_refund = 0; // Hoặc bạn có thể tính toán số tiền hoàn trả khác nếu cần
     }


    // Duyệt đơn => cập nhật departure_requests
    $sqlUpdate = "UPDATE Departure_Requests SET status = 'approved', processed_date = NOW(), refund_reduction_reason = ? WHERE departure_id = ?"; //Lưu lý do giảm cọc
    $stmtU = $conn->prepare($sqlUpdate);
    $stmtU->bind_param("si", $refund_reduction_reason, $departure_id); // Bind lý do giảm cọc
    if(!$stmtU->execute()) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi duyệt đơn.']);
        exit();
    }
    $stmtU->close();

    // Update hợp đồng => chuyển status = 'expired'
    $sqlContract = "UPDATE Contracts SET status = 'expired' WHERE contract_id = ?";
    $stmtC = $conn->prepare($sqlContract);
    $stmtC->bind_param("i", $contract_id);
    if(!$stmtC->execute()) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật hợp đồng.']);
        exit();
    }
    $stmtC->close();

    // **Nếu admin chọn "Khởi tạo yêu cầu trả cọc", cập nhật deposit_refund_status**
    if ($initiateRefund == 1) {
        $sqlRefundStatus = "UPDATE Departure_Requests SET deposit_refund_status = 'refund_initiated', refund_amount = ? WHERE departure_id = ?"; // Lưu số tiền cọc hoàn trả
        $stmtRefundStatus = $conn->prepare($sqlRefundStatus);
        $stmtRefundStatus->bind_param("di", $deposit_amount_refund, $departure_id); // Bind số tiền cọc
        $stmtRefundStatus->execute();
        $stmtRefundStatus->close();
        $refund_status_message = " và yêu cầu trả cọc đã được khởi tạo."; // Thêm vào message
    } else {
        $refund_status_message = ".";
    }


    // Thêm thông báo cho sinh viên
    if ($user_id) {
        $notif_title = "Đơn rời phòng (hết hạn) được duyệt";
        // Thêm thông tin về hoàn trả cọc vào message
        $notif_message = "Đơn xin rời phòng do hết hạn hợp đồng của bạn đã được duyệt" . $refund_status_message . " Vui lòng xem trạng thái đơn để biết thêm chi tiết và xác nhận nhận cọc khi bạn đã nhận được tiền.";
        $notif_type = "departure";
        $sqlNotif = "INSERT INTO Notifications (user_id, title, message, notification_type) VALUES (?, ?, ?, ?)";
        $stmtN = $conn->prepare($sqlNotif);
        $stmtN->bind_param("isss", $user_id, $notif_title, $notif_message, $notif_type);
        $stmtN->execute();
        $stmtN->close();
    }

    echo json_encode(['success' => true, 'message' => 'Đơn rời phòng (hết hạn) đã được duyệt' . $refund_status_message]); // Cập nhật message
}
elseif ($action === 'reject') {
    $reason = isset($data['reason']) ? trim($data['reason']) : '';
    // Từ chối => update departure_requests (giữ nguyên)
    $sqlUpdate = "UPDATE Departure_Requests SET status = 'rejected', processed_date = NOW() WHERE departure_id = ?";
    $stmtU = $conn->prepare($sqlUpdate);
    $stmtU->bind_param("i", $departure_id);
    if(!$stmtU->execute()) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi từ chối đơn.']);
        exit();
    }
    $stmtU->close();

    // Gửi thông báo cho sinh viên (giữ nguyên)
    if ($user_id) {
        $notif_title = "Đơn rời phòng (hết hạn) bị từ chối";
        $notif_message = "Đơn của bạn đã bị từ chối. Lý do: " . ($reason ?: "Không có lý do.");
        $notif_type = "departure";
        $sqlNotif = "INSERT INTO Notifications (user_id, title, message, notification_type) VALUES (?, ?, ?, ?)";
        $stmtN = $conn->prepare($sqlNotif);
        $stmtN->bind_param("isss", $user_id, $notif_title, $notif_message, $notif_type);
        $stmtN->execute();
        $stmtN->close();
    }
    echo json_encode(['success' => true, 'message' => 'Đơn rời phòng (hết hạn) đã bị từ chối.']);
}
else {
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
}

$conn->close();
?>