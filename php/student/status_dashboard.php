<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

$student_code = $_SESSION['username'];
include '../config/db_connect.php';

// Lấy thông tin báo cáo của sinh viên từ Equipment_Reports
$sql_reports = "SELECT er.report_id, er.report_date, er.reported_quantity, er.reported_condition, er.status,
                       f.facility_code, f.facility_name
                FROM Equipment_Reports er
                JOIN Facilities f ON er.facility_id = f.facility_id
                JOIN Students s ON er.student_id = s.student_id
                WHERE s.student_code = ?
                ORDER BY er.report_date DESC";
$stmt = $conn->prepare($sql_reports);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result_reports = $stmt->get_result();
$reports = [];
while ($row = $result_reports->fetch_assoc()) {
    $reports[] = $row;
}
$stmt->close();

// Lấy thông tin yêu cầu của sinh viên từ Equipment_Requests
$sql_requests = "SELECT request_id, request_type, facility_name, quantity, description, status, created_at
                 FROM Equipment_Requests
                 WHERE student_id = (SELECT student_id FROM Students WHERE student_code = ?)
                 ORDER BY created_at DESC";
$stmt = $conn->prepare($sql_requests);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result_requests = $stmt->get_result();
$requests = [];
while ($row = $result_requests->fetch_assoc()) {
    $requests[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tình trạng Báo cáo & Yêu cầu - Sinh viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- CSS chung của sinh viên -->
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <!-- CSS riêng cho dashboard -->
    <link rel="stylesheet" href="../../assets/css/status_dashboard.css">
</head>
<body>
    <?php include 'layout/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'layout/header.php'; ?>
        <div class="content">
            <h2>Tình trạng Báo cáo & Yêu cầu</h2>
            <div class="tabs">
                <button class="tab-btn active" data-tab="reports">Báo cáo sự cố</button>
                <button class="tab-btn" data-tab="requests">Yêu cầu thiết bị</button>
            </div>
            <div id="reports" class="tab-content active">
                <?php if (count($reports) > 0): ?>
                <table class="status-table">
                    <thead>
                        <tr>
                            <th>Mã báo cáo</th>
                            <th>Mã thiết bị</th>
                            <th>Tên thiết bị</th>
                            <th>Số lượng lỗi</th>
                            <th>Mô tả</th>
                            <th>Ngày báo cáo</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report['report_id']); ?></td>
                            <td><?php echo htmlspecialchars($report['facility_code']); ?></td>
                            <td><?php echo htmlspecialchars($report['facility_name']); ?></td>
                            <td><?php echo htmlspecialchars($report['reported_quantity']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($report['reported_condition'])); ?></td>
                            <td><?php echo htmlspecialchars($report['report_date']); ?></td>
                            <td><?php echo $report['status'] === 'pending' ? 'Chờ xử lý' : 'Đã xử lý'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>Không có báo cáo nào.</p>
                <?php endif; ?>
            </div>
            <div id="requests" class="tab-content">
                <?php if (count($requests) > 0): ?>
                <table class="status-table">
                    <thead>
                        <tr>
                            <th>Mã yêu cầu</th>
                            <th>Loại yêu cầu</th>
                            <th>Tên thiết bị</th>
                            <th>Số lượng</th>
                            <th>Mô tả</th>
                            <th>Ngày gửi</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($req['request_id']); ?></td>
                            <td><?php echo $req['request_type'] === 'additional' ? 'Thêm chung' : 'Cá nhân'; ?></td>
                            <td><?php echo htmlspecialchars($req['facility_name']); ?></td>
                            <td><?php echo htmlspecialchars($req['quantity']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($req['description'])); ?></td>
                            <td><?php echo htmlspecialchars($req['created_at']); ?></td>
                            <td>
                                <?php 
                                    if ($req['status'] === 'pending') echo 'Chờ xử lý';
                                    elseif ($req['status'] === 'approved') echo 'Đã duyệt';
                                    elseif ($req['status'] === 'rejected') echo 'Từ chối';
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>Không có yêu cầu thiết bị nào.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Tab switching logic
    document.querySelectorAll('.tab-btn').forEach(function(tabBtn) {
        tabBtn.addEventListener('click', function() {
            const tab = this.dataset.tab;
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(tab).classList.add('active');
        });
    });
    </script>
</body>
</html>
