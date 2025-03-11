<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    die("Chỉ sinh viên mới truy cập.");
}
$studentId = $_SESSION['user_id'];
$adminId = 1;  // Giả sử admin có user_id = 1

// Lấy username của sinh viên (để hiển thị trong header, nếu muốn)
include '../config/db_connect.php';
$sql = "SELECT username FROM Users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$studentInfo = ['username' => '']; // Giá trị mặc định
if ($result->num_rows > 0) {
    $studentInfo = $result->fetch_assoc();
}
$stmt->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chat với Admin</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f0f2f5;
    }

    .chat-container {
        width: 90%;
        max-width: 800px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        margin: 0 auto;
        margin-top: 100px; 
        height: 90vh; /* Chiếm 90% chiều cao viewport */
    }

    .chat-header {
        background-color: #f0f0f0;
        padding: 15px;
        display: flex;
        align-items: center;
        border-bottom: 1px solid #ddd;
    }
        .chat-header-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        overflow: hidden;
        margin-right: 15px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .chat-header-avatar img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover; /* Căn giữa và cắt ảnh nếu cần */
    }
    .chat-header-avatar i {
       font-size: 20px;
       color:#888;
    }

    .chat-header-info {
        flex: 1; /* Chiếm phần còn lại của header */
    }

    .chat-header-title {
        font-weight: bold;
        margin-bottom: 2px; /* Khoảng cách với dòng trạng thái */
    }

    .chat-header-status {
        font-size: 0.9em;
        color: #777;
    }

    .chat-box {
        flex: 1; /* Chiếm phần lớn diện tích */
        overflow-y: auto;
        padding: 20px;
        background-color: #e5ddd5;
        /* background-image: url('../../assets/img/chat-background.png'); */
        background-repeat: repeat;
    }

    .chat-message {
        margin-bottom: 15px;
        clear: both;
        padding: 10px 15px;
        border-radius: 18px;
        max-width: 70%;
        word-wrap: break-word;
        overflow-wrap: break-word;
        hyphens: auto;
        position: relative; /* Để định vị thời gian */

    }
    .chat-message p {
      white-space: pre-wrap;
      word-break: break-word;
    }

    .chat-message.right {
        float: right;
        background-color: #dcf8c6; /* Màu nền tin nhắn của sinh viên */
        text-align: right;
    }

    .chat-message.left {
        float: left;
        background-color: #fff;
        text-align: left;
    }

    .chat-message small {
        display: block; /* Hiển thị thời gian trên một dòng riêng */
        font-size: 0.8em;
        color: #888;
        margin-top: 5px;
    }
        .chat-message.right small{
          text-align: right;
        }
        .chat-message.left small{
           text-align: left;
        }


    .chat-input {
        display: flex;
        padding: 10px 15px;
        background-color: #f0f0f0;
        border-top: 1px solid #ddd;
    }

    .chat-input textarea {
        flex: 1;
        padding: 10px;
        border: none;
        border-radius: 20px;
        resize: none; /* Không cho phép thay đổi kích thước */
        outline: none;
        margin-right: 10px;
    }

    .chat-input button {
        background-color: #128c7e;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 45px;
        height: 45px;
        font-size: 20px;
        cursor: pointer;
        display: flex; /* Để căn giữa icon */
        justify-content: center;
        align-items: center;
        transition: background-color 0.2s;
    }
     .chat-input button:hover {
        background-color: #075e54;
    }
    /* Responsive */
    @media (max-width: 768px){
      .chat-container{
         width: 100%;
         height: 100vh;
         border-radius: 0;
      }
      .chat-message {
        max-width: 90%;
      }
    }
    </style>
</head>
<body>
<?php include 'layout/header.php'; ?>
<?php include 'layout/sidebar.php'; ?>

<div class="chat-container">
     <div class="chat-header">
        <div class="chat-header-avatar">
            <!-- Thay thế bằng ảnh avatar của admin, hoặc icon nếu không có -->
           <img src="../../assets/img/default-avatar.png" alt="Admin Avatar">
        </div>
        <div class="chat-header-info">
            <div class="chat-header-title">Chat với Admin</div>
            <div class="chat-header-status">Đang hoạt động</div> <!-- Cập nhật trạng thái bằng JS nếu có -->
        </div>
    </div>
    <div class="chat-box" id="chatBox">
        <!-- Tin nhắn -->
    </div>
    <div class="chat-input">
        <textarea id="txtMsg" placeholder="Nhập tin nhắn..."></textarea>
        <button onclick="sendMsg()"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script>
let ws;
let studentId = <?php echo $studentId; ?>;
let adminId = <?php echo $adminId; ?>;
let chatBox = document.getElementById('chatBox');

// Hàm load lịch sử chat
function loadChatHistory(){
    fetch("../chat/chat_history.php?other_id=" + adminId)
      .then(response => response.json())
      .then(data => {
         if(data.success){
             chatBox.innerHTML = ""; // Xóa tin nhắn cũ
             data.messages.forEach(function(msg){
                  appendMessage(msg);
             });
         } else {
             console.error("Lỗi tải lịch sử chat: ", data.message);
         }
      })
      .catch(error => console.error("Error: ", error));
}

// Khởi tạo WebSocket
function initWS(){
    ws = new WebSocket("ws://localhost:8080");
    ws.onopen = function(){
        console.log("WS connected");
        ws.send(JSON.stringify({ type:"AUTH", user_id: studentId }));
    };
    ws.onmessage = function(event){
        let data = JSON.parse(event.data);
        if(data.type === "CHAT"){
            if((data.sender_id === adminId && data.receiver_id === studentId) ||
               (data.sender_id === studentId && data.receiver_id === adminId)){
                appendMessage(data);
            }
        }
    };
    ws.onerror = function(err){
        console.error("WS error:", err);
    };
    ws.onclose = function(){
        console.log("WS closed");
         setTimeout(initWS, 5000); // Thử kết nối lại sau 5 giây
    };
}

// Hàm gửi tin nhắn
function sendMsg(){
    let content = document.getElementById('txtMsg').value.trim();
    if(!content) return;
    ws.send(JSON.stringify({
        type:"CHAT",
        receiver_id: adminId,
        content: content
    }));
    document.getElementById('txtMsg').value = ''; // Xóa nội dung ô nhập
}

// Hàm hiển thị tin nhắn
function appendMessage(m){
    let div = document.createElement('div');
    let align = (m.sender_id === studentId) ? 'right' : 'left';
    div.classList.add("chat-message", align);

    // Tạo phần tử <p> cho nội dung tin nhắn
    let contentP = document.createElement('p');
    contentP.textContent = m.content;
    div.appendChild(contentP);

    // Tạo phần tử <small> cho thời gian
    let timeSmall = document.createElement('small');
    timeSmall.textContent = m.created_at;
    div.appendChild(timeSmall);

    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
}

// Khởi tạo WebSocket và load lịch sử chat khi trang được tải
initWS();
loadChatHistory();

// Bắt sự kiện nhấn Enter để gửi tin nhắn
document.getElementById('txtMsg').addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        sendMsg();
    }
});
</script>
</body>
</html>