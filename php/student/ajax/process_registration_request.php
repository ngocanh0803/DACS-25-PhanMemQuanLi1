<?php
session_start();
header('Content-Type: application/json');

include '../../config/db_connect.php';

// Kiểm tra quyền truy cập: chỉ sinh viên mới có quyền gửi đơn
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'errors' => ['Bạn không có quyền truy cập.']]);
    exit();
}

// Kiểm tra phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'errors' => ['Phương thức không hợp lệ.']]);
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

$response = ['success' => false, 'errors' => [], 'messages' => []]; // Khởi tạo mảng response

// Xử lý file upload (nếu có)
$documentPaths = [];
if (isset($_FILES['documents']) && $_FILES['documents']['error'][0] != UPLOAD_ERR_NO_FILE) {
    $uploadDir = 'uploads/registration_documents/'; // Thư mục upload cho đơn đăng ký
    $absoluteUploadDir = dirname(__FILE__) . '/../uploads/registration_documents/';
    $maxFileSize = 5 * 1024 * 1024; // 5MB - Kích thước file tối đa cho phép

    if (!is_dir($absoluteUploadDir)) {
        if (mkdir($absoluteUploadDir, 0755, true)) {
            $response['messages'][] = "Đã tạo thư mục upload: " . $absoluteUploadDir;
        } else {
            $response['errors'][] = "Không thể tạo thư mục upload: " . $absoluteUploadDir;
            echo json_encode($response);
            exit(); // Dừng nếu không tạo được thư mục
        }
    }
    $response['messages'][] = "Bắt đầu quá trình upload tài liệu...";

    foreach ($_FILES['documents']['tmp_name'] as $key => $tmpName) {
        $filename = basename($_FILES['documents']['name'][$key]);
        if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
            $fileSize = $_FILES['documents']['size'][$key];

            if ($fileSize <= $maxFileSize) {
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                $newFileName = bin2hex(random_bytes(16)) . '.' . $extension;
                $targetFile = $uploadDir . $newFileName;
                $absoluteTargetFile = $absoluteUploadDir . $newFileName;

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

                if (in_array($mime, $allowedTypes)) {
                    if (move_uploaded_file($tmpName, $absoluteTargetFile)) {
                        $documentPaths[] = $targetFile; // Lưu đường dẫn tương đối vào database
                        $response['messages'][] = "File '" . $filename . "' upload thành công.";
                    } else {
                        $response['errors'][] = "Lỗi khi di chuyển file '" . $filename . "'.";
                    }
                } else {
                    $response['errors'][] = "Loại file '" . $filename . "' không được phép. Chỉ chấp nhận: " . implode(', ', $allowedTypes) . ".";
                }
            } else {
                $response['errors'][] = "File '" . $filename . "' quá lớn. Kích thước tối đa: " . number_format($maxFileSize / 1024 / 1024, 2) . "MB.";
            }
        } else {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE   => 'File vượt quá upload_max_filesize trong php.ini.',
                UPLOAD_ERR_FORM_SIZE  => 'File vượt quá MAX_FILE_SIZE trong form HTML.',
                UPLOAD_ERR_PARTIAL    => 'File chỉ được upload một phần.',
                UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm.',
                UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file lên đĩa.',
                UPLOAD_ERR_EXTENSION  => 'Một extension PHP đã dừng việc upload file.',
            ];
            $errorCode = $_FILES['documents']['error'][$key];
            $errorMessage = $error_messages[$errorCode] ?? 'Lỗi không xác định.';
            $response['errors'][] = "Lỗi upload file '" . $filename . "': " . $errorMessage;
        }
    }
    $response['messages'][] = "Hoàn tất quá trình upload tài liệu.";
} else {
    $response['messages'][] = "Không có tài liệu nào được upload kèm theo.";
}

$documentsJson = !empty($documentPaths) ? json_encode($documentPaths) : null;

// Lấy student_id từ bảng Students dựa trên student_code
$sqlStudent = "SELECT student_id FROM Students WHERE student_code = ?";
$stmtStudent = $conn->prepare($sqlStudent);
if (!$stmtStudent) {
    $response['errors'][] = 'Lỗi chuẩn bị truy vấn student: ' . $conn->error;
    echo json_encode($response);
    exit();
}
$stmtStudent->bind_param("s", $student_code);
$stmtStudent->execute();
$resultStudent = $stmtStudent->get_result();
if ($resultStudent->num_rows == 0) {
    $response['errors'][] = 'Không tìm thấy thông tin sinh viên.';
    echo json_encode($response);
    exit();
}
$student = $resultStudent->fetch_assoc();
$student_id = $student['student_id'];
$stmtStudent->close();

// INSERT đơn đăng ký vào bảng Applications
$sql = "INSERT INTO Applications (student_id, desired_start_date, desired_end_date, deposit, documents, status) VALUES (?, ?, ?, ?, ?, 'pending')";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    $response['errors'][] = 'Lỗi chuẩn bị truy vấn application: ' . $conn->error;
    echo json_encode($response);
    exit();
}
$stmt->bind_param("issds", $student_id, $start_date, $end_date, $deposit, $documentsJson);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Đơn đăng ký đã được gửi thành công.';
} else {
    $response['errors'][] = 'Lỗi khi gửi đơn đăng ký: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response); // Trả về JSON response cuối cùng
exit();
?>