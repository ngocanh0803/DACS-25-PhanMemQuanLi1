<?php
if (session_start() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập và role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}
$studentId = $_SESSION['user_id'];
$adminId = 1;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang chính - Sinh viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main_student.css">
</head>
<body>

    <!-- Include Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Nội dung chính -->
    <div class="main-content">
        <!-- Include Header -->
        <?php include 'layout/header.php'; ?>

        <div class="content">

            <!-- Các card chức năng (giữ nguyên) -->
            <?php include 'layout/card-container.php'; ?>

            <!-- Phần Tin tức & Thông báo (phiên bản HTML tĩnh) -->
            <?php include 'layout/news-section.php'; ?>

        </div>
    </div>
    <!-- Include Chatbox -->
    <?php include 'chatbox.php'; ?>
</body>
</html>