<?php
session_start();

// Kiểm tra admin (ví dụ role là 'admin' hoặc 'manager')
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header("Location: ../php/login.php");
    exit();
}

include '../config/db_connect.php';

// Truy vấn lấy tất cả báo cáo sự cố
$sql = "SELECT er.report_id, er.report_date, er.reported_quantity, er.reported_condition, er.status, 
       f.facility_code, f.facility_name, f.status AS facility_status,
       s.student_code, s.full_name
        FROM Equipment_Reports er
        JOIN Facilities f ON er.facility_id = f.facility_id
        JOIN Students s ON er.student_id = s.student_id
        ORDER BY er.report_date DESC";
$result = $conn->query($sql);
$reports = [];
if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()){
        $reports[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Báo cáo Sự cố</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/payments.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/equipment_reports_admin.css">
    <link rel="stylesheet" href="../../assets/css/equipment_requests_admin.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container1">
        <?php include 'layout/menu.php'; ?>
        <div class="content">
            <h2>Quản lý Báo cáo Sự cố</h2>
            <table class="reports-table">
                <thead>
                    <tr>
                        <th>Mã Báo cáo</th>
                        <th>Mã thiết bị</th>
                        <th>Tên thiết bị</th>
                        <th>Sinh viên báo cáo</th>
                        <th>Số lượng lỗi</th>
                        <th>Mô tả</th>
                        <th>Ngày báo cáo</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($reports as $report): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($report['report_id']); ?></td>
                        <td><?php echo htmlspecialchars($report['facility_code']); ?></td>
                        <td><?php echo htmlspecialchars($report['facility_name']); ?></td>
                        <td><?php echo htmlspecialchars($report['full_name']) . " (" . htmlspecialchars($report['student_code']) . ")"; ?></td>
                        <td><?php echo htmlspecialchars($report['reported_quantity']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($report['reported_condition'])); ?></td>
                        <td><?php echo htmlspecialchars($report['report_date']); ?></td>
                        <td><?php echo $report['status'] === 'pending' ? 'Chờ xử lý' : 'Đã xử lý'; ?></td>
                        <td>
                            <?php if($report['status'] === 'pending'): ?>
                                <?php if(strtolower($report['facility_status']) != 'broken'): ?>
                                    <button class="btn verify-btn" data-report-id="<?php echo $report['report_id']; ?>">Xác nhận hỏng</button>
                                <?php else: ?>
                                    <button class="btn processed-btn" data-report-id="<?php echo $report['report_id']; ?>">Đã xử lý</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span>--</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Các file JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/search.js"></script>
    <script src="../../assets/js/equipment_reports_admin.js"></script>
</body>
</html>
