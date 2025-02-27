<?php
header('Content-Type: application/json');
include '../config/db_connect.php';

$building = $_GET['building'];
$floor = $_GET['floor'];

$sql = "SELECT room_id, room_number FROM Rooms WHERE building = ? AND floor = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $building, $floor);
$stmt->execute();
$result = $stmt->get_result();

$rooms = [];
while ($row = $result->fetch_assoc()) {
    $rooms[] = $row;
}

echo json_encode(['rooms' => $rooms]);

$stmt->close();
$conn->close();
?>