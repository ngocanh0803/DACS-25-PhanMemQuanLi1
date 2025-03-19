<?php
session_start();
// Kiểm tra đăng nhập và role: chỉ cho phép sinh viên truy cập
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

$student_code = $_SESSION['username'];

// Kết nối CSDL
include '../config/db_connect.php';

// Truy vấn lấy thông tin cá nhân của sinh viên
$sql = "SELECT student_id, student_code, full_name, email, phone, gender, date_of_birth, address, nationality, major, year_of_study, gpa, room_id, status
        FROM Students
        WHERE student_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    $student = $result->fetch_assoc();
} else {
    die("Không tìm thấy thông tin sinh viên.");
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông tin cá nhân - Sinh viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Link file CSS chung cho giao diện sinh viên -->
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <!-- File CSS riêng cho trang profile -->
    <link rel="stylesheet" href="../../assets/css/profile_student.css">    
</head>
<body>

    <!-- Include Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Nội dung chính -->
    <div class="main-content">
        <!-- Include Header -->
        <?php include 'layout/header.php'; ?>

        <div class="content">
            <h2 style="padding-bottom: 20px;"><center>Thông tin cá nhân</center></h2>
            <div class="profile-container">
                 <!-- Thêm ảnh đại diện -->
                <img src="../../assets/img/default-avatar.png" alt="Avatar" class="profile-avatar">
                <form id="profile-form">
                    <div class="form-group">
                        <label for="student_code">Mã sinh viên:</label>
                        <input type="text" id="student_code" name="student_code" value="<?php echo htmlspecialchars($student['student_code']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Họ và tên:</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="phone">Số điện thoại:</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="gender">Giới tính:</label>
                        <input type="text" id="gender" name="gender" value="<?php echo htmlspecialchars($student['gender']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Ngày sinh:</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($student['date_of_birth']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="address">Địa chỉ:</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($student['address']); ?>" readonly>
                    </div>
                     <div class="form-group">
                        <label for="nationality">Quốc tịch:</label>
                        <input type="text" id="nationality" name="nationality" value="<?php echo htmlspecialchars($student['nationality']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="major">Ngành học:</label>
                        <input type="text" id="major" name="major" value="<?php echo htmlspecialchars($student['major']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="year_of_study">Niên khóa:</label>
                        <input type="number" id="year_of_study" name="year_of_study" value="<?php echo htmlspecialchars($student['year_of_study']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="gpa">Điểm trung bình:</label>
                        <input type="text" id="gpa" name="gpa" value="<?php echo htmlspecialchars($student['gpa']); ?>" readonly>
                    </div>
                    <!-- Nút chuyển sang chế độ chỉnh sửa -->
                    <div class="form-group">
                        <button type="button" id="edit-btn" class="btn">Chỉnh sửa thông tin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
      <!-- Include Chatbox -->
    <?php include 'chatbox.php'; ?>
    <!-- File JS dành cho trang profile -->
    <script src="../../assets/js/profile_student.js"></script>
</body>
</html>