<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}
$student_code = $_SESSION['username'];
include '../config/db_connect.php';

$sqlStudent = "SELECT student_id, full_name FROM Students WHERE student_code = ?";
$stmtStudent = $conn->prepare($sqlStudent);
$stmtStudent->bind_param("s", $student_code);
$stmtStudent->execute();
$resultStudent = $stmtStudent->get_result();
if ($resultStudent->num_rows == 0) {
    die("Không tìm thấy thông tin sinh viên.");
}
$student = $resultStudent->fetch_assoc();
$student_id = $student['student_id'];
$stmtStudent->close();

$sqlApps = "SELECT * FROM Applications WHERE student_id = ? ORDER BY created_at DESC";
$stmtApps = $conn->prepare($sqlApps);
$stmtApps->bind_param("i", $student_id);
$stmtApps->execute();
$resultApps = $stmtApps->get_result();
$applications = [];
while ($row = $resultApps->fetch_assoc()) {
    $applications[] = $row;
}
$stmtApps->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trạng thái đơn đăng ký</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <link rel="stylesheet" href="../../assets/css/status_dashboard2.css">
</head>
<body>
    <?php include 'layout/sidebar.php'; ?>
    <?php include 'layout/header.php'; ?>

    <div class="main-content">
        <div class="dashboard-container">
        <h2><i class="fas fa-file-alt"></i> Trạng thái đơn đăng ký</h2>
        <?php if (count($applications) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> Mã đơn</th>
                        <th class="hide-on-mobile"><i class="fas fa-calendar-alt"></i> Ngày gửi</th>
                        <th class="hide-on-mobile"><i class="fas fa-calendar-check"></i> Ngày vào</th>
                        <th class="hide-on-mobile"><i class="fas fa-calendar-times"></i> Ngày ra</th>
                        <th><i class="fas fa-donate"></i> Tiền cọc</th>
                        <th><i class="fas fa-info-circle"></i> Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app):
                        $statusClass = '';
                        $statusText = '';
                        switch ($app['status']) {
                            case 'pending':
                                $statusClass = 'pending';
                                $statusText = 'Chờ duyệt';
                                break;
                            case 'approved':
                                $statusClass = 'approved';
                                $statusText = 'Đã duyệt';
                                break;
                            case 'rejected':
                                $statusClass = 'rejected';
                                $statusText = 'Bị từ chối';
                                break;
                            default:
                                $statusText = htmlspecialchars($app['status']); // Hiển thị giá trị gốc
                        }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($app['application_id']); ?></td>
                            <td class="hide-on-mobile"><?php echo htmlspecialchars($app['created_at']); ?></td>
                            <td class="hide-on-mobile"><?php echo htmlspecialchars($app['desired_start_date']); ?></td>
                            <td class="hide-on-mobile"><?php echo htmlspecialchars($app['desired_end_date']); ?></td>
                            <td><?php echo number_format($app['deposit'], 0, ',', '.'); ?> VND</td>
                            <td><span class="status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-applications">Bạn chưa gửi đơn đăng ký nào.</p>
        <?php endif; ?>
    </div>
    </div>
      <!-- Include Chatbox -->
      <?php include 'chatbox.php'; ?>
</body>
</html>