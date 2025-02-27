<?php
session_start();

// Kết nối đến cơ sở dữ liệu
include '../config/db_connect.php';

// Xử lý khi người dùng bấm nút "Đăng nhập"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Truy vấn kiểm tra thông tin người dùng
    $sql = "SELECT * FROM Users WHERE username = ? AND password = ? AND role = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $password, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    // Kiểm tra kết quả truy vấn
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION["username"] = $username;
        $_SESSION["role"] = $user["role"];
        $_SESSION["is_approved"] = $user["is_approved"];

        if ($user["is_approved"] == 1) {
            // Tài khoản đã được duyệt
            if ($user["role"] == 'student') {
                // Nếu là sinh viên, chuyển hướng đến giao diện sinh viên
                header("Location: ../student/main.php");
            } else {
                // Các role còn lại chuyển đến giao diện quản trị hiện có
                header("Location: main.php");
            }
            exit();
        } else {
            // Tài khoản chưa được duyệt
            header("Location: account_status.php");
            exit();
        }
    } else {
        // Sai thông tin đăng nhập
        $error = "Tên đăng nhập hoặc mật khẩu không chính xác.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Đăng Nhập</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Đăng Nhập</h2>
        <form method="POST" action="">
            <div class="input-group">
                <label for="username">Tên đăng nhập</label>
                <input type="text" id="username" name="username" required placeholder="Nhập tên đăng nhập">
                <i class="fa fa-user"></i>
            </div>
            <div class="input-group password-wrapper">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu">
                <i id="togglePassword" class="fa fa-eye"></i>
            </div>

            <div class="input-group">
                <label for="role">Vai trò</label>
                <select id="role" name="role" required>
                    <option value="student">Sinh viên</option>
                    <option value="manager">Quản lý phòng</option>
                    <option value="student_manager">Quản lý sinh viên</option>
                    <option value="accountant">Kế toán</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn">Đăng Nhập</button>
            <?php
            // Hiển thị thông báo lỗi nếu có
            if (isset($error)) {
                echo "<p class='error'>$error</p>";
            }
            ?>
        </form>
        <div class="links">
            <a href="reset_password.php">Đổi mật khẩu?</a>
            <a href="register.php">Đăng ký</a>
        </div>
    </div>
    <script>
        // JavaScript để Ẩn/Hiện mật khẩu
        document.addEventListener("DOMContentLoaded", function() {
            const togglePassword = document.querySelector("#togglePassword");
            const passwordInput = document.querySelector("#password");

            togglePassword.addEventListener("click", function() {
                // Kiểm tra trạng thái input
                const type = passwordInput.type === "password" ? "text" : "password";
                passwordInput.type = type;

                // Thay đổi icon
                this.classList.toggle("fa-eye");
                this.classList.toggle("fa-eye-slash");
            });
        });

    </script>
</body>
</html>