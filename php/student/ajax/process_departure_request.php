<?php
session_start();
header('Content-Type: application/json');

include '../../config/db_connect.php';

// Kiểm tra quyền truy cập: chỉ sinh viên mới có quyền gửi đơn
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập.']);
    exit();
}

// Kiểm tra phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
    exit();
}

// Lấy dữ liệu từ form
$contract_id = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if (empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Lý do xin rời phòng là bắt buộc.']);
    exit();
}

// Xử lý file upload (nếu có)
$documentPaths = [];
if (isset($_FILES['documents']) && $_FILES['documents']['error'][0] != UPLOAD_ERR_NO_FILE) {
    $uploadDir = '../uploads/departure_documents/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    foreach ($_FILES['documents']['tmp_name'] as $key => $tmpName) {
        // Kiểm tra lỗi upload file
        if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
            $filename = basename($_FILES['documents']['name'][$key]);
            // Tạo tên file độc nhất để tránh trùng lặp
            $targetFile = $uploadDir . time() . '_' . $filename;
            if (move_uploaded_file($tmpName, $targetFile)) {
                $documentPaths[] = $targetFile;
            }
        }
    }
}
$documents_json = !empty($documentPaths) ? json_encode($documentPaths) : null;

// Lấy student_id từ bảng Students dựa trên student_code (student_code = username)
$student_code = $_SESSION['username'];
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

// INSERT đơn xin rời phòng vào bảng Departure_Requests
$sql = "INSERT INTO Departure_Requests (student_id, contract_id, reason, documents, status) VALUES (?, ?, ?, ?, 'pending')";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Lỗi chuẩn bị truy vấn: ' . $conn->error]);
    exit();
}
$stmt->bind_param("iiss", $student_id, $contract_id, $reason, $documents_json);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Đơn xin rời phòng đã được gửi thành công.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi gửi đơn: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
