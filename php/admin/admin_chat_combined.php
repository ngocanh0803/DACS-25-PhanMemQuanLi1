<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Chỉ admin mới truy cập.");
}
$adminId = $_SESSION['user_id'];

include '../config/db_connect.php';

// Lấy danh sách người dùng (không cần avatar)
$sqlUsers = "SELECT user_id, username, role FROM Users WHERE user_id != ?";
$stmtUsers = $conn->prepare($sqlUsers);
$stmtUsers->bind_param("i", $adminId);
$stmtUsers->execute();
$resultUsers = $stmtUsers->get_result();
$users = [];
while ($row = $resultUsers->fetch_assoc()) {
    $users[] = $row;
}
$stmtUsers->close();

// Giá trị mặc định cho receiver (tránh lỗi khi chưa chọn người nhận)
$receiver_id = 0;
$receiverInfo = ['username' => ''];

// Nếu có receiver_id trong URL, lấy thông tin người nhận
if (isset($_GET['receiver_id'])) {
    $receiver_id = intval($_GET['receiver_id']);
    $sqlReceiver = "SELECT username FROM Users WHERE user_id = ?";
    $stmtReceiver = $conn->prepare($sqlReceiver);
    $stmtReceiver->bind_param("i", $receiver_id);
    $stmtReceiver->execute();
    $resultReceiver = $stmtReceiver->get_result();
    if ($resultReceiver->num_rows > 0) {
        $receiverInfo = $resultReceiver->fetch_assoc();
    }
    $stmtReceiver->close();
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chat Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f0f2f5;
        /* margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh; */
    }

    .chat-app-container {
        display: flex; /* Sử dụng flexbox */
        width: 100%; /* Chiều rộng */
        /* max-width: 1200px; Giới hạn chiều rộng tối đa */
        height: 90vh; /* Chiều cao */
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .user-list-container {
        width: 30%; /* Chiều rộng danh sách user */
        border-right: 1px solid #ddd;
        overflow-y: auto; /* Cuộn dọc */
        display: flex;
        flex-direction: column; /* Xếp các phần tử theo chiều dọc */
    }

    .search-bar {
        padding: 10px 15px;
        border-bottom: 1px solid #ddd;
        display: flex;
        align-items: center;
    }

    .search-input {
        flex: 1;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 20px;
        outline: none;
    }

    .search-icon {
        margin-right: 8px;
        color: #aaa;
    }

    .user-list {
      flex: 1; /* Phần còn lại của user-list-container */
      overflow-y: auto;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .user-item {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        border-bottom: 1px solid #ddd;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .user-item:hover {
        background-color: #f0f0f0;
    }
     .user-item.active {
        background-color: #e0e0e0;  /* Màu nền khi user được chọn */
    }

    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        overflow: hidden;
        margin-right: 15px;
        display:flex;
        justify-content: center;
        align-items: center;
    }
    .user-avatar img{
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
    }

    .user-info {
        flex: 1;
    }

    .user-name {
        font-weight: bold;
        margin-bottom: 3px;
    }

    .user-role {
        font-size: 0.9em;
        color: #777;
    }

    /* Phần chat container */
    .chat-container {
        width: 70%; /* Chiều rộng khung chat */
        display: flex;
        flex-direction: column;
    }

    .chat-header {
        background-color: #f0f0f0;
        padding: 15px;
        display: flex;
        align-items: center;
        border-bottom: 1px solid #ddd;
        width: 100%;
    }

    .chat-header-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      overflow: hidden;
      margin-right: 10px;
       display: flex;
       justify-content: center;
       align-items: center;
    }
    .chat-header-avatar img {
       max-width: 100%;
       max-height: 100%;
       object-fit: cover;
    }

    .chat-header-info {
        flex: 1;
    }

    .chat-header-name {
        font-weight: bold;
    }

    .chat-header-status {
        font-size: 0.9em;
        color: #777;
    }

    .chat-messages {
        /* flex: 1; */
        /* width: 865px; */
        box-sizing: content-box;
        box-sizing: border-box;
        height: 100%;
        overflow-y: auto;
        padding: 20px;
       background-color: #e5ddd5; /* Màu nền WhatsApp */
        /* background-image: url('../../assets/img/chat-background.png');  */
        background-repeat: repeat;
    }

    .message {
        margin-bottom: 15px;
        clear: both;
        padding: 10px 15px;
        border-radius: 18px;
        max-width: 70%;
        word-wrap: break-word;
        overflow-wrap: break-word;
        hyphens: auto;
    }

    .message p {
        white-space: pre-wrap;
        word-break: break-word;
    }

    .message-admin {
        float: right;
        background-color: #dcf8c6;
        text-align: right;
    }

    .message-user {
        float: left;
        background-color: #fff;
        text-align: left;
    }

    .message-time {
        display: block;
        font-size: 0.8em;
        color: #888;
        margin-top: 5px;
    }
    .message-admin .message-time{
        text-align: right;
    }
    .message-user .message-time{
       text-align: left;
    }

    .chat-input-area {
        display: flex;
        padding: 10px 15px;
        border-top: 1px solid #ddd;
        background-color: #f0f0f0;
    }

    .chat-input {
        flex: 1;
        padding: 10px;
        border: none;
        border-radius: 20px;
        outline: none;
        margin-right: 10px;
    }

    .chat-send-button {
        background-color: #128c7e;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 45px;
        height: 45px;
        font-size: 20px;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: background-color 0.2s;
    }
    .chat-send-button:hover {
        background-color: #075e54;
    }
     /* Ẩn khung chat ban đầu */
    .chat-container {
        display: none;
        display: flex;
        box-sizing: border-box;
    }
    #chatContainer{
        max-width: 1200px;
        width: 100%;
    }
    /* Responsive */
    @media (max-width: 768px) {
        .chat-app-container {
            flex-direction: column; /* Chuyển thành cột trên mobile */
            height: auto; /* Chiều cao tự động */
            width: 100%; /* Full chiều rộng */
            border-radius: 0; /* Bỏ bo tròn */

        }

        .user-list-container {
            width: 100%; /* Full chiều rộng */
            border-right: none; /* Loại bỏ border */
             height: auto; /* Chiều cao tự động */
             max-height: 300px; /* Giới hạn chiều cao */
             overflow-y: auto; /* Vẫn cuộn nếu cần */
        }

        .chat-container {
            width: 100%; /* Full chiều rộng */
             /* Hiển thị khung chat mặc định trên mobile */
            display: flex;
            border-top: 1px solid #ddd;
        }
        .message {
            max-width: 90%; /* Tin nhắn rộng hơn trên mobile */
        }
    }
    </style>
