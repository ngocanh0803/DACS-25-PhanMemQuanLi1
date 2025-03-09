<?php
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'manager', 'student_manager', 'accountant'])) {
    header("Location: ../php/login.php");
    exit();
}

include '../config/db_connect.php';

// Truy vấn danh sách đơn đăng ký từ bảng Applications, kết hợp thông tin sinh viên
$sql = "SELECT a.application_id, a.desired_start_date, a.desired_end_date, a.deposit, a.documents, a.status, a.created_at,
               s.student_code, s.full_name
        FROM Applications a
        JOIN Students s ON a.student_id = s.student_id
        ORDER BY a.created_at DESC";
$result = $conn->query($sql);
$applications = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $applications[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Đơn đăng ký ở</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container1 { padding: 20px; }
        h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: center; }
        th { background-color: #007bff; color: #fff; }
        .action-btn { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; }
        .approve-btn { background-color: #28a745; color: #fff; }
        .reject-btn { background-color: #dc3545; color: #fff; }
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
        .form-group select, .form-group input, .form-group textarea { padding: 8px; font-size: 16px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-confirm { padding: 8px 16px; background-color: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
<?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="container1">
                <h2>Danh sách Đơn đăng ký ở</h2>
                <?php if(count($applications) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Họ tên sinh viên</th>
                                <th>Mã SV</th>
                                <th>Ngày gửi</th>
                                <th>Ngày nhận phòng</th>
                                <th>Ngày kết thúc</th>
                                <th>Tiền đặt cọc</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($applications as $app): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($app['application_id']); ?></td>
                                    <td><?php echo htmlspecialchars($app['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($app['student_code']); ?></td>
                                    <td><?php echo htmlspecialchars($app['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($app['desired_start_date']); ?></td>
                                    <td><?php echo htmlspecialchars($app['desired_end_date']); ?></td>
                                    <td><?php echo number_format($app['deposit'], 2); ?></td>
                                    <td>
                                        <?php 
                                            if($app['status'] == 'pending') echo 'Chờ duyệt';
                                            elseif($app['status'] == 'approved') echo 'Đã duyệt';
                                            elseif($app['status'] == 'rejected') echo 'Bị từ chối';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if($app['status'] == 'pending'): ?>
                                            <button class="action-btn approve-btn" data-id="<?php echo $app['application_id']; ?>">Duyệt</button>
                                            <button class="action-btn reject-btn" data-id="<?php echo $app['application_id']; ?>">Từ chối</button>
                                        <?php else: ?>
                                            --
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Không có đơn đăng ký nào.</p>
                <?php endif; ?>
            </div>
        </main>


    <!-- Modal duyệt đơn (Approve Modal) -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeApproveModal">&times;</span>
            <h3>Duyệt đơn đăng ký</h3>
            <div class="form-group">
                <label for="assign_room">Chọn phòng:</label>
                <select id="assign_room">
                    <option value="">Chọn phòng</option>
                    <?php
                    // Lấy danh sách phòng có trạng thái available từ bảng Rooms
                    include '../config/db_connect.php';
                    $sqlRooms = "SELECT room_id, room_code FROM Rooms WHERE status = 'available'";
                    $resultRooms = $conn->query($sqlRooms);
                    if ($resultRooms && $resultRooms->num_rows > 0) {
                        while ($room = $resultRooms->fetch_assoc()) {
                            echo "<option value='{$room['room_id']}'>{$room['room_code']}</option>";
                        }
                    }
                    $conn->close();
                    ?>
                </select>
            </div>
            <button class="btn-confirm" id="confirmApprove">Xác nhận duyệt</button>
        </div>
    </div>

    <!-- Modal từ chối đơn (Reject Modal) -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeRejectModal">&times;</span>
            <h3>Từ chối đơn đăng ký</h3>
            <div class="form-group">
                <label for="reject_reason">Lý do từ chối:</label>
                <textarea id="reject_reason" rows="4" placeholder="Nhập lý do từ chối"></textarea>
            </div>
            <button class="btn-confirm" id="confirmReject">Xác nhận từ chối</button>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/search.js"></script>
    <script>
        // JavaScript xử lý modal duyệt/từ chối
        document.addEventListener('DOMContentLoaded', function() {
            let selectedApplicationId = null;

            // Approve Modal
            const approveModal = document.getElementById('approveModal');
            const closeApproveModal = document.getElementById('closeApproveModal');
            const confirmApprove = document.getElementById('confirmApprove');
            const assignRoomSelect = document.getElementById('assign_room');

            // Reject Modal
            const rejectModal = document.getElementById('rejectModal');
            const closeRejectModal = document.getElementById('closeRejectModal');
            const confirmReject = document.getElementById('confirmReject');
            const rejectReason = document.getElementById('reject_reason');

            // Khi nhấn nút duyệt đơn
            document.querySelectorAll('.approve-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    selectedApplicationId = this.getAttribute('data-id');
                    // Mở modal duyệt
                    approveModal.style.display = 'block';
                });
            });

            // Khi nhấn nút từ chối đơn
            document.querySelectorAll('.reject-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    selectedApplicationId = this.getAttribute('data-id');
                    // Mở modal từ chối
                    rejectModal.style.display = 'block';
                });
            });

            closeApproveModal.addEventListener('click', function() {
                approveModal.style.display = 'none';
                selectedApplicationId = null;
            });
            closeRejectModal.addEventListener('click', function() {
                rejectModal.style.display = 'none';
                selectedApplicationId = null;
                rejectReason.value = "";
            });

            // Xử lý duyệt đơn
            confirmApprove.addEventListener('click', function() {
                const room_id = assignRoomSelect.value;
                if (!room_id) {
                    alert("Vui lòng chọn phòng.");
                    return;
                }
                // Gửi yêu cầu duyệt qua AJAX
                fetch('ajax/handle_registration_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        application_id: selectedApplicationId,
                        action: 'approve',
                        room_id: room_id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if(data.success) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error("Lỗi duyệt đơn:", error);
                    alert("Lỗi khi xử lý đơn.");
                });
            });

            // Xử lý từ chối đơn
            confirmReject.addEventListener('click', function() {
                const reason = rejectReason.value.trim();
                if (!reason) {
                    alert("Vui lòng nhập lý do từ chối.");
                    return;
                }
                fetch('ajax/handle_registration_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        application_id: selectedApplicationId,
                        action: 'reject',
                        reason: reason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if(data.success) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error("Lỗi từ chối đơn:", error);
                    alert("Lỗi khi xử lý đơn.");
                });
            });

            // Đóng modal khi click ngoài nội dung modal
            window.addEventListener('click', function(e) {
                if(e.target == approveModal) {
                    approveModal.style.display = 'none';
                    selectedApplicationId = null;
                }
                if(e.target == rejectModal) {
                    rejectModal.style.display = 'none';
                    selectedApplicationId = null;
                    rejectReason.value = "";
                }
            });
        });
    </script>
</body>
</html>
