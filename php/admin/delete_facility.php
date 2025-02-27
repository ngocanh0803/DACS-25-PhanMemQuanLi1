<?php
// delete_facility.php
header('Content-Type: application/json');
include '../config/db_connect.php';

$facility_id = intval($_POST['facility_id']);

$sql = "DELETE FROM Facilities WHERE facility_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $facility_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Xóa cơ sở vật chất thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa cơ sở vật chất']);
}

$stmt->close();
$conn->close();
?>
