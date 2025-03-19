<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}
include '../config/db_connect.php';

$student_code = $_SESSION['username'];
$sqlStu = "SELECT student_id FROM Students WHERE student_code = ?";
$stmtStu = $conn->prepare($sqlStu);
$stmtStu->bind_param("s", $student_code);
$stmtStu->execute();
$resStu = $stmtStu->get_result();
if ($resStu->num_rows == 0) {
    die("Không tìm thấy sinh viên.");
}
$student = $resStu->fetch_assoc();
$student_id = $student['student_id'];
$stmtStu->close();

$sqlReq = "SELECT * FROM LateRequests WHERE student_id = ? ORDER BY request_time DESC";
$stmtReq = $conn->prepare($sqlReq);
$stmtReq->bind_param("i", $student_id);
$stmtReq->execute();
$resReq = $stmtReq->get_result();
$requests = [];
while($row = $resReq->fetch_assoc()) {
    $requests[] = $row;
}
$stmtReq->close();
$conn->close();

// Hàm chuyển đổi trạng thái (có style)
function displayStatus($status) {
    switch ($status) {
        case 'pending':
            return '<span class="status-badge pending">Đang chờ</span>';
        case 'approved':
            return '<span class="status-badge approved">Đã duyệt</span>';
        case 'rejected':
            return '<span class="status-badge rejected">Từ chối</span>';
        default:
            return htmlspecialchars($status);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trạng Thái Yêu Cầu Về Muộn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main_student.css">
     <link rel="stylesheet" href="../../assets/css/late_status.css">
</head>
<body>
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/header.php'; ?>

<div class="main-content">
    <div class="status-container">
        <h2><i class="fas fa-user-clock"></i> Trạng Thái Yêu Cầu Về Muộn</h2>
        <?php if (count($requests) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> Mã YC</th>
                        <th class="hide-on-mobile"><i class="fas fa-calendar-alt"></i> Thời gian gửi</th>
                        <th><i class="fas fa-comment"></i> Lý do</th>
                        <th><i class="fas fa-info-circle"></i> Trạng thái</th>
                        <th class = "hide-on-mobile"><i class="fas fa-calendar-check"></i> Thời gian xử lý</th>
                        <th><i class="fas fa-exclamation-triangle"></i> Vi phạm?</th>
                        <th><i class="fas fa-sticky-note"></i> Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['late_request_id']); ?></td>
                        <td class="hide-on-mobile"><?php echo htmlspecialchars($r['request_time']); ?></td>
                        <!-- Giữ nguyên xuống dòng -->
                        <td style="white-space: pre-wrap;"><?php echo htmlspecialchars($r['reason']); ?></td>
                        <td><?php echo displayStatus($r['status']); ?></td>
                        <td class = "hide-on-mobile"><?php echo htmlspecialchars($r['processed_time'] ?? ''); ?></td>
                        <td><?php echo ($r['is_violation'] == 1) ? 'Có' : 'Không'; ?></td>
                         <!-- Giữ nguyên xuống dòng -->
                        <td style="white-space: pre-wrap;"><?php echo htmlspecialchars($r['note'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-requests">Bạn chưa gửi yêu cầu nào.</p>
        <?php endif; ?>
    </div>
</div>
<!-- Include Chatbox -->
<?php include 'chatbox.php'; ?>
</body>
</html>