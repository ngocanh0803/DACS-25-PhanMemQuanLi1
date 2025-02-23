<?php
// Kết nối đến cơ sở dữ liệu
include 'db_connect.php';

// Xử lý khi người dùng bấm nút "Đăng ký"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Kiểm tra mật khẩu và xác nhận mật khẩu
    if ($password !== $confirm_password) {
        $error = "Mật khẩu và xác nhận mật khẩu không khớp.";
    } else {
        // Kiểm tra xem tên người dùng đã tồn tại chưa
        $sql_check = "SELECT * FROM Users WHERE username = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error = "Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.";
        } else {
            // Thêm người dùng mới vào cơ sở dữ liệu
            // $sql = "INSERT INTO Users (username, password, role) VALUES (?, ?, ?)";
            $sql = "INSERT INTO Users (username, password, role, is_approved) VALUES (?, ?, ?, 0)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $username, $password, $role);

            if ($stmt->execute()) {
                // Đăng ký thành công, hiển thị thông báo
                // $success = "Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.";
                // Đăng ký thành công, chuyển hướng đến trang trạng thái tài khoản
                header("Location: account_status.php");
                exit();
            } else {
                $error = "Có lỗi xảy ra. Vui lòng thử lại.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assest/css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Đăng Ký</h2>
        <form method="POST" action="">
            <div class="input-group">
                <label for="username">Tên đăng nhập</label>
                <input type="text" id="username" name="username" required>
                <i class="fa fa-user"></i>
            </div>
            <div class="input-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required>
                <i class="fa fa-lock"></i>
            </div>
            <div class="input-group">
                <label for="confirm_password">Xác nhận mật khẩu</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <i class="fa fa-lock"></i>
            </div>
            <div class="input-group">
                <label for="role">Vai trò</label>
                <select id="role" name="role" required>
                    <option value="manager">Quản lý phòng</option>
                    <option value="student_manager">Quản lý sinh viên</option>
                    <option value="accountant">Kế toán</option>
                </select>
            </div>
            <button type="submit" class="btn">Đăng Ký</button>
            <?php
            if (isset($error)) {
                echo "<p class='error'>$error</p>";
            }
            if (isset($success)) {
                echo "<p class='success'>$success</p>";
            }
            ?>
        </form>
        <div class="links">
            <a href="login.php">Bạn đã có tài khoản? Đăng nhập</a>
        </div>
    </div>
</body>
</html>