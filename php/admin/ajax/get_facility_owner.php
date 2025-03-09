<?php
// get_facility_owner.php
header('Content-Type: application/json');
include '../config/db_connect.php';

if (!isset($_GET['facility_id'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu tham số facility_id.']);
    exit();
}

$facility_id = intval($_GET['facility_id']);

// Truy vấn thông tin thiết bị, bao gồm is_student_device và owner_code (lấy từ bảng Students thông qua Equipment_Requests)
$sql = "SELECT f.facility_code, f.facility_name, f.is_student_device, s.student_code AS owner_code, s.full_name
        FROM Facilities f
        JOIN Equipment_Requests er ON f.facility_name = er.facility_name AND f.room_id = er.room_id
        JOIN Students s ON er.student_id = s.student_id
        WHERE f.facility_id = ?
        ORDER BY er.created_at DESC
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $facility_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Thiết bị không tồn tại.']);
    exit();
}

$facility = $result->fetch_assoc();
$stmt->close();

if ($facility['is_student_device'] != 1 || empty($facility['owner_code'])) {
    echo json_encode(['success' => false, 'message' => 'Thiết bị này không thuộc sở hữu của sinh viên.']);
    exit();
}

$conn->close();

echo json_encode([
    'success' => true,
    'owner_name' => $facility['full_name'],
    'owner_code' => $facility['owner_code'],
    'device_type' => 'Cá nhân',
    'facility_code' => $facility['facility_code'],
    'facility_name' => $facility['facility_name']
]);
?>
