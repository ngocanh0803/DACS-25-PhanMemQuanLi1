<?php
// add_facility.php
header('Content-Type: application/json');
include '../config/db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

$room_id = intval($data['room_id']);
$facility_code = htmlspecialchars($data['facility_code']);
$facility_name = htmlspecialchars($data['facility_name']);
$quantity = intval($data['quantity']);
$status = htmlspecialchars($data['status']);

$sql = "INSERT INTO Facilities (facility_code, room_id, facility_name, quantity, status) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sisis", $facility_code, $room_id, $facility_name, $quantity, $status);

if ($stmt->execute()) {
    // Thông báo đã thêm cơ sở vật chất thành công
    $message = "Đã thêm cơ sở vật chất mới: {$facility_name} (Mã: {$facility_code}) vào phòng.";
    
    // Lưu thông báo cho các sinh viên trong phòng đó
    // Giả sử rằng bảng Users có trường username trùng với student_code trong Students.
    $sqlNotif = "SELECT u.user_id 
                 FROM Users u 
                 JOIN Students s ON u.username = s.student_code 
                 WHERE s.room_id = ?";
    $stmtNotif = $conn->prepare($sqlNotif);
    $stmtNotif->bind_param("i", $room_id);
    $stmtNotif->execute();
    $resultNotif = $stmtNotif->get_result();
    
    // Chuẩn bị câu lệnh INSERT thông báo
    $sqlInsertNotif = "INSERT INTO Notifications (user_id, title, message, notification_type) VALUES (?, ?, ?, ?)";
    $stmtInsertNotif = $conn->prepare($sqlInsertNotif);
    
    $title = "Cập nhật cơ sở vật chất";
    $notification_type = "maintenance"; // Bạn có thể điều chỉnh loại thông báo nếu cần
    
    while ($row = $resultNotif->fetch_assoc()) {
        $user_id = $row['user_id'];
        $stmtInsertNotif->bind_param("isss", $user_id, $title, $message, $notification_type);
        $stmtInsertNotif->execute();
    }
    
    $stmtInsertNotif->close();
    $stmtNotif->close();
    
    echo json_encode(['success' => true, 'message' => 'Thêm cơ sở vật chất thành công và thông báo đã được gửi đến sinh viên']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm cơ sở vật chất']);
}

$stmt->close();
$conn->close();
?>
