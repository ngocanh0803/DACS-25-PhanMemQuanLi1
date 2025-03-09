<?php
session_start();

// Kiểm tra admin (role admin hoặc manager)
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    header("Location: ../php/login.php");
    exit();
}

include '../config/db_connect.php';

// Truy vấn lấy tất cả đơn yêu cầu thiết bị
$sql = "SELECT er.request_id, er.request_type, er.facility_name, er.quantity, er.description, er.status, er.created_at,
               s.student_code, s.full_name, r.room_code
        FROM Equipment_Requests er
        JOIN Students s ON er.student_id = s.student_id
        JOIN Rooms r ON er.room_id = r.room_id
        ORDER BY er.created_at DESC";
$result = $conn->query($sql);
$requests = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()){
        $requests[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Yêu cầu Thiết bị</title>
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
            <h2>Quản lý Yêu cầu Thiết bị</h2>
            <table class="requests-table">
                <thead>
                    <tr>
                        <th>Mã yêu cầu</th>
                        <th>Sinh viên</th>
                        <th>Phòng</th>
                        <th>Loại yêu cầu</th>
                        <th>Tên thiết bị</th>
                        <th>Số lượng</th>
                        <th>Mô tả</th>
                        <th>Ngày gửi</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($requests as $req): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($req['request_id']); ?></td>
                        <td><?php echo htmlspecialchars($req['full_name']) . " (" . htmlspecialchars($req['student_code']) . ")"; ?></td>
                        <td><?php echo htmlspecialchars($req['room_code']); ?></td>
                        <td><?php echo $req['request_type'] === 'additional' ? 'Thêm chung' : 'Cá nhân'; ?></td>
                        <td><?php echo htmlspecialchars($req['facility_name']); ?></td>
                        <td><?php echo htmlspecialchars($req['quantity']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($req['description'])); ?></td>
                        <td><?php echo htmlspecialchars($req['created_at']); ?></td>
                        <td>
                            <?php 
                                if($req['status'] === 'pending') echo 'Chờ xử lý';
                                elseif($req['status'] === 'approved') echo 'Đã duyệt';
                                elseif($req['status'] === 'rejected') echo 'Từ chối';
                            ?>
                        </td>
                        <td>
                            <?php if($req['status'] === 'pending'): ?>
                                <button class="btn approve-btn" data-request-id="<?php echo $req['request_id']; ?>">Duyệt</button>
                                <button class="btn reject-btn" data-request-id="<?php echo $req['request_id']; ?>">Từ chối</button>
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
    <script src="../../assets/js/equipment_requests_admin.js"></script>
</body>
</html>
