<?php


// Vì username chính là student_code nên lấy nó từ session
$student_code = $_SESSION['username'];

// Kết nối CSDL (chỉnh sửa thông tin kết nối cho phù hợp)
include '../config/db_connect.php';

// Truy vấn: Lấy họ và full_name từ bảng Students dựa trên student_code
$sql = "SELECT SUBSTRING_INDEX(full_name, ' ', 1) AS surname, full_name 
        FROM Students 
        WHERE student_code = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $surname = $row['surname'];
    $fullName = $row['full_name'];
} 

$stmt->close();
$conn->close();
?>
<!-- header.php -->
<div class="header">
    <div class="sidebar-logo">
        <img style="width: 54%;" src="https://qldtbeta.phenikaa-uni.edu.vn/congsinhvien/logo.png" alt="Logo">
    </div>
    <div style="display: flex; text-align: center;">
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Tìm kiếm thông tin">
        </div>
        <!-- Notification bell -->
        <div class="notification-wrapper" >
            <i class="fas fa-bell" id="notification-bell" style="font-size: 24px; cursor: pointer;"></i>
            <span id="notification-count" class="badge">0</span>
            <!-- Dropdown để hiển thị danh sách thông báo (sẽ được load động bằng JS) -->
            <div id="notification-dropdown" class="dropdown">
                <!-- Thông báo sẽ được load ở đây -->
            </div>
        </div>
        <div class="user-profile">
            <img src="https://qldtbeta.phenikaa-uni.edu.vn/upload/Core/images/no-avatar.png" alt="User">
            <span><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></span>
            <div class="dropdown-menu">
                <a href="#"><i class="fas fa-key"></i> Thay đổi mật khẩu</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>
    </div>
</div>
<!-- Include file JS thông báo -->
<script src="../../../assets/js/notifications.js"></script>