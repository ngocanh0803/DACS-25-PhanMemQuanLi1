<?php
include 'db_connect.php';

if (isset($_GET['payment_id'])) {
    $payment_id = filter_input(INPUT_GET, 'payment_id', FILTER_VALIDATE_INT);
    if ($payment_id === false) {
        $_SESSION['error'] = "ID hóa đơn không hợp lệ.";
        header('Location: view_payments.php?room_id=' . urlencode($_GET['room_id']));
        exit();
    }

    // Truy vấn hóa đơn chi tiết
    $sql_payment = "SELECT p.*, r.building, r.room_number FROM Payments p JOIN Rooms r ON p.room_id = r.room_id WHERE p.payment_id = ?";
    $stmt_payment = $conn->prepare($sql_payment);
    $stmt_payment->bind_param("i", $payment_id);
    $stmt_payment->execute();
    $result_payment = $stmt_payment->get_result();
    $payment = $result_payment->fetch_assoc();

    if (!$payment) {
        $_SESSION['error'] = "Hóa đơn không tồn tại.";
        header('Location: view_payments.php?room_id=' . urlencode($_GET['room_id']));
        exit();
    }
} else {
    $_SESSION['error'] = "Không tìm thấy ID hóa đơn.";
    header('Location: payments_list.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi Tiết Hóa Đơn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assest/css/main.css">
    <link rel="stylesheet" href="../assest/css/payments.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container1">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="invoice-detail-container">
                <h2>Chi Tiết Hóa Đơn - <?php echo htmlspecialchars($payment['payment_code']); ?></h2>
                <?php
                if (isset($_SESSION['error'])) {
                    echo '<div class="alert alert-danger">'.htmlspecialchars($_SESSION['error']).'</div>';
                    unset($_SESSION['error']);
                }
                ?>
                <div class="mb-3">
                    <p><strong>Tòa nhà:</strong> <?php echo htmlspecialchars($payment['building']); ?></p>
                    <p><strong>Phòng:</strong> <?php echo htmlspecialchars($payment['room_number']); ?></p>
                    <p><strong>Tháng:</strong> <?php echo date('m/Y', strtotime($payment['payment_date'])); ?></p>
                </div>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Số điện (kWh)</th>
                            <th>Số nước (m³)</th>
                            <th>Tổng tiền (VNĐ)</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['electricity_usage']); ?></td>
                            <td><?php echo htmlspecialchars($payment['water_usage']); ?></td>
                            <td><?php echo number_format($payment['total_amount'], 0, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($payment['payment_status'] == 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán'); ?></td>
                        </tr>
                    </tbody>
                </table>
                <a href="view_payments.php?room_id=<?php echo htmlspecialchars($payment['room_id']); ?>" class="btn btn-secondary">Quay lại</a>
            </div>
        </main>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="../assest/js/main.js"></script>
    <script src="../assest/js/search.js"></script>
    <script>
        <?php if (isset($_SESSION['success'])): ?>
            toastr.success("<?php echo htmlspecialchars($_SESSION['success']); ?>");
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            toastr.error("<?php echo htmlspecialchars($_SESSION['error']); ?>");
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
