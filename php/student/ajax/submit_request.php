<?php
// submit_request.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Phiên làm việc hết hạn. Vui lòng đăng nhập lại.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    exit();
}

$student_code = $_SESSION['username'];

include '../../config/db_connect.php';

// Lấy student_id và room_id từ bảng Students
$sql_student = "SELECT student_id, room_id FROM Students WHERE student_code = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $student_id = $student['student_id'];
    $room_id = $student['room_id'];
} else {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin sinh viên.']);
    exit();
}
$stmt->close();

// Lấy dữ liệu từ POST
$request_type = isset($_POST['request_type']) ? trim($_POST['request_type']) : '';
$facility_name = isset($_POST['facility_name']) ? trim($_POST['facility_name']) : '';
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

if (empty($request_type) || empty($facility_name) || $quantity <= 0 || empty($description)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin yêu cầu.']);
    exit();
}

// Lưu đơn yêu cầu vào bảng Equipment_Requests
$sql_insert = "INSERT INTO Equipment_Requests (student_id, room_id, request_type, facility_name, quantity, description) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql_insert);
$stmt->bind_param("iissis", $student_id, $room_id, $request_type, $facility_name, $quantity, $description);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Yêu cầu đã được gửi thành công.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi gửi yêu cầu.']);
}
$stmt->close();
$conn->close();
?>
