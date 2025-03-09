<?php
session_start();
include '../config/db_connect.php';

// Kiểm tra quyền truy cập
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['manager', 'accountant', 'admin', 'student_manager'])) {
    $_SESSION['error'] = "Bạn không có quyền truy cập.";
    header('Location: login.php');
    exit();
}

if (isset($_GET['payment_id']) && isset($_GET['room_id'])) {
    $payment_id = filter_input(INPUT_GET, 'payment_id', FILTER_VALIDATE_INT);
    $room_id = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT);
    $status = ($_GET['status'] === 'paid') ? 'paid' : 'unpaid';

    if ($payment_id === false || $room_id === false) {
        $_SESSION['error'] = "Thông tin không hợp lệ.";
        header('Location: view_payments.php?room_id=' . urlencode($room_id));
        exit();
    }

    // Cập nhật trạng thái và payment_date tùy theo trạng thái thanh toán
    if ($status === 'paid') {
        $sql_update = "UPDATE Payments SET payment_status = ?, payment_date = NOW() WHERE payment_id = ?";
    } else {
        $sql_update = "UPDATE Payments SET payment_status = ?, payment_date = NULL WHERE payment_id = ?";
    }
    
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $status, $payment_id);

    if ($stmt_update->execute()) {
        $_SESSION['success'] = "Cập nhật trạng thái hóa đơn thành công.";
    } else {
        $_SESSION['error'] = "Lỗi khi cập nhật trạng thái hóa đơn.";
    }

    $stmt_update->close();
    header('Location: view_payments.php?room_id=' . urlencode($room_id));
    exit();
} else {
    $_SESSION['error'] = "Thông tin không hợp lệ.";
    header('Location: payments_list.php');
    exit();
}
?>
