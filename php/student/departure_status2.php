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
if ($resStu->num_rows === 0) {
    die("Không tìm thấy sinh viên.");
}
$student = $resStu->fetch_assoc();
$student_id = $student['student_id'];
$stmtStu->close();

// Lấy danh sách đơn Departure_Requests
$sqlReq = "SELECT departure_id, request_date, reason, status, processed_date
           FROM Departure_Requests 
           WHERE student_id = ?
           ORDER BY request_date DESC";
$stmtReq = $conn->prepare($sqlReq);
$stmtReq->bind_param("i", $student_id);
$stmtReq->execute();
$resReq = $stmtReq->get_result();
$requests = [];
while ($row = $resReq->fetch_assoc()) {
    $requests[] = $row;
}
$stmtReq->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trạng thái Đơn rời phòng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <style>
        .status-container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            padding: 30px;
            border: 1px solid #ddd;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
            font-family: 'Times New Roman', serif;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%; 
            border-collapse: collapse;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px; 
            text-align: center;
        }
        table th {
            background: #007bff; 
            color: #fff;
        }
        .no-requests {
            text-align: center; 
            margin-top: 20px;
        }
    </style>
</head>
<body>

<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/header.php'; ?>

<div class="main-content">
    <div class="status-container">
        <h2>Trạng thái Đơn rời phòng</h2>
        <?php if (count($requests) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Ngày gửi</th>
                        <th>Lý do</th>
                        <th>Trạng thái</th>
                        <th>Ngày xử lý</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['departure_id']); ?></td>
                        <td><?php echo htmlspecialchars($r['request_date']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($r['reason'])); ?></td>
                        <td>
                            <?php
                                if ($r['status'] == 'pending') echo 'Chờ xử lý';
                                elseif ($r['status'] == 'approved') echo 'Đã duyệt';
                                elseif ($r['status'] == 'rejected') echo 'Từ chối';
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($r['processed_date'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-requests">Bạn chưa gửi đơn rời phòng nào.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
