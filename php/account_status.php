<?php
session_start();

// Nếu người dùng chưa đăng nhập, chuyển về trang đăng nhập
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// Nội dung thông báo trạng thái tài khoản
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trạng Thái Tài Khoản</title>
    <link rel="stylesheet" href="../assest/css/account_status.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .status-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h2 {
            color: #333;
        }
        p {
            color: #555;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="status-container">
        <h2>Trạng Thái Tài Khoản</h2>
        <p>Cảm ơn bạn đã đăng ký. Tài khoản của bạn đang chờ quản trị viên phê duyệt.</p>
        <p>Vui lòng quay lại sau hoặc liên hệ với quản trị viên để được duyệt tài khoản.</p>
        <a href="login.php" class="btn">Quay lại Đăng Nhập</a>
    </div>
</body>
</html>