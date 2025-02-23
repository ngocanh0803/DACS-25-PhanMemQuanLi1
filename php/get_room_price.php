<?php
// get_room_price.php
include 'db_connect.php';

if (isset($_GET['room_id'])) {
    $room_id = (int)$_GET['room_id'];
    $sql = "SELECT capacity, price FROM Rooms WHERE room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result) {
        // Trả về JSON: { "price": ..., "capacity": ... }
        echo json_encode([
            'price'    => (float)$result['price'],
            'capacity' => (int)$result['capacity']
        ]);
    } else {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>
