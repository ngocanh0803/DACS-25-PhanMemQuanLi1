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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .status-container {
            max-width: 500px;
            padding: 25px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: fadeIn 0.8s ease-in-out;
        }

        h2 {
            color: #2d3436;
            font-weight: 600;
        }

        p {
            color: #636e72;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #0984e3;
            color: white;
            font-weight: 500;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 4px 10px rgba(9, 132, 227, 0.4);
        }

        .btn:hover {
            background: #74b9ff;
            box-shadow: 0 6px 15px rgba(9, 132, 227, 0.6);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="status-container">
        <h2>Trạng Thái Tài Khoản</h2>
        <p>Cảm ơn bạn đã đăng ký! Tài khoản của bạn đang chờ quản trị viên phê duyệt.</p>
        <p>Vui lòng quay lại sau hoặc liên hệ với quản trị viên để được hỗ trợ.</p>
        <a href="login.php" class="btn">Quay lại Đăng Nhập</a>
    </div>
</body>
</html>
