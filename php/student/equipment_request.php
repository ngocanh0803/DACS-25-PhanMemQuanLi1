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

// Lấy thông tin sinh viên để biết student_id và room_id
$sql_student = "SELECT student_id, room_id FROM Students WHERE student_code = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result_student = $stmt->get_result();

if ($result_student->num_rows > 0) {
    $student = $result_student->fetch_assoc();
    $student_id = $student['student_id'];
    $room_id = $student['room_id'];
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
    <title>Yêu cầu thiết bị - Sinh viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- CSS chung cho giao diện sinh viên -->
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <!-- CSS riêng cho trang yêu cầu thiết bị -->
    <link rel="stylesheet" href="../../assets/css/equipment_request.css">
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Nội dung chính -->
    <div class="main-content">
        <!-- Include Header -->
        <?php include 'layout/header.php'; ?>

        <div class="content">
            <h2>Yêu cầu thiết bị</h2>
            <form id="requestForm">
                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
                <div class="form-group">
                    <label for="request_type">Loại yêu cầu:</label>
                    <select name="request_type" id="request_type" required>
                        <option value="additional">Thêm thiết bị chung</option>
                        <option value="personal">Chuyển thêm thiết bị cá nhân</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="facility_name">Tên thiết bị:</label>
                    <input type="text" name="facility_name" id="facility_name" required>
                </div>
                <div class="form-group">
                    <label for="quantity">Số lượng:</label>
                    <input type="number" name="quantity" id="quantity" required min="1">
                </div>
                <div class="form-group">
                    <label for="description">Lý do / Mô tả yêu cầu:</label>
                    <textarea name="description" id="description" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn">Gửi yêu cầu</button>
            </form>
        </div>
    </div>

    <!-- JS cho trang yêu cầu thiết bị -->
    <script src="../../assets/js/equipment_request.js"></script>
</body>
</html>
