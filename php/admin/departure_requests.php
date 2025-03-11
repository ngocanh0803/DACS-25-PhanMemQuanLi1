<?php
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'manager', 'student_manager', 'accountant'])) {
    header("Location: ../php/login.php");
    exit();
}

include '../config/db_connect.php';

// Lấy danh sách đơn xin rời phòng từ bảng Departure_Requests, kết hợp thông tin sinh viên
$sql = "SELECT dr.departure_id, dr.request_date, dr.reason, dr.status, dr.processed_date,
               s.student_code, s.full_name, dr.documents
        FROM Departure_Requests dr
        JOIN Students s ON dr.student_id = s.student_id
        ORDER BY dr.request_date DESC";
$result = $conn->query($sql);
$departures = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()){
        $departures[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn xin rời phòng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- CSS chung của admin -->
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
        /* .container1 { padding: 20px; } */
        h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: center; }
        th { background-color: #007bff; color: #fff; }
        .action-btn { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; margin: 2px; }
        .approve-btn { background-color: #28a745; color: #fff; }
        .reject-btn { background-color: #dc3545; color: #fff; }
        /* Modal styles */
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
        .form-group input, .form-group textarea {
            padding: 8px; font-size: 16px; border: 1px solid #ccc; border-radius: 4px;
        }
        .btn-confirm { padding: 8px 16px; background-color: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
<?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="container1">
                <h2>Danh sách đơn xin rời phòng</h2>
                <?php if(count($departures) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Sinh viên</th>
                                <th>Mã SV</th>
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
                                    <td><?php echo htmlspecialchars($dep['request_date']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($dep['reason'])); ?></td>
                                    <td>
                                        <?php 
                                            switch($dep['status']){
                                                case 'pending': echo 'Chờ xử lý'; break;
                                                case 'approved': echo 'Đã duyệt'; break;
                                                case 'rejected': echo 'Bị từ chối'; break;
                                                default: echo $dep['status'];
                                            }
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
                    <p>Không có đơn xin rời phòng nào.</p>
                <?php endif; ?>
            </div>
        </main>

    <!-- Modal duyệt đơn -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeApproveModal">&times;</span>
            <h3>Duyệt đơn xin rời phòng</h3>
            <div class="form-group">
                <label for="assign_room">Chọn phòng bàn giao:</label>
                <select id="assign_room">
                    <option value="">Chọn phòng</option>
                    <?php
                    // Lấy danh sách phòng có trạng thái 'occupied' (hoặc theo yêu cầu)
                    include '../config/db_connect.php';
                    $sqlRooms = "SELECT room_id, room_code FROM Rooms WHERE status = 'occupied'";
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

    <!-- Modal từ chối đơn -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeRejectModal">&times;</span>
            <h3>Từ chối đơn xin rời phòng</h3>
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
        // Xử lý modal duyệt và từ chối
        document.addEventListener('DOMContentLoaded', function() {
            let selectedDepartureId = null;

            const approveModal = document.getElementById('approveModal');
            const rejectModal = document.getElementById('rejectModal');

            const closeApproveModal = document.getElementById('closeApproveModal');
            const closeRejectModal = document.getElementById('closeRejectModal');

            const confirmApprove = document.getElementById('confirmApprove');
            const confirmReject = document.getElementById('confirmReject');

            const assignRoomSelect = document.getElementById('assign_room');
            const rejectReason = document.getElementById('reject_reason');

            // Khi nhấn nút duyệt đơn
            document.querySelectorAll('.approve-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    selectedDepartureId = this.getAttribute('data-id');
                    approveModal.style.display = 'block';
                });
            });

            // Khi nhấn nút từ chối đơn
            document.querySelectorAll('.reject-btn').forEach(function(btn) {
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

            // Xử lý duyệt đơn
            confirmApprove.addEventListener('click', function() {
                const room_id = assignRoomSelect.value;
                if (!room_id) {
                    alert("Vui lòng chọn phòng bàn giao.");
                    return;
                }
                fetch('ajax/handle_departure_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        departure_id: selectedDepartureId,
                        action: 'approve',
                        room_id: room_id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
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
                fetch('ajax/handle_departure_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        departure_id: selectedDepartureId,
                        action: 'reject',
                        reason: reason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error("Lỗi từ chối đơn:", error);
                    alert("Lỗi khi xử lý đơn.");
                });
            });

            window.addEventListener('click', function(e) {
                if (e.target == approveModal) {
                    approveModal.style.display = 'none';
                    selectedDepartureId = null;
                }
                if (e.target == rejectModal) {
                    rejectModal.style.display = 'none';
                    selectedDepartureId = null;
                    rejectReason.value = "";
                }
            });
        });
    </script>
</body>
</html>
