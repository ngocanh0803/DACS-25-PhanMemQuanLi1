<?php
header('Content-Type: application/json');
include 'db_connect.php';

if (isset($_GET['room_id'])) {
    $room_id = $_GET['room_id'];

    $sql = "SELECT * FROM Facilities WHERE room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $facilities = [];
    while ($row = $result->fetch_assoc()) {
        $facilities[] = $row;
    }

    echo json_encode(['facilities' => $facilities]);
} elseif (isset($_GET['facility_id'])) {
    $facility_id = $_GET['facility_id'];

    $sql = "SELECT * FROM Facilities WHERE facility_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $facility_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $facilities = [];
    while ($row = $result->fetch_assoc()) {
        $facilities[] = $row;
    }

    echo json_encode(['facilities' => $facilities]);
}

$stmt->close();
$conn->close();
