<?php
include 'db_connect.php';

// Kiểm tra tham số `building` và `floor`
$building = isset($_GET['building']) ? $_GET['building'] : '';
$floor = isset($_GET['floor']) ? $_GET['floor'] : '';

if (!$building || !$floor) {
    echo json_encode(['error' => 'Building and floor parameters are required.']);
    exit;
}

// Truy vấn lấy danh sách phòng dựa trên `building` và `floor`
$sql = "SELECT room_id, room_number, capacity, status 
        FROM Rooms 
        WHERE building = ? AND floor = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $building, $floor);
$stmt->execute();
$result = $stmt->get_result();

// Chuẩn bị dữ liệu phòng dưới dạng JSON
$rooms = [];
while ($row = $result->fetch_assoc()) {
    $rooms[] = [
        'room_id' => $row['room_id'],
        'room_number' => $row['room_number'],
        'capacity' => $row['capacity'],
        'status' => $row['status']
    ];
}

// Trả về dữ liệu JSON
echo json_encode($rooms);
