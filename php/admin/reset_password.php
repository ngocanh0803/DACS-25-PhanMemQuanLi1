<?php
// Kết nối đến cơ sở dữ liệu
include '../config/db_connect.php';

// Xử lý khi người dùng bấm nút "Đặt lại mật khẩu"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Kiểm tra mật khẩu mới và xác nhận mật khẩu mới
    if ($new_password !== $confirm_new_password) {
        $error = "Mật khẩu mới và xác nhận mật khẩu không khớp.";
    } else {
        // Kiểm tra xem tên người dùng và mật khẩu cũ có đúng không
        $sql_check = "SELECT * FROM Users WHERE username = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $user = $result_check->fetch_assoc();

            // Kiểm tra mật khẩu cũ
            if ($user['password'] === $old_password) { 
                // Cập nhật mật khẩu mới
                $sql_update = "UPDATE Users SET password = ? WHERE username = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ss", $new_password, $username);

                if ($stmt_update->execute()) {
                    // Đặt lại mật khẩu thành công, chuyển hướng về trang đăng nhập
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "Có lỗi xảy ra. Vui lòng thử lại.";
                }
            } else {
                $error = "Mật khẩu cũ không chính xác.";
            }
        } else {
            $error = "Tên đăng nhập không tồn tại.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Lại Mật Khẩu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Đặt Lại Mật Khẩu</h2>
        <form method="POST" action="">
            <div class="input-group">
                <label for="username">Tên đăng nhập</label>
                <input type="text" id="username" name="username" required placeholder="Nhập tên đăng nhập">
                <i class="fa fa-user"></i>
            </div>
            <div class="input-group">
                <label for="old_password">Mật khẩu hiện tại</label>
                <input type="password" id="old_password" name="old_password" required placeholder="Nhập mật khẩu hiện tại">
                <i class="fa fa-lock"></i>
            </div>
            <div class="input-group">
                <label for="new_password">Mật khẩu mới</label>
                <input type="password" id="new_password" name="new_password" required placeholder="Nhập mật khẩu mới">
                <i class="fa fa-lock"></i>
            </div>
            <div class="input-group">
                <label for="confirm_new_password">Xác nhận mật khẩu mới</label>
                <input type="password" id="confirm_new_password" name="confirm_new_password" required placeholder="Nhập lại mật khẩu mới">
                <i class="fa fa-lock"></i>
            </div>
            <button type="submit" class="btn">Đặt Lại Mật Khẩu</button>
            <?php
            // Hiển thị thông báo lỗi nếu có
            if (isset($error)) {
                echo "<p class='error'>$error</p>";
            }
            ?>
        </form>
        <div class="links">
            <a href="login.php">Quay lại Đăng Nhập</a>
        </div>
    </div>
</body>
</html>