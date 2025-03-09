<?php
header('Content-Type: application/json');
session_start();
include '../../config/db_connect.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'manager', 'student_manager', 'accountant'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập.']);
    exit();
}

// Nhận dữ liệu từ AJAX
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['departure_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit();
}

$departure_id = intval($data['departure_id']);
$action = $data['action'];

// Lấy thông tin đơn xin rời phòng
$sql = "SELECT * FROM Departure_Requests WHERE departure_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $departure_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Đơn xin rời phòng không tồn tại.']);
    exit();
}
$request = $result->fetch_assoc();
$stmt->close();

if ($action === 'approve') {
    // Yêu cầu phải có room_id được gửi kèm để xác định phòng bàn giao
    if (!isset($data['room_id']) || empty($data['room_id'])) {
        echo json_encode(['success' => false, 'message' => 'Chưa chọn phòng bàn giao.']);
        exit();
    }
    $room_id = intval($data['room_id']);

    // Cập nhật đơn xin rời phòng thành 'approved' và ghi nhận ngày xử lý
    $processed_date = date('Y-m-d H:i:s');
    $sql_update = "UPDATE Departure_Requests SET status = 'approved', processed_date = ? WHERE departure_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $processed_date, $departure_id);
    $stmt_update->execute();
    $stmt_update->close();

    // Nếu đơn có hợp đồng liên kết, bạn có thể cập nhật hợp đồng thành kết thúc sớm
    if (!empty($request['contract_id'])) {
        $contract_id = $request['contract_id'];
        // Ví dụ: cập nhật hợp đồng thành 'terminated' và lưu lý do rời phòng
        $termination_reason = "Sinh viên xin rời phòng trước hạn hợp đồng.";
        $terminated_at = date('Y-m-d H:i:s');
        $sql_contract = "UPDATE Contracts SET status = 'terminated', end_date = ?, terms = CONCAT(terms, ' | ', ?) WHERE contract_id = ?";
        $stmt_contract = $conn->prepare($sql_contract);
        $stmt_contract->bind_param("ssi", $terminated_at, $termination_reason, $contract_id);
        $stmt_contract->execute();
        $stmt_contract->close();
    }

    // Gửi thông báo đến sinh viên
    $sql_user = "SELECT user_id FROM Users WHERE username = (SELECT student_code FROM Students WHERE student_id = ?)";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $request['student_id']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        $user = $result_user->fetch_assoc();
        $user_id = $user['user_id'];
        $notif_title = "Đơn xin rời phòng được duyệt";
        $notif_message = "Đơn xin rời phòng của bạn đã được duyệt. Vui lòng hoàn tất thủ tục bàn giao phòng.";
        $notif_type = "general";
        $sql_notif = "INSERT INTO Notifications (user_id, title, message, notification_type) VALUES (?, ?, ?, ?)";
        $stmt_notif = $conn->prepare($sql_notif);
        $stmt_notif->bind_param("isss", $user_id, $notif_title, $notif_message, $notif_type);
        $stmt_notif->execute();
        $stmt_notif->close();
    }
    $stmt_user->close();

    echo json_encode(['success' => true, 'message' => 'Đơn xin rời phòng đã được duyệt và cập nhật hợp đồng (nếu có).']);
} elseif ($action === 'reject') {
    $reject_reason = isset($data['reason']) ? trim($data['reason']) : '';
    // Cập nhật đơn thành rejected
    $sql_update = "UPDATE Departure_Requests SET status = 'rejected', processed_date = ? WHERE departure_id = ?";
    $processed_date = date('Y-m-d H:i:s');
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $processed_date, $departure_id);
    $stmt_update->execute();
    $stmt_update->close();

    // Gửi thông báo từ chối đến sinh viên
    $sql_user = "SELECT user_id FROM Users WHERE username = (SELECT student_code FROM Students WHERE student_id = ?)";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $request['student_id']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        $user = $result_user->fetch_assoc();
        $user_id = $user['user_id'];
        $notif_title = "Đơn xin rời phòng bị từ chối";
        $notif_message = "Đơn xin rời phòng của bạn đã bị từ chối. Lý do: " . ($reject_reason ? $reject_reason : "Không có lý do cụ thể.");
        $notif_type = "general";
        $sql_notif = "INSERT INTO Notifications (user_id, title, message, notification_type) VALUES (?, ?, ?, ?)";
        $stmt_notif = $conn->prepare($sql_notif);
        $stmt_notif->bind_param("isss", $user_id, $notif_title, $notif_message, $notif_type);
        $stmt_notif->execute();
        $stmt_notif->close();
    }
    $stmt_user->close();

    echo json_encode(['success' => true, 'message' => 'Đơn xin rời phòng đã bị từ chối.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
}

$conn->close();
?>
