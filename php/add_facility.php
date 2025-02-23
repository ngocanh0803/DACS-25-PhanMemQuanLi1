<?php
// add_facility.php
header('Content-Type: application/json');
include 'db_connect.php';

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
    echo json_encode(['success' => true, 'message' => 'Thêm cơ sở vật chất thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm cơ sở vật chất']);
}

$stmt->close();
$conn->close();
?>
