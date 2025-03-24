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

// Truy vấn danh sách đơn đăng ký và lấy cột 'documents'
$sql = "SELECT a.application_id, a.desired_start_date, a.desired_end_date, a.deposit, a.documents, a.status, a.created_at,
               s.student_code, s.full_name
        FROM Applications a
        JOIN Students s ON a.student_id = s.student_id
        ORDER BY a.created_at DESC";
$result = $conn->query($sql);
$applications = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Giải mã JSON documents
        $row['documents'] = json_decode($row['documents'], true);
        $applications[] = $row;
    }
}
$conn->close();

// Define the correct base URL for file downloads
$baseURL = '/php/student/'; // Adjust this if your structure is different
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Đơn đăng ký ở</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/admin_registration_requests.css">
    <style>
        .file-links {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .file-links a {
            color: #007bff;
            text-decoration: none;
            word-break: break-all;
        }
        .file-links a:hover {
            text-decoration: underline;
        }
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
                                <th>Họ tên SV</th>
                                <th>Mã SV</th>
                                <th>Ngày gửi</th>
                                <th>Ngày nhận phòng</th>
                                <th>Ngày kết thúc</th>
                                <th>Tiền cọc</th>
                                <th style="  width: 150px !important;
                                white-space: nowrap !important;
                                overflow: hidden !important;
                                text-overflow: ellipsis !important;">
                                Tài liệu</th> <!-- Cột mới: Tài liệu -->
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
                                            if (is_array($app['documents'])) {
                                                echo '<div class="file-links">';
                                                foreach ($app['documents'] as $documentPath) {
                                                    $fileName = basename($documentPath);
                                                    // Construct the correct file URL by prepending the base URL
                                                    $fileURL = $baseURL . htmlspecialchars($documentPath);
                                                    echo '<a href="' . $fileURL . '" target="_blank">' . htmlspecialchars($fileName) . '</a>';
                                                }
                                                echo '</div>';
                                            } else {
                                                echo 'Không có';
                                            }
                                        ?>
                                    </td>
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
                    // Kết nối database
                    include '../config/db_connect.php';

                    // Truy vấn danh sách phòng kèm số lượng sinh viên hiện tại
                    $sql_rooms = "
                        SELECT r.room_id, r.building, r.room_number, r.capacity, r.status,
                            (SELECT COUNT(*) FROM Students s WHERE s.room_id = r.room_id) AS current_occupancy
                        FROM Rooms r
                        WHERE r.status IN ('available', 'occupied') OR r.room_id = ?
                        ORDER BY r.building, r.room_number
                    ";

                    // Chuẩn bị truy vấn
                    $stmt_rooms = $conn->prepare($sql_rooms);
                    $stmt_rooms->bind_param("i", $student['room_id']);
                    $stmt_rooms->execute();
                    $result_rooms = $stmt_rooms->get_result();

                    $rooms = [];

                    while ($row = $result_rooms->fetch_assoc()) {
                        // Tính số chỗ trống còn lại
                        $remaining_capacity = $row['capacity'] - $row['current_occupancy'];

                        // Nếu là phòng hiện tại của sinh viên, cho phép hiển thị dù đã đầy
                        if ($row['room_id'] == $student['room_id'] || $remaining_capacity > 0) {
                            $rooms[] = [
                                'room_id' => $row['room_id'],
                                'building' => $row['building'],
                                'room_number' => $row['room_number'],
                                'remaining_capacity' => $remaining_capacity + ($row['room_id'] == $student['room_id'] ? 1 : 0)
                            ];
                        }
                    }

                    // Đóng statement
                    $stmt_rooms->close();
                    $conn->close();

                    // Hiển thị danh sách phòng dưới dạng <option>
                    foreach ($rooms as $room):
                        $selected = ($student['room_id'] == $room['room_id']) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($room['room_id']); ?>" <?php echo $selected; ?>>
                            <?php echo "Tòa " . htmlspecialchars($room['building']) . " - Phòng " . htmlspecialchars($room['room_number']) . " (Còn " . $room['remaining_capacity'] . " chỗ)"; ?>
                        </option>
                    <?php endforeach; ?>
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

    <?php include 'layout/js.php'; ?>
    <script src="../../assets/js/admin_registration_requests.js"></script>
</body>
</html>
