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

// Lấy thông tin sinh viên để biết room_id từ bảng Students
$sql_student = "SELECT room_id FROM Students WHERE student_code = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result_student = $stmt->get_result();

if ($result_student->num_rows > 0) {
    $student = $result_student->fetch_assoc();
    $room_id = $student['room_id'];
} else {
    die("Không tìm thấy thông tin sinh viên.");
}
$stmt->close();

$payments = [];
if ($room_id) {
    // Truy vấn lấy thông tin hóa đơn từ bảng Payments với join bảng Rooms để lấy mã phòng
    $sql_payment = "SELECT p.payment_code, r.room_code, p.electricity_usage, p.water_usage, p.total_amount, p.payment_status, p.payment_date, p.created_at
                    FROM Payments p
                    JOIN Rooms r ON p.room_id = r.room_id
                    WHERE p.room_id = ?";
    $stmt = $conn->prepare($sql_payment);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result_payments = $stmt->get_result();
    while ($row = $result_payments->fetch_assoc()) {
        $payments[] = $row;
    }
    $stmt->close();
}
$conn->close();

// Hàm chuyển đổi trạng thái thanh toán sang tiếng Việt
function convertPaymentStatus($status) {
    switch ($status) {
        case 'paid':
            return 'Đã thanh toán';
        case 'unpaid':
            return 'Chưa thanh toán';
        default:
            return $status;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh toán & Hóa đơn - Sinh viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- CSS chung cho giao diện sinh viên -->
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <!-- CSS riêng cho trang Thanh toán & Hóa đơn -->
    <link rel="stylesheet" href="../../assets/css/payments_student.css">
</head>
<body>

    <!-- Include Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Nội dung chính -->
    <div class="main-content">
        <!-- Include Header -->
        <?php include 'layout/header.php'; ?>

        <div class="content">
            <h2>Thanh toán & Hóa đơn</h2>
            <?php if(count($payments) > 0): ?>
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th>Mã hóa đơn</th>
                            <th>Phòng</th>
                            <th>Sử dụng điện (kWh)</th>
                            <th>Sử dụng nước (m³)</th>
                            <th>Tổng tiền (VND)</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo hóa đơn</th>
                            <th>Ngày thanh toán</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['payment_code']); ?></td>
                                <td><?php echo htmlspecialchars($payment['room_code']); ?></td>
                                <td><?php echo number_format($payment['electricity_usage'], 2); ?></td>
                                <td><?php echo number_format($payment['water_usage'], 2); ?></td>
                                <td><?php echo number_format($payment['total_amount'], 2); ?></td>
                                <td><?php echo convertPaymentStatus($payment['payment_status']); ?></td>
                                <td><?php echo htmlspecialchars($payment['created_at']); ?></td>
                                <td>
                                    <?php 
                                        // Nếu payment_date là null, không hiển thị gì
                                        echo ($payment['payment_date'] !== null) ? htmlspecialchars($payment['payment_date']) : '';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-payment">Không có hóa đơn thanh toán nào.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- File JS dành cho trang Thanh toán & Hóa đơn -->
    <script src="../../assets/js/payments_student.js"></script>
</body>
</html>
