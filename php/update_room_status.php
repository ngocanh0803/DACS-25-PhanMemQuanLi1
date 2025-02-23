<?php
header('Content-Type: application/json');

include 'db_connect.php';

// Lấy dữ liệu JSON từ yêu cầu POST
$data = json_decode(file_get_contents('php://input'), true);

// Kiểm tra xem dữ liệu có đầy đủ không
if (!isset($data['room_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không đầy đủ']);
    exit;
}

$room_id = intval($data['room_id']);
$new_status = strtolower(trim($data['status']));

// Kiểm tra giá trị trạng thái mới hợp lệ
$valid_statuses = ['available', 'occupied', 'maintenance'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
    exit;
}

// Lấy trạng thái hiện tại của phòng và kiểm tra số lượng sinh viên trong phòng
$sql_check = "SELECT status, (SELECT COUNT(*) FROM Students WHERE room_id = ?) AS student_count FROM Rooms WHERE room_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $room_id, $room_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Phòng không tồn tại']);
    exit;
}

$room = $result_check->fetch_assoc();

$current_status = $room['status'];
$student_count = $room['student_count'];

// Xác định điều kiện cho phép cập nhật
$allowed = false;
$message = '';

if ($current_status === 'available' && in_array($new_status, ['occupied', 'maintenance'])) {
    $allowed = true;
} elseif ($current_status === 'maintenance' && in_array($new_status, ['available', 'occupied'])) {
    $allowed = true;
} elseif ($current_status === 'occupied' && $student_count == 0 && in_array($new_status, ['available', 'maintenance'])) {
    $allowed = true;
} else {
    if ($current_status === 'occupied' && $student_count > 0) {
        $message = 'Không thể chuyển phòng đang ở thành trống hoặc đang sửa chữa khi vẫn còn sinh viên trong phòng.';
    } else {
        $message = 'Trạng thái chuyển đổi không hợp lệ.';
    }
}

// Cập nhật trạng thái phòng nếu được phép
if ($allowed) {
    $sql_update = "UPDATE Rooms SET status = ? WHERE room_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $new_status, $room_id);

    if ($stmt_update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái phòng']);
    }

    $stmt_update->close();
} else {
    echo json_encode(['success' => false, 'message' => $message]);
}

$stmt_check->close();
$conn->close();
?>
