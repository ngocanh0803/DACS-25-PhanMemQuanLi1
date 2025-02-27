<?php
session_start();

// Kiểm tra đăng nhập và role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

// Vì username chính là student_code nên lấy nó từ session
$student_code = $_SESSION['username'];

// Kết nối CSDL (chỉnh sửa thông tin kết nối cho phù hợp)
include '../config/db_connect.php';

// Truy vấn: Lấy họ và full_name từ bảng Students dựa trên student_code
$sql = "SELECT SUBSTRING_INDEX(full_name, ' ', 1) AS surname, full_name 
        FROM Students 
        WHERE student_code = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $surname = $row['surname'];
    $fullName = $row['full_name'];
} 

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang chính - Sinh viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main_student.css">
</head>
<body>

    <!-- Include Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Nội dung chính -->
    <div class="main-content">
        <!-- Include Header -->
        <?php include 'layout/header.php'; ?>

        <div class="content">
            <div class="card-container">
                <div class="card">
                    <i class="fas fa-user-graduate card-icon"></i>
                    <h3>Thông tin cá nhân</h3>
                    <p>Xem và cập nhật thông tin.</p>
                    <a href="profile.php" class="btn">Xem ngay</a>
                </div>
                <div class="card">
                    <i class="fas fa-door-open card-icon"></i>
                    <h3>Phòng ở</h3>
                    <p>Xem thông tin phòng.</p>
                    <a href="room_info.php" class="btn">Xem ngay</a>
                </div>
                <div class="card">
                    <i class="fas fa-file-contract card-icon"></i>
                    <h3>Hợp đồng</h3>
                    <p>Xem chi tiết hợp đồng.</p>
                    <a href="contract.php" class="btn">Xem ngay</a>
                </div>
                <div class="card">
                    <i class="fas fa-money-bill-wave card-icon"></i>
                    <h3>Thanh toán</h3>
                    <p>Xem lịch sử hóa đơn.</p>
                    <a href="payments.php" class="btn">Xem ngay</a>
                </div>
                <div class="card">
                    <i class="fas fa-tools card-icon"></i>
                    <h3>Cơ sở vật chất</h3>
                    <p>Kiểm tra và báo cáo sự cố.</p>
                    <a href="facilities.php" class="btn">Xem ngay</a>
                </div>
                <div class="card">
                    <i class="fas fa-info-circle card-icon"></i>
                    <h3>Tình trạng phòng</h3>
                    <p>Theo dõi lịch sử phòng.</p>
                    <a href="room_status.php" class="btn">Xem ngay</a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
