<?php
header('Content-Type: application/json');
session_start();

// Kiểm tra quyền admin (admin hoặc manager)
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$facility_id = isset($data['facility_id']) ? intval($data['facility_id']) : 0;
$revoke_reason = isset($data['revoke_reason']) ? trim($data['revoke_reason']) : '';
$revoke_evidence = isset($data['revoke_evidence']) ? trim($data['revoke_evidence']) : '';

if ($facility_id <= 0 || empty($revoke_reason) || empty($revoke_evidence)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin thu hồi.']);
    exit();
}

include '../config/db_connect.php';

// Kiểm tra thiết bị có là của sinh viên (is_student_device == 1)
$sql_check = "SELECT * FROM Facilities WHERE facility_id = ? AND is_student_device = 1";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("i", $facility_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Thiết bị không tồn tại hoặc không phải thiết bị của sinh viên.']);
    exit();
}
$facility = $result->fetch_assoc();
$stmt->close();

// Lấy thông tin sinh viên từ bảng Students dựa trên room_id
// (Giả sử rằng trong trường hợp này, chỉ có 1 sinh viên duy nhất sở hữu thiết bị đó. Nếu không, cần logic khác.)
$sql_student = "SELECT student_id, student_code FROM Students WHERE room_id = ? LIMIT 1";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("i", $facility['room_id']);
$stmt->execute();
$result_student = $stmt->get_result();
$studentInfo = $result_student->fetch_assoc();
$stmt->close();

// Tạo thông báo cho sinh viên
$notificationMessage = "Thiết bị của bạn (Mã: " . $facility['facility_code'] . ", Tên: " . $facility['facility_name'] . ") đã bị thu hồi/xóa. Lý do: " . $revoke_reason . ". Bằng chứng: " . $revoke_evidence . ".";
$dummyUserId = 0; // Bạn cần xác định user_id của sinh viên dựa trên studentInfo, ví dụ:
$sqlUser = "SELECT user_id FROM Users WHERE username = ?";
$stmt = $conn->prepare($sqlUser);
$stmt->bind_param("s", $studentInfo['student_code']);
$stmt->execute();
$resultUser = $stmt->get_result();
if($resultUser->num_rows > 0){
    $userInfo = $resultUser->fetch_assoc();
    $studentUserId = $userInfo['user_id'];
} else {
    $studentUserId = 0;
}
$stmt->close();

// Xóa thiết bị khỏi bảng Facilities
$sql_delete = "DELETE FROM Facilities WHERE facility_id = ?";
$stmt = $conn->prepare($sql_delete);
$stmt->bind_param("i", $facility_id);
if ($stmt->execute()) {
    // Gửi thông báo cho sinh viên
    $sql_notif = "INSERT INTO Notifications (user_id, title, message) VALUES (?, ?, ?)";
    $stmt_notif = $conn->prepare($sql_notif);
    $title = "Thông báo thu hồi thiết bị";
    $stmt_notif->bind_param("iss", $studentUserId, $title, $notificationMessage);
    $stmt_notif->execute();
    $stmt_notif->close();
    echo json_encode(['success' => true, 'message' => 'Thiết bị đã được thu hồi/xóa thành công.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi thu hồi/xóa thiết bị.']);
}
$stmt->close();
$conn->close();
?>
