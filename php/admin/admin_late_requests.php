<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin','manager','student_manager','accountant'])) {
    header("Location: ../php/login.php");
    exit();
}

include '../config/db_connect.php';

$sql = "SELECT lr.*, s.student_code, s.full_name
        FROM LateRequests lr
        JOIN Students s ON lr.student_id = s.student_id
        ORDER BY lr.request_time DESC";
$result = $conn->query($sql);
$requests = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Yêu Cầu Về Muộn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/admin_late_requests.css">
</head>
<body>
<?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="container1">
                <h2>Danh Sách Yêu Cầu Về Muộn</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Mã YC</th>
                            <th>Mã SV</th>
                            <th>Họ Tên</th>
                            <th>Lý Do</th>
                            <th>Thời Gian Gửi</th>
                            <th>Trạng Thái</th>
                            <th>Vi Phạm?</th>
                            <th>Ghi Chú</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php foreach($requests as $req): ?>
                        <tr>
                            <td><?php echo $req['late_request_id']; ?></td>
                            <td><?php echo htmlspecialchars($req['student_code']); ?></td>
                            <td><?php echo htmlspecialchars($req['full_name']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($req['reason'])); ?></td>
                            <td><?php echo $req['request_time']; ?></td>
                            <td>
                                <?php
                                    if ($req['status'] == 'pending') echo 'Chờ Xử Lý';
                                    elseif ($req['status'] == 'approved') echo 'Đã Hỗ Trợ';
                                    elseif ($req['status'] == 'rejected') echo 'Từ Chối';
                                ?>
                            </td>
                            <td><?php echo ($req['is_violation'] == 1) ? 'Có' : 'Không'; ?></td>
                            <td><?php echo nl2br(htmlspecialchars($req['note'] ?? '')); ?></td>
                            <td>
                                <?php if ($req['status'] == 'pending'): ?>
                                    <button class="action-btn approve-btn" data-id="<?php echo $req['late_request_id']; ?>">Duyệt</button>
                                    <button class="action-btn reject-btn" data-id="<?php echo $req['late_request_id']; ?>">Từ Chối</button>
                                <?php else: ?>
                                    <?php if ($req['status'] == 'approved' && $req['is_violation'] == 0): ?>
                                        <button class="action-btn violation-btn" data-id="<?php echo $req['late_request_id']; ?>">Ghi Vi Phạm</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9">Không có yêu cầu nào.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>

<?php include 'layout/js.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function handleAction(requestId, action, reason=null) {
        const data = { late_request_id: requestId, action: action };
        if (reason) data['reject_reason'] = reason;

        fetch('ajax/handle_late_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(resp => {
            alert(resp.message);
            if (resp.success) {
                window.location.reload();
            }
        })
        .catch(err => {
            console.error("Error:", err);
            alert("Lỗi khi xử lý yêu cầu.");
        });
    }

    // Approve
    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            handleAction(id, 'approve');
        });
    });

    // Reject
    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const reason = prompt("Nhập lý do từ chối:", "Về quá giờ nhiều lần...");
            if (reason !== null) {
                handleAction(id, 'reject', reason);
            }
        });
    });

    // Mark violation
    document.querySelectorAll('.violation-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (confirm("Bạn có chắc muốn đánh dấu vi phạm cho lần về muộn này?")) {
                handleAction(id, 'mark_violation');
            }
        });
    });
});
</script>
</body>
</html>
