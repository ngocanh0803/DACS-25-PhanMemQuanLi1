<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Khởi động session nếu chưa được khởi động
}

// Kiểm tra nếu người dùng chưa đăng nhập
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

// Thông tin người dùng từ session
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Xử lý đăng xuất
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<header>
    <div class="header-container">
        <div class="left-section">
            <button id="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo_header">
                <div class="img"><img src="../assest/img/header.png" alt=""></div>
                <!-- <div class="logo_text">Quản lí kí túc xá </div> -->
            </div>
        </div>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="menu-search" placeholder="Tìm kiếm...">
            <div id="search-results" class="search-results"></div>
        </div>
        <div class="right-section">
            <div class="user-info">
                <div class="role">
                    <i class="fas fa-user-shield"></i>
                    <span>Quyền của tôi: <?php echo htmlspecialchars($role); ?></span>
                </div>
                <div class="username">
                    <i class="fas fa-user"></i>
                    <span id="username"><?php echo htmlspecialchars($username); ?></span>
                </div>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="logout" id="logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Đăng xuất
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
