<?php
header('Content-Type: application/json');
include 'db_connect.php';

$building = $_GET['building'];

$sql = "SELECT DISTINCT floor FROM Rooms WHERE building = ? ORDER BY floor";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $building);
$stmt->execute();
$result = $stmt->get_result();

$floors = [];
while ($row = $result->fetch_assoc()) {
    $floors[] = $row['floor'];
}

echo json_encode(['floors' => $floors]);

$stmt->close();
$conn->close();
?>
