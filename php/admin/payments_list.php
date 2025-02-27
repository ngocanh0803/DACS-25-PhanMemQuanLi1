<?php
// session_start();
include '../config/db_connect.php';

// Kiểm tra quyền truy cập (nếu cần)
// if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['manager', 'student_manager', 'accountant', 'admin'])) {
//     $_SESSION['error'] = "Bạn không có quyền truy cập.";
//     header('Location: login.php');
//     exit();
// }

// Lấy danh sách phòng
$sql_rooms = "SELECT * FROM Rooms ORDER BY building, floor, room_number";
$result_rooms = $conn->query($sql_rooms);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Thanh Toán</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/payments.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="payments-list-container">
                <h2>Quản lý Thanh Toán</h2>
                <table>
                    <!-- Thêm cột trạng thái phòng -->
                    <thead>
                        <tr>
                            <th>Tòa nhà</th>
                            <th>Tầng</th>
                            <th>Phòng</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($room = $result_rooms->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($room['building']); ?></td>
                                <td><?php echo htmlspecialchars($room['floor']); ?></td>
                                <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                <td><?php echo htmlspecialchars($room['status'] == 'occupied' ? 'Đang có người ở' : ($room['status'] == 'available' ? 'Phòng trống' : 'Đang bảo trì')); ?></td>
                                <td>
                                    <?php if ($room['status'] == 'occupied'): ?>
                                        <a href="add_payment.php?room_id=<?php echo $room['room_id']; ?>" class="add-btn">Tạo hóa đơn</a>
                                    <?php else: ?>
                                        <span class="disabled-btn">Tạo hóa đơn</span>
                                    <?php endif; ?>
                                    <a href="view_payments.php?room_id=<?php echo $room['room_id']; ?>" class="view-btn">Xem hóa đơn</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/search.js"></script>
</body>
</html>
