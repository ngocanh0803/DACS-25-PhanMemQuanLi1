<?php
session_start();      // Bắt đầu (hoặc tiếp tục) session
session_destroy();    // Hủy toàn bộ dữ liệu session
header("Location: ../admin/login.php");  // Chuyển hướng về trang đăng nhập
exit();
