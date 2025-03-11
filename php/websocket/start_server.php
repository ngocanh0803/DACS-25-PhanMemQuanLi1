<?php
require __DIR__ . '/../../vendor/autoload.php';

use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;
// use MyApp\NotificationServer;

// namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PDO;

class NotificationServer implements MessageComponentInterface {
    protected $clients;         // Danh sách kết nối (SplObjectStorage)
    protected $userConnections; // Map user_id => Connection
    protected $pdo;             // Kết nối DB PDO

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        // Kết nối MySQL bằng PDO
        $dsn = "mysql:host=localhost;dbname=dormitory_management;charset=utf8";
        $this->pdo = new PDO($dsn, "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "WebSocket server started\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection: (#{$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Received message from #{$from->resourceId}: $msg\n";
        $data = json_decode($msg, true);
        if (!$data) return;

        switch($data['type'] ?? '') {
            case 'AUTH':
                // Client gửi {type:"AUTH", user_id:xxx}
                $from->userId = $data['user_id'] ?? 0;
                $this->userConnections[$from->userId] = $from;
                echo "Connection #{$from->resourceId} authenticated as user_id={$from->userId}\n";
                break;

            case 'CHAT':
                // Client gửi {type:"CHAT", receiver_id:..., content:"..."}
                $senderId = $from->userId ?? 0;
                $receiverId = $data['receiver_id'] ?? 0;
                $content = $data['content'] ?? '';
                // Lưu tin nhắn vào DB
                $msgId = $this->saveMessage($senderId, $receiverId, $content);
                // Chuẩn bị gói tin chat
                $chatMsg = [
                    'type' => 'CHAT',
                    'sender_id' => $senderId,
                    'receiver_id' => $receiverId,
                    'content' => $content,
                    'created_at' => date('Y-m-d H:i:s'),
                    'message_id' => $msgId
                ];
                // Gửi lại cho chính người gửi
                $from->send(json_encode($chatMsg));
                // Gửi cho người nhận nếu online
                if (isset($this->userConnections[$receiverId])) {
                    $this->userConnections[$receiverId]->send(json_encode($chatMsg));
                }
                break;

            default:
                echo "Unknown message type from #{$from->resourceId}\n";
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        if (isset($conn->userId)) {
            $uId = $conn->userId;
            if (isset($this->userConnections[$uId])) {
                unset($this->userConnections[$uId]);
            }
        }
        echo "Connection #{$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    // Lưu tin nhắn vào DB, trả về message_id
    protected function saveMessage($sender, $receiver, $content) {
        $sql = "INSERT INTO Messages (sender_id, receiver_id, content) VALUES (:s, :r, :c)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':s' => $sender,
            ':r' => $receiver,
            ':c' => $content
        ]);
        return $this->pdo->lastInsertId();
    }
}

$port = 8081;
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new NotificationServer()
        )
    ),
    $port
);

echo "WebSocket server running on port $port\n";
$server->run();
