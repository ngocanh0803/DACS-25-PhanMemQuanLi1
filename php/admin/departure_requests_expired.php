<?php
session_start();

// Kiểm tra quyền: chỉ admin/manager/student_manager/accountant
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin','manager','student_manager','accountant'])) {
    header("Location: ../php/login.php");
    exit();
}

include '../config/db_connect.php';

// Lấy danh sách đơn rời phòng do hết hạn hợp đồng 
// (ví dụ: ta kiểm tra reason LIKE '%hết hạn%' hoặc cột type = 'expire')
$sql = "SELECT dr.departure_id, dr.request_date, dr.reason, dr.documents, dr.status, dr.processed_date,
       s.student_code, s.full_name, c.contract_code
        FROM Departure_Requests dr
        JOIN Students s ON dr.student_id = s.student_id
        JOIN Contracts c ON dr.contract_id = c.contract_id
        -- BỎ hẳn WHERE, hiển thị tất cả
        ORDER BY dr.request_date DESC
        ";

$result = $conn->query($sql);
$departures = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departures[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Đơn rời phòng (Hết hạn HĐ)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- CSS chung của admin -->
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/departure_requests_expired.css">
</head>
<body>
<?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="container1">
                <h2>Danh sách Đơn rời phòng do Hết hạn Hợp đồng</h2>
                <?php if(count($departures) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Sinh viên</th>
                                <th>Mã SV</th>
                                <th>Mã HĐ</th>
                                <th>Ngày gửi</th>
                                <th>Lý do</th>
                                <th>Trạng thái</th>
                                <th>Ngày xử lý</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($departures as $dep): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($dep['departure_id']); ?></td>
                                <td><?php echo htmlspecialchars($dep['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($dep['student_code']); ?></td>
                                <td><?php echo htmlspecialchars($dep['contract_code']); ?></td>
                                <td><?php echo htmlspecialchars($dep['request_date']); ?></td>
                                <td style="word-wrap: break-word; max-width: 200px"><?php echo nl2br(htmlspecialchars($dep['reason'])); ?></td>
                                <td>
                                    <?php 
                                        if($dep['status'] == 'pending') echo 'Chờ xử lý';
                                        elseif($dep['status'] == 'approved') echo 'Đã duyệt';
                                        elseif($dep['status'] == 'rejected') echo 'Từ chối';
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($dep['processed_date'] ?? ''); ?></td>
                                <td>
                                    <?php if($dep['status'] == 'pending'): ?>
                                        <button class="action-btn approve-btn" data-id="<?php echo $dep['departure_id']; ?>">Duyệt</button>
                                        <button class="action-btn reject-btn" data-id="<?php echo $dep['departure_id']; ?>">Từ chối</button>
                                    <?php else: ?>
                                        --
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Không có đơn rời phòng nào do hết hạn hợp đồng.</p>
                <?php endif; ?>
            </div>
        </main>

    <!-- Modal duyệt đơn -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeApproveModal">&times;</span>
            <h3>Duyệt đơn rời phòng (Hết hạn HĐ)</h3>
            <p>Chọn xử lý phòng và hợp đồng khi sinh viên rời:</p>
            <div class="btn-confirm" id="confirmApprove">Xác nhận</div>
        </div>
    </div>

    <!-- Modal từ chối đơn -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeRejectModal">&times;</span>
            <h3>Từ chối đơn rời phòng</h3>
            <div class="form-group">
                <label for="reject_reason">Lý do từ chối:</label>
                <textarea id="reject_reason" rows="4" placeholder="Nhập lý do từ chối"></textarea>
            </div>
            <button class="btn-confirm" id="confirmReject">Xác nhận từ chối</button>
        </div>
    </div>
    <?php include 'layout/js.php'; ?>
    <script src="../../assets/js/departure_requests_expired.php.js"></script>
</body>
</html>
