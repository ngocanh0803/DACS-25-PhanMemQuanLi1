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

// Lấy thông tin sinh viên để biết phòng được phân (room_id)
$sql_student = "SELECT room_id FROM Students WHERE student_code = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

$room = null;
if ($student && $student['room_id']) {
    $room_id = $student['room_id'];
    
    // Truy vấn lấy thông tin phòng từ bảng Rooms
    $sql_room = "SELECT room_code, building, floor, room_number, capacity, status, price FROM Rooms WHERE room_id = ?";
    $stmt = $conn->prepare($sql_room);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $room_result = $stmt->get_result();
    if($room_result->num_rows > 0){
        $room = $room_result->fetch_assoc();
    }
    $stmt->close();
}

// Chuyển đổi trạng thái sang tiếng Việt
$status_vn = '';
if ($room) {
    switch ($room['status']) {
        case 'available':
            $status_vn = 'Còn trống';
            break;
        case 'occupied':
            $status_vn = 'Đang ở';
            break;
        case 'maintenance':
            $status_vn = 'Bảo trì';
            break;
        default:
            $status_vn = $room['status'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông tin phòng - Sinh viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- CSS chung cho giao diện sinh viên -->
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <!-- CSS riêng cho trang thông tin phòng -->
    <link rel="stylesheet" href="../../assets/css/room_info.css">
</head>
<body>

    <!-- Include Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Nội dung chính -->
    <div class="main-content">
        <!-- Include Header -->
        <?php include 'layout/header.php'; ?>

        <div class="content">
            <h2>Thông tin phòng của tôi</h2>
            <?php if ($room): ?>
                <div class="room-info-container">
                    <div class="room-detail">
                        <span class="label">Mã phòng:</span>
                        <span class="value"><?php echo htmlspecialchars($room['room_code']); ?></span>
                    </div>
                    <div class="room-detail">
                        <span class="label">Tòa nhà:</span>
                        <span class="value"><?php echo htmlspecialchars($room['building']); ?></span>
                    </div>
                    <div class="room-detail">
                        <span class="label">Số tầng:</span>
                        <span class="value"><?php echo htmlspecialchars($room['floor']); ?></span>
                    </div>
                    <div class="room-detail">
                        <span class="label">Số phòng:</span>
                        <span class="value"><?php echo htmlspecialchars($room['room_number']); ?></span>
                    </div>
                    <div class="room-detail">
                        <span class="label">Sức chứa:</span>
                        <span class="value"><?php echo htmlspecialchars($room['capacity']); ?></span>
                    </div>
                    <div class="room-detail">
                        <span class="label">Trạng thái:</span>
                        <span class="value"><?php echo htmlspecialchars(($status_vn)); ?></span>
                    </div>
                    <div class="room-detail">
                        <span class="label">Giá thuê:</span>
                        <span class="value"><?php echo number_format($room['price'], 2); ?> VND</span>
                    </div>
                </div>
            <?php else: ?>
                <p class="no-room">Bạn chưa được phân phòng. Vui lòng liên hệ quản lý để được hỗ trợ.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Nếu cần JS để xử lý tương tác (ví dụ: tải lại thông tin, ...), include file JS -->
    <script src="../../assets/js/room_info.js"></script>
    <script>
        // room_info.js
        document.addEventListener("DOMContentLoaded", function() {
            // Ví dụ: Hiển thị thông báo khi thông tin phòng được tải thành công
            console.log("Thông tin phòng đã được tải.");
        });
    </script>
</body>
</html>
