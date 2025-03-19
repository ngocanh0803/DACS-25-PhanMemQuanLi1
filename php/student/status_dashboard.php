<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

$student_code = $_SESSION['username'];
include '../config/db_connect.php';

// Lấy student_id
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

// Lấy báo cáo
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

// Lấy yêu cầu
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
    <title>Tình trạng Báo cáo & Yêu cầu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <link rel="stylesheet" href="../../assets/css/status_dashboard.css">
</head>
<body>
    <?php include 'layout/sidebar.php'; ?>
    <?php include 'layout/header.php'; ?>
    <div class="main-content">

        <div class="content">
            <h2><i class="fas fa-clipboard-list"></i> Tình trạng Báo cáo & Yêu cầu</h2>
            <div class="tabs">
                <button class="tab-btn active" data-tab="reports"><i class="fas fa-exclamation-triangle"></i> Báo cáo sự cố</button>
                <button class="tab-btn" data-tab="requests"><i class="fas fa-plus-square"></i> Yêu cầu thiết bị</button>
            </div>
            <div id="reports" class="tab-content active">
                <?php if (count($reports) > 0): ?>
                <table class="status-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> Mã</th>
                            <th class="hide-on-mobile"><i class="fas fa-barcode"></i> Mã TB</th>
                            <th><i class="fas fa-tools"></i> Tên thiết bị</th>
                            <th class="hide-on-mobile"><i class="fas fa-sort-numeric-up"></i> Số lượng</th>
                            <th><i class="fas fa-comment"></i> Mô tả</th>
                            <th class="hide-on-mobile"><i class="fas fa-calendar-alt"></i> Ngày báo</th>
                            <th><i class="fas fa-info-circle"></i> Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report):
                             $statusClass = $report['status'] === 'pending' ? 'pending' : 'processed';
                            $statusText = $report['status'] === 'pending' ? 'Chờ xử lý' : 'Đã xử lý';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report['report_id']); ?></td>
                            <td class="hide-on-mobile"><?php echo htmlspecialchars($report['facility_code']); ?></td>
                            <td><?php echo htmlspecialchars($report['facility_name']); ?></td>
                            <td class="hide-on-mobile"><?php echo htmlspecialchars($report['reported_quantity']); ?></td>
                            <!-- Giữ nguyên xuống dòng -->
                            <td style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['reported_condition']); ?></td>
                            <td class="hide-on-mobile"><?php echo htmlspecialchars($report['report_date']); ?></td>

                            <td><span class="status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="no-reports">Không có báo cáo nào.</p>
                <?php endif; ?>
            </div>
            <div id="requests" class="tab-content">
                <?php if (count($requests) > 0): ?>
                <table class="status-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> Mã</th>
                            <th><i class="fas fa-tag"></i> Loại</th>
                            <th><i class="fas fa-tools"></i> Tên thiết bị</th>
                            <th class="hide-on-mobile"><i class="fas fa-sort-numeric-up"></i> Số lượng</th>
                            <th><i class="fas fa-comment"></i> Mô tả</th>
                            <th class="hide-on-mobile"><i class="fas fa-calendar-alt"></i> Ngày gửi</th>
                            <th><i class="fas fa-info-circle"></i> Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req):
                            $statusClass = '';
                            $statusText = '';
                            if ($req['status'] === 'pending') {
                                $statusClass = 'pending';
                                $statusText = 'Chờ xử lý';
                            } elseif ($req['status'] === 'approved') {
                                $statusClass = 'approved';
                                $statusText = 'Đã duyệt';
                            } elseif ($req['status'] === 'rejected') {
                                $statusClass = 'rejected';
                                $statusText = 'Từ chối';
                            }
                        ?>

                        <tr>
                            <td><?php echo htmlspecialchars($req['request_id']); ?></td>
                            <td><?php echo $req['request_type'] === 'additional' ? 'Thêm chung' : 'Cá nhân'; ?></td>
                            <td><?php echo htmlspecialchars($req['facility_name']); ?></td>
                            <td class="hide-on-mobile"><?php echo htmlspecialchars($req['quantity']); ?></td>
                             <!-- Giữ nguyên xuống dòng -->
                            <td style="white-space: pre-wrap;"><?php echo htmlspecialchars($req['description']); ?></td>
                            <td class="hide-on-mobile"><?php echo htmlspecialchars($req['created_at']); ?></td>

                            <td><span class="status <?php echo $statusClass; ?>"><?php echo $statusText?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="no-requests">Không có yêu cầu thiết bị nào.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
      <!-- Include Chatbox -->
      <?php include 'chatbox.php'; ?>
    <script>
    // Tab switching logic
    document.addEventListener('DOMContentLoaded', function() { // Đảm bảo DOM đã load
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Lấy data-tab của nút được click
            const tabId = this.dataset.tab;

            // 1. Xử lý nút:  Bỏ active ở tất cả các nút, thêm active vào nút được click
            tabBtns.forEach(otherBtn => otherBtn.classList.remove('active'));
            this.classList.add('active');

            // 2. Xử lý nội dung:  Ẩn tất cả, hiện cái tương ứng với nút được click
            tabContents.forEach(content => {
                content.classList.remove('active'); // Ẩn tất cả
            });
            document.getElementById(tabId).classList.add('active'); // Hiện tab content
        });
    });
});
    </script>
</body>
</html>