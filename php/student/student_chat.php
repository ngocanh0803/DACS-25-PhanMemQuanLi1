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
    <link rel="stylesheet" href="../../assets/css/chat_student.css">
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