<?php
include '../config/db_connect.php';

// Kiểm tra tham số `room_id`
$room_id = isset($_GET['room_id']) ? $_GET['room_id'] : '';

if (!$room_id) {
    echo json_encode(['error' => 'Room ID is required.']);
    exit;
}

// Truy vấn lấy chi tiết phòng
$sql = "SELECT room_number, capacity, status 
        FROM Rooms 
        WHERE room_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $room = $result->fetch_assoc();

    // Truy vấn lấy danh sách sinh viên trong phòng
    $studentSql = "SELECT full_name, age 
                   FROM Students 
                   WHERE room_id = ?";
    $studentStmt = $conn->prepare($studentSql);
    $studentStmt->bind_param("i", $room_id);
    $studentStmt->execute();
    $studentResult = $studentStmt->get_result();

    // Lấy thông tin sinh viên
    $students = [];
    while ($student = $studentResult->fetch_assoc()) {
        $students[] = [
            'name' => $student['full_name'],
            'age' => $student['age']
        ];
    }

    // Thêm thông tin sinh viên vào dữ liệu phòng
    $room['students'] = $students;

    // Trả về dữ liệu JSON cho phòng
    echo json_encode($room);
} else {
    echo json_encode(['error' => 'Room not found.']);
}
?>
