<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}
$student_code = $_SESSION['username'];
include '../config/db_connect.php';

// Lấy student_id từ bảng Students
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

// Lấy danh sách đơn đăng ký của sinh viên từ bảng Applications
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
    <style>
        .dashboard-container {
            max-width: 960px;
            margin-top: 100px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .dashboard-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        table th {
            background-color: #007bff;
            color: #fff;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-family: 'Times New Roman', serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        table th {
            background-color: #007bff;
            color: #fff;
        }
        .no-request {
            text-align: center;
            font-size: 18px;
            margin-top: 20px;
            font-family: 'Times New Roman', serif;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'layout/sidebar.php'; ?>
    <?php include 'layout/header.php'; ?>
    <div style="margin-top: 100px" class="dashboard-container">
        <h2>Trạng thái đơn đăng ký</h2>
        <?php if (count($applications) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Ngày gửi</th>
                        <th>Ngày nhận phòng dự kiến</th>
                        <th>Ngày kết thúc dự kiến</th>
                        <th>Tiền đặt cọc</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($app['application_id']); ?></td>
                            <td><?php echo htmlspecialchars($app['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($app['desired_start_date']); ?></td>
                            <td><?php echo htmlspecialchars($app['desired_end_date']); ?></td>
                            <td><?php echo number_format($app['deposit'], 2); ?></td>
                            <td>
                                <?php 
                                    switch ($app['status']) {
                                        case 'pending':
                                            echo 'Chờ phê duyệt';
                                            break;
                                        case 'approved':
                                            echo 'Đã phê duyệt';
                                            break;
                                        case 'rejected':
                                            echo 'Bị từ chối';
                                            break;
                                        default:
                                            echo $app['status'];
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Bạn chưa gửi đơn đăng ký nào.</p>
        <?php endif; ?>
    </div>
</body>
</html>
