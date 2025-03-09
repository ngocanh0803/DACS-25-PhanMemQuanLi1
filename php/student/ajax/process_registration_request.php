<?php
session_start();
include '../../config/db_connect.php';

// Kiểm tra sinh viên
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập.']);
    exit();
}

// Lấy dữ liệu từ form
$student_code = trim($_POST['student_code']);
$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$address = trim($_POST['address']);
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$deposit = floatval($_POST['deposit']);

// Xử lý upload file (nếu có)
$documentPaths = [];
if (isset($_FILES['documents']) && $_FILES['documents']['error'][0] != UPLOAD_ERR_NO_FILE) {
    $uploadDir = '../uploads/documents/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    foreach ($_FILES['documents']['tmp_name'] as $key => $tmpName) {
        $filename = basename($_FILES['documents']['name'][$key]);
        $targetFile = $uploadDir . time() . '_' . $filename;
        if (move_uploaded_file($tmpName, $targetFile)) {
            $documentPaths[] = $targetFile;
        }
    }
}

// Chuyển đổi mảng file thành JSON để lưu vào cơ sở dữ liệu
$documents_json = !empty($documentPaths) ? json_encode($documentPaths) : null;

// Lấy student_id từ bảng Students dựa trên student_code
$sqlStudent = "SELECT student_id FROM Students WHERE student_code = ?";
$stmtStudent = $conn->prepare($sqlStudent);
$stmtStudent->bind_param("s", $student_code);
$stmtStudent->execute();
$resultStudent = $stmtStudent->get_result();
if ($resultStudent->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin sinh viên.']);
    exit();
}
$student = $resultStudent->fetch_assoc();
$student_id = $student['student_id'];
$stmtStudent->close();

// INSERT đơn đăng ký vào bảng Applications
$sql = "INSERT INTO Applications (student_id, desired_start_date, desired_end_date, deposit, documents, status) VALUES (?, ?, ?, ?, ?, 'pending')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issds", $student_id, $start_date, $end_date, $deposit, $documents_json);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Đơn đăng ký đã được gửi thành công.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi gửi đơn đăng ký.']);
}
$stmt->close();
$conn->close();
?>
