<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}
include '../config/db_connect.php';

$student_code = $_SESSION['username'];
// Lấy student_id
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

// Lấy danh sách yêu cầu
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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trạng Thái Yêu Cầu Về Muộn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <style>
        .main-content { padding: 20px; }
        .status-container {
            max-width: 950px; 
            margin: 0 auto;
            background: #fff; 
            padding: 30px; 
            border: 1px solid #ccc;
            font-family: 'Times New Roman', serif;
        }
        h2 { text-align: center; margin-bottom: 20px; }
        table {
            width: 100%; 
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd; 
            padding: 10px; 
            text-align: center;
        }
        th {
            background: #007bff; 
            color: #fff;
        }
    </style>
</head>
<body>
<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/header.php'; ?>

<div style="margin-top: 100px;" class="main-content">
    <div class="status-container">
        <h2>Trạng Thái Yêu Cầu Về Muộn</h2>
        <?php if (count($requests) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Mã YC</th>
                        <th>Thời gian gửi</th>
                        <th>Lý do</th>
                        <th>Trạng thái</th>
                        <th>Thời gian xử lý</th>
                        <th>Vi phạm?</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?php echo $r['late_request_id']; ?></td>
                        <td><?php echo $r['request_time']; ?></td>
                        <td><?php echo nl2br(htmlspecialchars($r['reason'])); ?></td>
                        <td>
                            <?php 
                                if ($r['status'] == 'pending') echo 'Đang chờ';
                                elseif ($r['status'] == 'approved') echo 'Đã hỗ trợ';
                                elseif ($r['status'] == 'rejected') echo 'Từ chối';
                            ?>
                        </td>
                        <td><?php echo $r['processed_time'] ?? ''; ?></td>
                        <td><?php echo ($r['is_violation'] == 1) ? 'Có' : 'Không'; ?></td>
                        <td><?php echo nl2br(htmlspecialchars($r['note'] ?? '')); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Bạn chưa gửi yêu cầu nào.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
