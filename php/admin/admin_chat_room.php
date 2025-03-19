<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Chỉ admin mới truy cập.");
}
$adminId = $_SESSION['user_id'];
$receiver_id = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : 0;
if ($receiver_id === 0) {
    die("Receiver không hợp lệ.");
}

// Lấy thông tin người nhận (chỉ cần username)
include '../config/db_connect.php';
$sql = "SELECT username FROM Users WHERE user_id = ?";  // Chỉ lấy username
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $receiver_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Không tìm thấy người dùng.");
}
$receiverInfo = $result->fetch_assoc();
$stmt->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chat với <?php echo htmlspecialchars($receiverInfo['username']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Bootstrap CSS -->
    <!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"> -->
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/admin_chat_room.css">
</head>
<body>
<?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="chat-container">
                <div class="chat-header">
                    <div class="chat-header-avatar">
                        <!-- Hiển thị ảnh mặc định -->
                        <img src="../../assets/img/default-avatar.png" alt="Default Avatar">
                    </div>
                    <div class="chat-header-info">
                        <div class="chat-header-name"><?php echo htmlspecialchars($receiverInfo['username']); ?></div>
                        <div class="chat-header-status">Đang hoạt động</div> <!-- Thêm trạng thái -->
                    </div>

                </div>
                <div class="chat-messages" id="chatBox">
                    <!-- Tin nhắn sẽ được đổ vào đây -->
                </div>
                <div class="chat-input-area">
                    <input type="text" class="chat-input" id="txtMsg" placeholder="Nhập tin nhắn...">
                    <button class="chat-send-button" onclick="sendMsg()"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </main>
    </div>

<!-- Bootstrap JS (optional) -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<?php include 'layout/js.php'; ?>
<script>
// ... (JavaScript giữ nguyên như trước) ...
let ws;
let adminId = <?php echo $adminId; ?>;
let receiverId = <?php echo $receiver_id; ?>;
let chatBox = document.getElementById('chatBox');

// Hàm load lịch sử chat
function loadChatHistory(){
    fetch("../chat/chat_history.php?other_id=" + receiverId)
      .then(response => response.json())
      .then(data => {
         if(data.success){
             chatBox.innerHTML = ""; // Xóa nội dung cũ
             data.messages.forEach(function(msg){
                  appendMessage(msg);
             });
         } else {
             console.error("Lỗi tải lịch sử chat: ", data.message);
         }
      })
      .catch(error => console.error("Error: ", error));
}

// Khởi tạo kết nối WebSocket
function initWS(){
    ws = new WebSocket("ws://localhost:8080");
    ws.onopen = function(){
        console.log("WS connected");
        ws.send(JSON.stringify({ type: "AUTH", user_id: adminId }));
    };
    ws.onmessage = function(event){
        let data = JSON.parse(event.data);
        if(data.type === "CHAT"){
            if ((data.sender_id === receiverId && data.receiver_id === adminId) ||
                (data.sender_id === adminId && data.receiver_id === receiverId)) {
                appendMessage(data);
            }
        }
    };
    ws.onerror = function(err){
        console.error("WS error:", err);
    };
    ws.onclose = function(){
        console.log("WS closed");
        // Thử kết nối lại sau 5s (tùy chọn)
        setTimeout(initWS, 5000);
    };
}

// Hàm gửi tin nhắn
function sendMsg(){
    let content = document.getElementById('txtMsg').value.trim();
    if(!content) return;
    let packet = {
        type: "CHAT",
        receiver_id: receiverId,
        content: content
    };
    ws.send(JSON.stringify(packet));
    document.getElementById('txtMsg').value = ''; // Xóa nội dung ô input
}

// Hàm hiển thị tin nhắn
function appendMessage(msg){
     let div = document.createElement('div');
    div.classList.add('message'); // Thêm class .message

    // Xác định tin nhắn của admin hay user
    if (msg.sender_id === adminId) {
        div.classList.add('message-admin');
    } else {
        div.classList.add('message-user');
    }

    // Tạo phần tử chứa nội dung tin nhắn
     let messageContent = document.createElement('p');
     messageContent.textContent = msg.content;
      div.appendChild(messageContent);


    // Tạo phần tử chứa thời gian
    let timeSpan = document.createElement('span');
    timeSpan.classList.add('message-time');
    timeSpan.textContent = msg.created_at; // Giả sử 'created_at' có định dạng sẵn
    div.appendChild(timeSpan);

    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight; // Cuộn xuống cuối
}

// Khởi tạo WebSocket và tự động load lịch sử chat khi trang được tải
initWS();
loadChatHistory();
// Tự động reload lịch sử chat mỗi 30 giây (30000ms)
//  setInterval(loadChatHistory, 30000);

// Bắt sự kiện nhấn Enter trong ô nhập tin nhắn
 document.getElementById('txtMsg').addEventListener('keypress', function(event) {
     if (event.key === 'Enter') {
         sendMsg();
     }
 });
</script>
</body>
</html>