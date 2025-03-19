<?php
include '../config/db_connect.php';

if (isset($_GET['room_id'])) {
    $room_id = $_GET['room_id'];

    // Lấy thông tin phòng
    $sql_room = "SELECT * FROM Rooms WHERE room_id = ?";
    $stmt_room = $conn->prepare($sql_room);
    $stmt_room->bind_param("i", $room_id);
    $stmt_room->execute();
    $room = $stmt_room->get_result()->fetch_assoc();

    // Phân trang
    $limit = 10; // Số lượng hóa đơn mỗi trang
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Đếm tổng số hóa đơn để tính tổng số trang
    $sql_count = "SELECT COUNT(*) AS total FROM Payments WHERE room_id = ?";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $room_id);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total_records = $result_count->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $limit);

    // Lấy danh sách hóa đơn theo trang
    $sql_payments_paged = "SELECT * FROM Payments WHERE room_id = ? ORDER BY payment_date DESC LIMIT ?, ?";
    $stmt_payments_paged = $conn->prepare($sql_payments_paged);
    $stmt_payments_paged->bind_param("iii", $room_id, $offset, $limit);
    $stmt_payments_paged->execute();
    $result_payments_paged = $stmt_payments_paged->get_result();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh Sách Hóa Đơn</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="payments-history-container">
                <h2>Danh Sách Hóa Đơn - Tòa <?php echo htmlspecialchars($room['building']); ?> Phòng <?php echo htmlspecialchars($room['room_number']); ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th>Tháng</th>
                            <th>Số điện (kWh)</th>
                            <th>Số nước (m³)</th>
                            <th>Tổng tiền (VNĐ)</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_payments_paged->num_rows > 0): ?>
                            <?php while ($payment = $result_payments_paged->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('m/Y', strtotime($payment['payment_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($payment['electricity_usage']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['water_usage']); ?></td>
                                    <td><?php echo number_format($payment['total_amount'], 0, ',', '.'); ?></td>
                                    <td><?php echo $payment['payment_status'] == 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">Không có hóa đơn nào được tìm thấy.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Hiển thị phân trang -->
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?room_id=<?php echo $room_id; ?>&page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                
                <a href="payments_list.php" class="back-btn">Quay lại</a>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php include 'layout/js.php'; ?>
</body>
</html>