</head>
<body>
<?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="chat-app-container">
            <!-- Danh sách người dùng -->
                <div class="user-list-container">
                    <div class="search-bar">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Tìm kiếm người dùng...">
                    </div>
                    <ul class="user-list">
                        <?php foreach ($users as $u): ?>
                        <li class="user-item" data-user-id="<?php echo $u['user_id']; ?>">
                            <div class="user-avatar">
                                <img src="../../assets/img/default-avatar.png" alt="Default Avatar">
                            </div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($u['username']); ?></div>
                                <div class="user-role"><?php echo htmlspecialchars($u['role']); ?></div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Khung chat -->
                <div class="chat-container" id="chatContainer">
                    <div class="chat-header">
                        <div class="chat-header-avatar">
                            <img src="../../assets/img/default-avatar.png" alt="Default Avatar">
                        </div>
                        <div class="chat-header-info">
                            <div class="chat-header-name" id="chatHeaderName"><?php echo htmlspecialchars($receiverInfo['username']); ?></div>
                            <div class="chat-header-status">Đang hoạt động</div>
                        </div>
                    </div>
                    <div class="chat-messages" id="chatBox">
                        <!-- Tin nhắn -->
                    </div>
                    <div class="chat-input-area">
                        <input type="text" class="chat-input" id="txtMsg" placeholder="Nhập tin nhắn...">
                        <button class="chat-send-button" onclick="sendMsg()"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </div>
            </div>
        </main>
  </div>

