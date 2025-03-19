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

// Lấy student_id
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

// Truy vấn hợp đồng
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

function convertContractStatus($status) {
    switch ($status) {
        case 'active':
            return '<span class="status active">Đang hoạt động</span>';
        case 'terminated':
            return '<span class="status terminated">Đã chấm dứt</span>';
        case 'expired':
            return '<span class="status expired">Hết hạn</span>';
        default:
            return htmlspecialchars($status);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hợp đồng - Sinh viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <link rel="stylesheet" href="../../assets/css/contract_student.css">
</head>
<body>

<?php include 'layout/sidebar.php'; ?>

<div class="main-content">
    <?php include 'layout/header.php'; ?>

    <div class="content">
        <h2 style="padding-bottom: 20px; text-align: center;">Thông tin Hợp đồng</h2>
        <?php if(count($contracts) > 0): ?>
            <?php foreach($contracts as $contract): ?>
                <div class="contract-container">
                    <div class="contract-detail">
                        <span class="label"><i class="fas fa-file-contract"></i> Mã hợp đồng:</span>
                        <span class="value"><?php echo htmlspecialchars($contract['contract_code']); ?></span>
                    </div>
                    <div class="contract-detail">
                        <span class="label"><i class="fas fa-calendar-check"></i> Ngày ký:</span>
                        <span class="value"><?php echo htmlspecialchars($contract['signed_date']); ?></span>
                    </div>
                    <div class="contract-detail">
                        <span class="label"><i class="fas fa-calendar-day"></i> Thời gian bắt đầu:</span>
                        <span class="value"><?php echo htmlspecialchars($contract['start_date']); ?></span>
                    </div>
                    <div class="contract-detail">
                        <span class="label"><i class="fas fa-calendar-times"></i> Thời gian kết thúc:</span>
                        <span class="value"><?php echo htmlspecialchars($contract['end_date']); ?></span>
                    </div>
                    <div class="contract-detail">
                        <span class="label"><i class="fas fa-donate"></i> Tiền đặt cọc:</span>
                        <span class="value"><?php echo number_format($contract['deposit'], 0, ',', '.'); ?> VND</span>
                    </div>
                    <div class="contract-detail">
                        <span class="label"><i class="fas fa-file-alt"></i> Điều khoản:</span>
                        <!-- Sử dụng white-space: pre-wrap; để giữ nguyên xuống dòng -->
                        <span class="value" style="white-space: pre-wrap;"><?php echo htmlspecialchars($contract['terms']); ?></span>
                    </div>

                    <div class="contract-detail">
                      <span class="label"><i class="fas fa-info-circle"></i> Trạng thái:</span>
                      <span class="value"><?php echo convertContractStatus($contract['status']); ?></span>
                    </div>
                    <div class="action-bar">
                      <a href="departure_expire.php" class="btn request-btn"> Yêu cầu rời phòng đúng hạn </a>
                      <a href="departure_request.php" class="btn request-btn"> Yêu cầu rời phòng trước hạn </a>
                  </div>
                </div>


            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-contract">Không tìm thấy hợp đồng nào. Vui lòng liên hệ quản lý để được hỗ trợ.</p>
        <?php endif; ?>
    </div>
</div>
  <!-- Include Chatbox -->
<?php include 'chatbox.php'; ?>
<!-- File JS (nếu cần) -->
<script src="../../assets/js/contract_student.js"></script>
</body>
</html>