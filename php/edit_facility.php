<?php
// edit_facility.php
header('Content-Type: application/json');
include 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

$facility_id = intval($data['facility_id']);
$facility_code = htmlspecialchars($data['facility_code']);
$facility_name = htmlspecialchars($data['facility_name']);
$quantity = intval($data['quantity']);
$status = htmlspecialchars($data['status']);

$sql = "UPDATE Facilities SET facility_code = ?, facility_name = ?, quantity = ?, status = ? WHERE facility_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssisi", $facility_code, $facility_name, $quantity, $status, $facility_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cập nhật cơ sở vật chất thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật cơ sở vật chất']);
}

$stmt->close();
$conn->close();
?>
