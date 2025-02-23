<?php
// facilities_by_room.php
header('Content-Type: application/json');
include 'db_connect.php';

$room_id = $_GET['room_id'] ?? null;

if ($room_id) {
    $room_id = intval($room_id); // Chuyển đổi sang số nguyên để ngăn chặn SQL Injection

    $sql = "SELECT facility_code, facility_name, quantity, status FROM Facilities WHERE room_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $facilities = [];
        while ($row = $result->fetch_assoc()) {
            $facilities[] = [
                'facility_code' => htmlspecialchars($row['facility_code']),
                'facility_name' => htmlspecialchars($row['facility_name']),
                'quantity' => intval($row['quantity']),
                'status' => htmlspecialchars($row['status'])
            ];
        }

        echo json_encode(['facilities' => $facilities]);
        $stmt->close();
    } else {
        echo json_encode(['error' => 'Không thể chuẩn bị truy vấn.']);
    }
} else {
    echo json_encode(['error' => 'Room ID is required']);
}

$conn->close();
?>
