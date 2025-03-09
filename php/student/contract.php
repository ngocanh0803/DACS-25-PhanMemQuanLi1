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

// Lấy student_id từ bảng Students dựa trên student_code
$sql_student = "SELECT student_id FROM Students WHERE student_code = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result_student = $stmt->get_result();

if($result_student->num_rows > 0) {
    $student = $result_student->fetch_assoc();
    $student_id = $student['student_id'];
} else {
    die("Không tìm thấy thông tin sinh viên.");
}
$stmt->close();

// Truy vấn lấy thông tin hợp đồng của sinh viên
$sql_contract = "SELECT contract_code, signed_date, start_date, end_date, deposit, terms, status 
                 FROM Contracts 
                 WHERE student_id = ?";
$stmt = $conn->prepare($sql_contract);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result_contract = $stmt->get_result();

$contracts = [];
if($result_contract->num_rows > 0) {
    while($row = $result_contract->fetch_assoc()){
        $contracts[] = $row;
    }
}
$stmt->close();
$conn->close();

// Chuyển đổi trạng thái hợp đồng sang tiếng Việt
function convertContractStatus($status) {
    switch ($status) {
        case 'active':
            return 'Đang hoạt động';
        case 'terminated':
            return 'Đã chấm dứt';
        case 'expired':
            return 'Hết hạn';
        default:
            return $status;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hợp đồng - Sinh viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- CSS chung cho giao diện sinh viên -->
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <!-- CSS riêng cho trang hợp đồng -->
    <link rel="stylesheet" href="../../assets/css/contract_student.css">
</head>
<body>

    <!-- Include Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Nội dung chính -->
    <div class="main-content">
        <!-- Include Header -->
        <?php include 'layout/header.php'; ?>

        <div class="content">
            <h2>Thông tin Hợp đồng</h2>
            <?php if(count($contracts) > 0): ?>
                <?php foreach($contracts as $contract): ?>
                    <div class="contract-container">
                        <div class="contract-detail">
                            <span class="label">Mã hợp đồng:</span>
                            <span class="value"><?php echo htmlspecialchars($contract['contract_code']); ?></span>
                        </div>
                        <div class="contract-detail">
                            <span class="label">Ngày ký:</span>
                            <span class="value"><?php echo htmlspecialchars($contract['signed_date']); ?></span>
                        </div>
                        <div class="contract-detail">
                            <span class="label">Thời gian bắt đầu:</span>
                            <span class="value"><?php echo htmlspecialchars($contract['start_date']); ?></span>
                        </div>
                        <div class="contract-detail">
                            <span class="label">Thời gian kết thúc:</span>
                            <span class="value"><?php echo htmlspecialchars($contract['end_date']); ?></span>
                        </div>
                        <div class="contract-detail">
                            <span class="label">Tiền đặt cọc:</span>
                            <span class="value"><?php echo number_format($contract['deposit'], 2); ?> VND</span>
                        </div>
                        <div class="contract-detail">
                            <span class="label">Điều khoản:</span>
                            <span class="value"><?php echo nl2br(htmlspecialchars($contract['terms'])); ?></span>
                        </div>
                        <div class="contract-detail">
                            <span class="label">Trạng thái:</span>
                            <span class="value"><?php echo convertContractStatus($contract['status']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-contract">Không tìm thấy hợp đồng nào. Vui lòng liên hệ quản lý để được hỗ trợ.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- File JS dành cho trang hợp đồng (nếu cần tương tác thêm) -->
    <script src="../../assets/js/contract_student.js"></script>
    <script>
        // contract_student.js
        document.addEventListener("DOMContentLoaded", function() {
            console.log("Trang hợp đồng đã được tải.");
            // Bạn có thể bổ sung thêm các xử lý tương tác tại đây.
        });

    </script>
</body>
</html>
