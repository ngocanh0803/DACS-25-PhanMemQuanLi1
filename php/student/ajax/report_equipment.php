<?php
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

// Lấy student_id từ bảng Students
$sql_student = "SELECT student_id FROM Students WHERE student_code = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $student_id = $student['student_id'];
} else {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin sinh viên.']);
    exit();
}
$stmt->close();

// Lấy dữ liệu từ POST
$facility_id = isset($_POST['facility_id']) ? intval($_POST['facility_id']) : 0;
$reported_quantity = isset($_POST['reported_quantity']) ? intval($_POST['reported_quantity']) : 0;
$reported_condition = isset($_POST['reported_condition']) ? trim($_POST['reported_condition']) : '';

if ($facility_id <= 0 || $reported_quantity <= 0 || empty($reported_condition)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin báo cáo.']);
    exit();
}

// Lưu báo cáo vào bảng Equipment_Reports
$sql_insert = "INSERT INTO Equipment_Reports (facility_id, student_id, reported_quantity, reported_condition) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql_insert);
$stmt->bind_param("iiis", $facility_id, $student_id, $reported_quantity, $reported_condition);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Báo cáo sự cố đã được gửi thành công.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi gửi báo cáo.']);
}
$stmt->close();
$conn->close();
?>
