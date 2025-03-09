<?php
require __DIR__ . '/../../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class NotificationServer implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        // Lưu tất cả các client kết nối
        $this->clients = new \SplObjectStorage;
        echo "WebSocket server started\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        // Thêm connection mới vào danh sách
        $this->clients->attach($conn);
        echo "New connection: ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // Phân tích message từ client (ví dụ: yêu cầu đánh dấu đã đọc)
        echo "Received message: $msg\n";
        // Bạn có thể xử lý message theo nội dung và gửi đến các client khác nếu cần.
    }

    public function onClose(ConnectionInterface $conn) {
        // Khi kết nối đóng, loại bỏ khỏi danh sách
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    // Hàm gửi thông báo tới tất cả client kết nối
    public function sendNotification($notificationData) {
        foreach ($this->clients as $client) {
            $client->send(json_encode($notificationData));
        }
    }
}

// Khởi tạo server Ratchet
$port = 8080;
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new NotificationServer()
        )
    ),
    $port
);

echo "Server running on port $port\n";
$server->run();