<?php include 'layout/js.php'; ?>
<script>
    let ws;
    let adminId = <?php echo $adminId; ?>;
    let currentReceiverId = <?php echo $receiver_id; ?>; // Sử dụng biến toàn cục
    let chatBox = document.getElementById('chatBox');
    let chatContainer = document.getElementById('chatContainer'); // Lấy element khung chat
    let chatHeaderName = document.getElementById('chatHeaderName');


    // Hàm load lịch sử chat
    function loadChatHistory(receiverId) {
        fetch("../chat/chat_history.php?other_id=" + receiverId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    chatBox.innerHTML = ""; // Xóa tin nhắn cũ
                    data.messages.forEach(function(msg) {
                        appendMessage(msg);
                    });
                } else {
                    console.error("Lỗi tải lịch sử chat: ", data.message);
                }
            })
            .catch(error => console.error("Error: ", error));
    }

    // Khởi tạo WebSocket
    function initWS() {
        ws = new WebSocket("ws://localhost:8080");
        ws.onopen = function() {
            console.log("WS connected");
            ws.send(JSON.stringify({ type: "AUTH", user_id: adminId }));
        };
        ws.onmessage = function(event) {
            let data = JSON.parse(event.data);
            if (data.type === "CHAT") {
                // Kiểm tra tin nhắn có thuộc cuộc trò chuyện hiện tại không
                if ((data.sender_id === currentReceiverId && data.receiver_id === adminId) ||
                    (data.sender_id === adminId && data.receiver_id === currentReceiverId)) {
                    appendMessage(data);
                }
            }
        };
        ws.onerror = function(err) {
            console.error("WS error:", err);
        };
        ws.onclose = function() {
            console.log("WS closed");
            setTimeout(initWS, 5000); // Thử kết nối lại
        };
    }

    // Gửi tin nhắn
    function sendMsg() {
        let content = document.getElementById('txtMsg').value.trim();
        if (!content || !currentReceiverId) return; // Không gửi nếu tin nhắn trống hoặc chưa chọn người nhận
        let packet = {
            type: "CHAT",
            receiver_id: currentReceiverId,
            content: content
        };
        ws.send(JSON.stringify(packet));
        document.getElementById('txtMsg').value = '';
    }

    // Hiển thị tin nhắn
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

    // Xử lý sự kiện click vào user
    $(document).ready(function() {
      initWS(); // Khởi tạo WebSocket

      //Sự kiện click để hiển thị khung chat
      $('.user-item').click(function() {
          currentReceiverId = $(this).data('user-id'); // Cập nhật receiver ID
          let userName = $(this).find('.user-name').text(); // Lấy tên người nhận

           // Cập nhật header
          chatHeaderName.textContent = userName;
          $('.user-item').removeClass('active');
          $(this).addClass('active');

          chatContainer.style.display = 'flex'; // Hiện khung chat
          loadChatHistory(currentReceiverId);    // Tải tin nhắn

      });

      // Tìm kiếm
      $('.search-input').on('keyup', function() {
          let value = $(this).val().toLowerCase();
          $('.user-item').filter(function() {
              let userName = $(this).find('.user-name').text().toLowerCase();
              let userRole = $(this).find('.user-role').text().toLowerCase();
              $(this).toggle(userName.indexOf(value) > -1 || userRole.indexOf(value) > -1);
          });
      });

       // Gửi tin nhắn khi nhấn Enter
      document.getElementById('txtMsg').addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            sendMsg();
        }
    });
  });
</script>
</body>
</html>