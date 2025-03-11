<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Chưa đăng nhập"]);
    exit();
}
$myId = $_SESSION['user_id'];

if (!isset($_GET['other_id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Thiếu tham số other_id"]);
    exit();
}
$otherId = intval($_GET['other_id']);

// Truy vấn tin nhắn 1-1 giữa người dùng hiện tại và đối phương
// Sử dụng trường created_at thay cho sent_at và lấy thêm conversation_id nếu cần
$sql = "SELECT message_id, conversation_id, sender_id, receiver_id, content, created_at
        FROM Messages
        WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
          AND is_deleted = 0
        ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $myId, $otherId, $otherId, $myId);
$stmt->execute();
$result = $stmt->get_result();
$messages = [];
while ($row = $result->fetch_assoc()){
    $messages[] = $row;
}
$stmt->close();
$conn->close();

echo json_encode(["success" => true, "messages" => $messages]);
?>
