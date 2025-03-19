<?php
session_start();

// Kiểm tra đăng nhập và role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

$student_code = $_SESSION['username'];

// Kết nối CSDL
include '../config/db_connect.php';

// Lấy thông tin sinh viên
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

    // Truy vấn thông tin phòng
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

// Chuyển trạng thái sang tiếng Việt
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
            $status_vn = $room['status']; // Giữ nguyên nếu không khớp
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
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <link rel="stylesheet" href="../../assets/css/room_info.css">
</head>
<body>

<?php include 'layout/sidebar.php'; ?>

<div class="main-content">
    <?php include 'layout/header.php'; ?>

    <div class="content">
        <h1 style="text-align: center;">Thông tin phòng của tôi</h1>
        <?php if ($room): ?>
            <div class="room-info-container">
                <!-- Cột hình ảnh -->
                <div>
                  <img src="https://media.viez.vn/prod/2022/2/23/image_956df3bc70.png" alt="Hình ảnh phòng (tạm)" class="room-image">
                </div>

                <!-- Cột thông tin -->
                <div class="room-details">
                    <div class="room-detail">
                        <span class="label"><i class="fas fa-key"></i> Mã phòng:</span>
                        <span class="value"><?php echo htmlspecialchars($room['room_code']); ?></span>
                    </div>
                    <div class="room-detail">
                        <span class="label"><i class="fas fa-building"></i> Tòa nhà:</span>
                        <span class="value"><?php echo htmlspecialchars($room['building']); ?></span>
                    </div>
                    <div class="room-detail">
                        <span class="label"><i class="fas fa-layer-group"></i> Số tầng:</span>
                        <span class="value"><?php echo htmlspecialchars($room['floor']); ?></span>
                    </div>
                    <div class="room-detail">
                        <span class="label"><i class="fas fa-door-open"></i> Số phòng:</span>
                        <span class="value"><?php echo htmlspecialchars($room['room_number']); ?></span>
                    </div>
                    <div class="room-detail">
                        <span class="label"><i class="fas fa-users"></i> Sức chứa:</span>
                        <span class="value"><?php echo htmlspecialchars($room['capacity']); ?></span>
                    </div>
                    <div class="room-detail">
                        <span class="label"><i class="fas fa-info-circle"></i> Trạng thái:</span>
                        <span class="value <?php echo htmlspecialchars($room['status']); ?>"><?php echo htmlspecialchars($status_vn); ?></span>
                    </div>
                    <div class="room-detail">
                        <span class="label"><i class="fas fa-dollar-sign"></i> Giá thuê:</span>
                        <span class="value"><?php echo number_format($room['price'], 0, ',', '.'); ?> VND</span>
                    </div>
                    <!-- Nút liên hệ -->
                      <div>
                         <!-- <a href="chatbox.php" class="contact-btn">Liên hệ BQL</a> -->
                      </div>
                </div>


            </div>
        <?php else: ?>
            <p class="no-room">Bạn chưa được phân phòng. Vui lòng liên hệ quản lý để được hỗ trợ.</p>
        <?php endif; ?>
    </div>
</div>
    <!-- Include Chatbox -->
    <?php include 'chatbox.php'; ?>
    <script src="../../assets/js/room_info.js"></script>
</body>
</html>