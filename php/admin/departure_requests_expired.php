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
    <style>
        .container1 { padding: 20px; }
        h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: center; }
        th { background-color: #007bff; color: #fff; }
        .action-btn {
            padding: 5px 10px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            margin: 2px;
        }
        .approve-btn { background-color: #28a745; color: #fff; }
        .reject-btn  { background-color: #dc3545; color: #fff; }
        /* Modal style */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; top: 0;
            width: 100%; height: 100%;
            overflow: auto; 
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 4px;
            width: 400px;
            position: relative;
        }
        .modal-content h3 { margin-top: 0; }
        .close-modal {
            position: absolute;
            top: 10px; right: 15px;
            font-size: 20px;
            cursor: pointer;
        }
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; }
        .form-group label { font-weight: bold; margin-bottom: 5px; }
        .form-group textarea {
            padding: 8px; 
            font-size: 16px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            resize: none;
        }
        .btn-confirm {
            padding: 8px 16px; 
            background-color: #007bff; 
            color: #fff; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer;
        }
    </style>
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
                                <th>Mã đơn</th>
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
                                <td><?php echo nl2br(htmlspecialchars($dep['reason'])); ?></td>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/search.js"></script>>
    <script src="../../assets/js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let selectedDepartureId = null;

        const approveModal = document.getElementById('approveModal');
        const closeApproveModal = document.getElementById('closeApproveModal');
        const confirmApprove = document.getElementById('confirmApprove');

        const rejectModal = document.getElementById('rejectModal');
        const closeRejectModal = document.getElementById('closeRejectModal');
        const confirmReject = document.getElementById('confirmReject');
        const rejectReason = document.getElementById('reject_reason');

        // Bắt sự kiện nút duyệt
        document.querySelectorAll('.approve-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                selectedDepartureId = this.getAttribute('data-id');
                approveModal.style.display = 'block';
            });
        });

        // Bắt sự kiện nút từ chối
        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                selectedDepartureId = this.getAttribute('data-id');
                rejectModal.style.display = 'block';
            });
        });

        closeApproveModal.addEventListener('click', function() {
            approveModal.style.display = 'none';
            selectedDepartureId = null;
        });
        closeRejectModal.addEventListener('click', function() {
            rejectModal.style.display = 'none';
            selectedDepartureId = null;
            rejectReason.value = "";
        });

        // Xác nhận duyệt
        confirmApprove.addEventListener('click', function() {
            fetch('ajax/handle_departure_request_expired.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    departure_id: selectedDepartureId,
                    action: 'approve'
                })
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if(data.success) {
                    window.location.reload();
                }
            })
            .catch(err => {
                console.error("Error:", err);
                alert("Lỗi khi duyệt đơn.");
            });
        });

        // Xác nhận từ chối
        confirmReject.addEventListener('click', function() {
            const reason = rejectReason.value.trim();
            if(!reason) {
                alert("Vui lòng nhập lý do từ chối");
                return;
            }
            fetch('ajax/handle_departure_request_expired.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    departure_id: selectedDepartureId,
                    action: 'reject',
                    reason: reason
                })
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if(data.success) {
                    window.location.reload();
                }
            })
            .catch(err => {
                console.error("Error:", err);
                alert("Lỗi khi từ chối đơn.");
            });
        });

        // Đóng modal khi click outside
        window.addEventListener('click', function(e) {
            if(e.target == approveModal) {
                approveModal.style.display = 'none';
            }
            if(e.target == rejectModal) {
                rejectModal.style.display = 'none';
                rejectReason.value = "";
            }
        });
    });
    </script>
</body>
</html>
