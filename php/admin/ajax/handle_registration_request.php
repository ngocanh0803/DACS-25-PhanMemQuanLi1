<?php
header('Content-Type: application/json');
session_start();
include '../../config/db_connect.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager', 'student_manager', 'accountant'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập.']);
    exit();
}

// Nhận dữ liệu JSON từ AJAX
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['application_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit();
}

$application_id = intval($data['application_id']);
$action = $data['action'];

// Lấy thông tin đơn đăng ký
$sql = "SELECT * FROM Applications WHERE application_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Đơn đăng ký không tồn tại.']);
    exit();
}
$application = $result->fetch_assoc();
$stmt->close();

if ($action === 'approve') {
    // Yêu cầu phải có room_id được gửi kèm
    if (!isset($data['room_id']) || empty($data['room_id'])) {
        echo json_encode(['success' => false, 'message' => 'Chưa chọn phòng.']);
        exit();
    }
    $room_id = intval($data['room_id']);
    
    // Cập nhật đơn đăng ký thành approved
    $sql_update = "UPDATE Applications SET status = 'approved' WHERE application_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $application_id);
    $stmt_update->execute();
    $stmt_update->close();

    // Tạo hợp đồng mới dựa trên thông tin từ đơn đăng ký và room_id được chọn
    // Tạo mã hợp đồng: VD: CT-{student_id}-{timestamp}
    $contract_code = "CT-" . $application['student_id'] . "-" . time();
    $student_id = $application['student_id'];
    $desired_start_date = $application['desired_start_date'];
    $desired_end_date = $application['desired_end_date'];
    $deposit = $application['deposit'];
    $terms = "Hợp đồng đăng ký ở ký túc xá dựa trên đơn đăng ký của sinh viên.";
    $signed_date = date('Y-m-d'); // Ngày duyệt đơn

    $sql_contract = "INSERT INTO Contracts (contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    $stmt_contract = $conn->prepare($sql_contract);
    $stmt_contract->bind_param("siissids", $contract_code, $student_id, $room_id, $signed_date, $desired_start_date, $desired_end_date, $deposit, $terms);
    if (!$stmt_contract->execute()) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi tạo hợp đồng.']);
        exit();
    }
    $stmt_contract->close();

    // Gửi thông báo đến sinh viên về duyệt đơn và tạo hợp đồng
    // Lấy user_id của sinh viên từ bảng Users (username == student_code)
    $sql_user = "SELECT user_id FROM Users WHERE username = (SELECT student_code FROM Students WHERE student_id = ?)";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $student_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        $user = $result_user->fetch_assoc();
        $user_id = $user['user_id'];
        $notif_title = "Đơn đăng ký được duyệt";
        $notif_message = "Đơn đăng ký ở của bạn đã được duyệt. Hợp đồng (mã: {$contract_code}) đã được tạo.";
        $notif_type = "contract";
        $sql_notif = "INSERT INTO Notifications (user_id, title, message, notification_type) VALUES (?, ?, ?, ?)";
        $stmt_notif = $conn->prepare($sql_notif);
        $stmt_notif->bind_param("isss", $user_id, $notif_title, $notif_message, $notif_type);
        $stmt_notif->execute();
        $stmt_notif->close();
    }
    $stmt_user->close();

    echo json_encode(['success' => true, 'message' => 'Đơn đăng ký đã được duyệt và hợp đồng đã được tạo.']);
} elseif ($action === 'reject') {
    // Lấy lý do từ dữ liệu gửi (nếu có)
    $reject_reason = isset($data['reason']) ? trim($data['reason']) : '';
    
    // Cập nhật đơn đăng ký thành rejected
    $sql_update = "UPDATE Applications SET status = 'rejected' WHERE application_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $application_id);
    $stmt_update->execute();
    $stmt_update->close();

    // Gửi thông báo từ chối đến sinh viên
    $sql_user = "SELECT user_id FROM Users WHERE username = (SELECT student_code FROM Students WHERE student_id = ?)";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $application['student_id']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        $user = $result_user->fetch_assoc();
        $user_id = $user['user_id'];
        $notif_title = "Đơn đăng ký bị từ chối";
        $notif_message = "Đơn đăng ký ở của bạn đã bị từ chối. Lý do: " . ($reject_reason ? $reject_reason : "Không có lý do cụ thể.");
        $notif_type = "contract";
        $sql_notif = "INSERT INTO Notifications (user_id, title, message, notification_type) VALUES (?, ?, ?, ?)";
        $stmt_notif = $conn->prepare($sql_notif);
        $stmt_notif->bind_param("isss", $user_id, $notif_title, $notif_message, $notif_type);
        $stmt_notif->execute();
        $stmt_notif->close();
    }
    $stmt_user->close();

    echo json_encode(['success' => true, 'message' => 'Đơn đăng ký đã bị từ chối.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
}

$conn->close();
?>
