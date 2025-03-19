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

$sql_student = "SELECT student_id, room_id FROM Students WHERE student_code = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result_student = $stmt->get_result();

if ($result_student->num_rows > 0) {
    $student = $result_student->fetch_assoc();
    $room_id = $student['room_id'];
    $student_id = $student['student_id'];  //<---- Lấy student id
} else {
    die("Không tìm thấy thông tin sinh viên.");
}
$stmt->close();
$payments = [];

if ($room_id) {
    $sql_payment = "SELECT p.payment_code, r.room_code, p.electricity_usage, p.water_usage, p.total_amount, p.payment_status, p.payment_date, p.created_at
                    FROM Payments p
                    JOIN Rooms r ON p.room_id = r.room_id
                    WHERE p.room_id = ?"; // Chỉ lấy theo room_id
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
function convertPaymentStatus($status) {
    switch ($status) {
        case 'paid':
            return '<span class="status-badge paid">Đã thanh toán</span>';
        case 'unpaid':
            return '<span class="status-badge unpaid">Chưa thanh toán</span>';
        default:
            return htmlspecialchars($status);
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh toán & Hóa đơn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <link rel="stylesheet" href="../../assets/css/payments_student.css">
</head>
<body>

<?php include 'layout/sidebar.php'; ?>

<div class="main-content">
    <?php include 'layout/header.php'; ?>

    <div class="content">
      <div class="payments-container">
        <h2><i class="fas fa-file-invoice-dollar"></i> Thanh toán & Hóa đơn</h2>
        <?php if(count($payments) > 0): ?>
            <table class="payments-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> Mã</th>
                        <th class = "hide-on-mobile"><i class="fas fa-door-open"></i> Phòng</th>
                        <th class = "hide-on-mobile"><i class="fas fa-bolt"></i> Điện (kWh)</th>
                        <th class = "hide-on-mobile"><i class="fas fa-tint"></i> Nước (m³)</th>
                        <th><i class="fas fa-dollar-sign"></i> Tổng tiền</th>
                        <th><i class="fas fa-info-circle"></i> Trạng thái</th>
                        <th class = "hide-on-mobile"><i class="fas fa-calendar-alt"></i> Ngày tạo</th>
                        <th class = "hide-on-mobile"><i class="fas fa-calendar-check"></i> Ngày TT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($payments as $payment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['payment_code']); ?></td>
                            <td class = "hide-on-mobile"><?php echo htmlspecialchars($payment['room_code']); ?></td>
                            <td class = "hide-on-mobile"><?php echo number_format($payment['electricity_usage'], 2); ?></td>
                            <td class = "hide-on-mobile"><?php echo number_format($payment['water_usage'], 2); ?></td>
                            <td><?php echo number_format($payment['total_amount'], 0, ',', '.'); ?> VND</td>
                            <td><?php echo convertPaymentStatus($payment['payment_status']); ?></td>
                            <td class = "hide-on-mobile"><?php echo htmlspecialchars($payment['created_at']); ?></td>
                            <td class = "hide-on-mobile">
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
</div>
 <!-- Include Chatbox -->
 <?php include 'chatbox.php'; ?>
<!-- JS (nếu cần) -->
<script src="../../assets/js/payments_student.js"></script>
</body>
</html>