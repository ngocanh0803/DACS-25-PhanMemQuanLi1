<?php if(session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Ký Túc Xá</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/management_schedule.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>  <!-- Include phần header -->
    <div class="container">
        <?php include 'layout/menu.php'; ?>  <!-- Include phần menu -->
        <main class="content">
            <!-- <h1>Chào mừng đến với phần mềm quản lý ký túc xá</h1> -->
            <!-- <img src="../../assets/img/welcome2.webp" alt="Welcome" class="welcome-image"> -->
            <?php include 'management_schedule.php'; ?>  <!-- Include phần menu -->
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/search.js"></script>
</body>
</html>
