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
    <style>
    /* ... (CSS giữ nguyên như trước) ... */
       /* Giao diện chat room giống Telegram/Messenger/WhatsApp */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #e5ddd5; /* Màu nền WhatsApp */
/* Đảm bảo full chiều cao */
    }
    .chat-container {
        width: 90%;
        max-width: 800px; /* Giới hạn độ rộng */
        background: #fff;
        border-radius: 10px;
        overflow: hidden; /* Ẩn các phần tử tràn */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        display: flex; /* Dùng flexbox */
        flex-direction: column; /* Xếp các phần tử theo chiều dọc */
        height: 90vh; /* Chiều cao 90% viewport */


    }

    .chat-header {
        background-color: #f0f0f0;
        padding: 15px;
        display: flex; /* Flexbox */
        align-items: center; /* Căn giữa theo chiều dọc */
        border-bottom: 1px solid #ddd;

    }

    .chat-header-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        overflow: hidden;
        margin-right: 15px;
        display: flex; /* Dùng flex cho avatar */
        justify-content: center;
        align-items: center;
    }
    .chat-header-avatar img {
         max-width: 100%;
         max-height: 100%;
         object-fit: cover;
    }
    .chat-header-avatar i{
       font-size: 20px;
       color: #888;
    }

    .chat-header-info {
        flex: 1; /* Chiếm phần còn lại */
    }

    .chat-header-name {
        font-weight: bold;
    }

    .chat-header-status {
        font-size: 0.9em;
        color: #777;
         /* Thêm "Đang hoạt động" - cập nhật bằng JS nếu có trạng thái online */
    }

    .chat-messages {
        flex: 1; /* Chiếm phần lớn diện tích */
        overflow-y: auto; /* Cuộn khi tin nhắn dài */
        padding: 20px;
        background-image: url('path/to/your/chat-background.png'); /* Thay bằng ảnh nền chat của bạn */
        background-repeat: repeat; /* Lặp lại ảnh nền */

    }

    .message {
        margin-bottom: 15px;
        clear: both;
        padding: 10px 15px;
        border-radius: 18px;
        max-width: 70%; /* Giới hạn độ rộng */
        word-wrap: break-word; /* Cho phép ngắt từ */
        overflow-wrap: break-word; /* Xử lý ngắt từ (chuẩn hơn) */
        hyphens: auto; /* Tự động thêm dấu gạch nối (nếu cần) */
    }
    /* Thêm các thuộc tính sau để xuống dòng */
    .message p {
      white-space: pre-wrap; /* Xử lý xuống dòng, giữ khoảng trắng */
      word-break: break-word; /* Ngắt từ để tránh tràn */
    }
    .message-admin {
        float: right; /* Tin nhắn của admin bên phải */
        background-color: #dcf8c6; /* Màu nền tin nhắn admin */
         text-align: right; /* Canh phải nội dung */

    }

    .message-user {
        float: left; /* Tin nhắn của user bên trái */
        background-color: #fff; /* Màu nền tin nhắn user */
        text-align: left;
    }

    .message-time {
        display: block; /* Thời gian xuống dòng */
        font-size: 0.8em;
        color: #888;
        margin-top: 5px; /* Khoảng cách với nội dung tin nhắn */
    }
     /* Căn phải thời gian cho tin nhắn admin */
    .message-admin .message-time {
         text-align: right;
    }
      /* Căn trái thời gian cho tin nhắn user */
    .message-user .message-time{
       text-align: left;
    }

    .chat-input-area {
        display: flex; /* Flexbox */
        padding: 10px 15px;
        border-top: 1px solid #ddd;
        background-color: #f0f0f0;
    }

    .chat-input {
        flex: 1; /* Chiếm phần còn lại */
        padding: 10px;
        border: none; /* Loại bỏ border */
        border-radius: 20px; /* Bo tròn */
        outline: none; /* Loại bỏ viền khi focus */
        margin-right: 10px;

    }

    .chat-send-button {
         background-color: #128c7e; /* Màu xanh WhatsApp */
        color: #fff;
        border: none;
        border-radius: 50%; /* Hình tròn */
        width: 45px;
        height: 45px;
        font-size: 20px;
        cursor: pointer;
         display: flex; /* Dùng flex để căn giữa icon */
        justify-content: center;
        align-items: center;
        /* transition: background-color: 0.2s; */
    }
    .chat-send-button:hover{
         background-color: #075e54; /* Màu đậm hơn khi hover */
    }
    .content {
         width: 100%;
         padding: 0; /* Loại bỏ padding mặc định của .content */
    }

    /* Thêm một số responsive */
    @media (max-width: 768px) {
      .chat-container {
         width: 100%; /* Full chiều rộng trên mobile */
         height: 100vh; /* Full chiều cao */
         border-radius: 0; /* Loại bỏ bo tròn */

      }
      .message {
        max-width: 90%; /* Tin nhắn chiếm phần lớn hơn trên mobile */
      }
    }


    </style>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS (optional) -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script src="../../assets/js/main.js"></script>
<script src="../../assets/js/search.js"></script>
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