<!-- chatbox.php -->
<style>
/* Toàn bộ CSS của chatbox (như trong code trên) */
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
 .chatbox-icon {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: #007bff; /* Màu xanh */
        color: #fff;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 24px;
        cursor: pointer;
        z-index: 1000; /* Đảm bảo nổi lên trên */
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        transition: background-color 0.3s ease;
    }

    .chatbox-icon:hover {
        background-color: #0056b3; /* Màu xanh đậm hơn khi hover */
    }

    .chatbox-popup {
        position: fixed;
        bottom: 80px; /* Cách icon một khoảng */
        right: 20px;
        width: 400px; /* Chiều rộng popup */
        height: 500px; /* Chiều cao popup */
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        z-index: 1001; /* Cao hơn icon */
        display: none; /* Ẩn ban đầu */
        overflow: hidden; /* Để scrollbar hoạt động */
    }
      .chatbox-popup.active {
          display: flex; /* Hiện khi active */
    }
    /* Thêm CSS để ẩn phần chat-container khi ở trong popup */
    .chatbox-popup .chat-container {
        margin-top: 0; /* Loại bỏ margin top */
        height: 100%; /* Chiếm toàn bộ chiều cao của popup */
        width: 100%;

    }
    /* Nút đóng popup */
    .chatbox-close {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 20px;
        color: #888;
        cursor: pointer;
        z-index: 1002; /* Cao nhất */
    }
</style>

<!-- Chatbox Icon -->
<div class="chatbox-icon">
    <i class="fas fa-comment-dots"></i>
</div>

<!-- Chatbox Popup -->
<div class="chatbox-popup">
    <span class="chatbox-close">×</span>
    <div class="chat-container">
        <div class="chat-header">
            <div class="chat-header-avatar">
                <img src="../../assets/img/default-avatar.png" alt="Admin Avatar">
            </div>
            <div class="chat-header-info">
                <div class="chat-header-title">Chat với Admin</div>
                <div class="chat-header-status">Đang hoạt động</div>
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
</div>

<script>
// Toàn bộ JavaScript của chatbox (như trong code trên)
let ws;
let studentId = <?php echo $studentId; ?>;
let adminId = <?php echo $adminId; ?>;
let chatBox = document.getElementById('chatBox');
let chatboxPopup = document.querySelector('.chatbox-popup');
let chatboxIcon = document.querySelector('.chatbox-icon');
let closeBtn = document.querySelector('.chatbox-close');


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

// Mở/đóng popup
chatboxIcon.addEventListener('click', function() {
    chatboxPopup.classList.toggle('active');
    if (chatboxPopup.classList.contains('active')) {
        loadChatHistory(); // Tải tin nhắn khi mở popup
    }
});

closeBtn.addEventListener('click', function() {
    chatboxPopup.classList.remove('active'); // Đóng popup
});

// Đóng popup khi click ra bên ngoài
document.addEventListener('click', function(event) {
    if (!chatboxPopup.contains(event.target) && !chatboxIcon.contains(event.target)) {
        chatboxPopup.classList.remove('active');
    }
});

// Bắt sự kiện nhấn Enter để gửi tin nhắn
document.getElementById('txtMsg').addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        sendMsg();
    }
});
 // Khởi tạo WebSocket ngay khi trang load
 initWS();
</script>