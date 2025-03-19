<?php
require __DIR__ . '/../../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
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
        // Sử dụng kết nối persistent
        $this->pdo = new PDO($dsn, "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => true
        ]);
        echo "WebSocket server started\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "onOpen triggered, resourceId={$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "onMessage triggered from #{$from->resourceId}: $msg\n";
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

                // Lấy hoặc tạo conversation_id cho cuộc chat 1-1
                $convId = $this->getOrCreateConversationId($senderId, $receiverId);

                // Lưu tin nhắn vào DB
                $msgId = $this->saveMessage($convId, $senderId, $receiverId, $content);
                // Chuẩn bị gói tin chat để gửi
                $chatMsg = [
                    'type'            => 'CHAT',
                    'conversation_id' => $convId,
                    'sender_id'       => $senderId,
                    'receiver_id'     => $receiverId,
                    'content'         => $content,
                    'message_type'    => 'text',
                    'is_read'         => 0,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'message_id'      => $msgId
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

    // Hàm lấy hoặc tạo conversation_id cho chat 1-1
    protected function getOrCreateConversationId($sender, $receiver) {
        // Sử dụng công thức: conversation_key = "Chat: min(sender, receiver)-max(sender, receiver)"
        $min = min($sender, $receiver);
        $max = max($sender, $receiver);
        $title = "Chat: {$min}-{$max}";
        
        // Kiểm tra nếu cuộc trò chuyện đã tồn tại
        $sql = "SELECT conversation_id FROM Conversations WHERE title = :title LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':title' => $title]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row['conversation_id'];
        } else {
            // Nếu chưa tồn tại, tạo mới cuộc trò chuyện
            $sqlInsert = "INSERT INTO Conversations (title, is_group) VALUES (:title, 0)";
            $stmtInsert = $this->pdo->prepare($sqlInsert);
            $stmtInsert->execute([':title' => $title]);
            return $this->pdo->lastInsertId();
        }
    }

    // Lưu tin nhắn vào DB, trả về message_id
    protected function saveMessage($convId, $sender, $receiver, $content) {
        $sql = "INSERT INTO Messages 
                (conversation_id, sender_id, receiver_id, content, message_type, is_read)
                VALUES (:conv, :s, :r, :c, 'text', 0)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':conv' => $convId,
            ':s'    => $sender,
            ':r'    => $receiver,
            ':c'    => $content
        ]);
        return $this->pdo->lastInsertId();
    }

    // (Tùy chọn) Hàm gửi notification broadcast
    public function sendNotification($data) {
        $msg = json_encode([
            'type' => 'NOTIFICATION',
            'data' => $data
        ]);
        foreach ($this->clients as $client) {
            $client->send($msg);
        }
    }
}

$port = 8080;
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
