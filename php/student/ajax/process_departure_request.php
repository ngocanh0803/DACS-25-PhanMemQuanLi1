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
    $uploadDir = 'uploads/departure_documents/';
    // $absoluteUploadDir = realpath(dirname(__FILE__)) . '/' . $uploadDir;
    $absoluteUploadDir = realpath(dirname(__FILE__) . '/../') . '/uploads/departure_documents/';

    if (!is_dir($absoluteUploadDir)) {
        mkdir($absoluteUploadDir, 0755, true);
         $response['messages'][] = "Đã tạo thư mục: " . $absoluteUploadDir; // Thêm vào mảng messages

    }
    $response['messages'][] = "Bắt đầu quá trình upload...";


    foreach ($_FILES['documents']['tmp_name'] as $key => $tmpName) {
        if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
            $filename = basename($_FILES['documents']['name'][$key]);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $newFileName = bin2hex(random_bytes(16)) . '.' . $extension;
            $targetFile = $uploadDir . $newFileName;
            $absoluteTargetFile = $absoluteUploadDir . $newFileName;

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpName);
            finfo_close($finfo);

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

            if (in_array($mime, $allowedTypes)) {
                $maxFileSize = 5 * 1024 * 1024; // 5MB
                if($_FILES['documents']['size'][$key] <= $maxFileSize){
                    if (move_uploaded_file($tmpName, $absoluteTargetFile)) {
                        // $documentPaths[] = $targetFile;
                        // $documentPaths[] = 'php/student/uploads/departure_documents/' . $newFileName; // Đường dẫn tương đối TỪ THƯ MỤC GỐC
                        $documentPaths[] = 'uploads/departure_documents/' . $newFileName; //["uploads\/departure_documents\/019569a0941ff0275ee03c11208c983f.docx"]
                        // $documentPaths[] = 'php/student/uploads/departure_documents/' . $newFileName;
                        $response['messages'][] = "Đã upload file: " . $filename . " thành công. Đường dẫn tuyệt đối: " . $absoluteTargetFile;
                    } else {
                       $response['errors'][] = "Lỗi khi di chuyển file: " . $filename;
                    }
                } else {
                   $response['errors'][] = "Lỗi: File " . $filename . " quá lớn. Kích thước tối đa: " . $maxFileSize/1024/1024 . "MB.";
                }
            } else {
               $response['errors'][] = "Lỗi: Loại file " . $filename . " (" . $mime . ") không được phép.";
            }
        }
        else { // Xử lý các lỗi upload khác
            $error_messages = [
                UPLOAD_ERR_INI_SIZE   => 'File vượt quá upload_max_filesize trong php.ini.',
                UPLOAD_ERR_FORM_SIZE  => 'File vượt quá MAX_FILE_SIZE trong form HTML.',
                UPLOAD_ERR_PARTIAL    => 'File chỉ được upload một phần.',
                UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm.',
                UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file lên đĩa.',
                UPLOAD_ERR_EXTENSION  => 'Một extension PHP đã dừng việc upload file.',
            ];
            $error_code = $_FILES['documents']['error'][$key];
            $error_message = $error_messages[$error_code] ?? 'Lỗi không xác định.';  // Nếu không có trong mảng $error_messages, thì trả về "Lỗi không xác định."
             $response['errors'][] = "Lỗi upload file " . basename($_FILES['documents']['name'][$key]) . ": " . $error_message;
        }
    }
     $response['messages'][] = "Quá trình upload kết thúc.";
} else {
    $response['messages'][] = "Không có file nào được chọn để upload."; // Thêm thông báo vào mảng
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
