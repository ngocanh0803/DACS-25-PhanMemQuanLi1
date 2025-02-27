<?php
header('Content-Type: application/json');

include '../config/db_connect.php';

// Lấy room_id từ URL
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

// Kiểm tra nếu room_id hợp lệ
if ($room_id <= 0) {
    echo json_encode(["error" => "Room ID không hợp lệ."]);
    exit;
}

// Chuẩn bị câu truy vấn để lấy danh sách sinh viên trong phòng
$sql = "SELECT student_code, full_name, email, phone FROM Students WHERE room_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(["error" => "Lỗi truy vấn SQL: " . $conn->error]);
    exit;
}

// Liên kết tham số và thực thi câu lệnh
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

$students = [];

// Lấy dữ liệu và lưu vào mảng
while ($row = $result->fetch_assoc()) {
    $students[] = [
        'student_code' => $row['student_code'],
        'full_name' => $row['full_name'],
        'email' => $row['email'],
        'phone' => $row['phone']
    ];
}

// Đóng kết nối và trả về dữ liệu JSON
$stmt->close();
$conn->close();

echo json_encode($students);
