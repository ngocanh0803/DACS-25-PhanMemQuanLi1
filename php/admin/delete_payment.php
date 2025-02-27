<?php
session_start();
include '../config/db_connect.php';

// Kiểm tra quyền truy cập
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['manager', 'accountant'])) {
    $_SESSION['error'] = "Bạn không có quyền truy cập.";
    header('Location: login.php');
    exit();
}

if (isset($_GET['payment_id']) && isset($_GET['room_id'])) {
    $payment_id = filter_input(INPUT_GET, 'payment_id', FILTER_VALIDATE_INT);
    $room_id = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT);

    if ($payment_id === false || $room_id === false) {
        $_SESSION['error'] = "Thông tin không hợp lệ.";
        header('Location: view_payments.php?room_id=' . urlencode($room_id));
        exit();
    }

    // Xóa hóa đơn
    $sql_delete = "DELETE FROM Payments WHERE payment_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $payment_id);

    if ($stmt_delete->execute()) {
        $_SESSION['success'] = "Xóa hóa đơn thành công.";
    } else {
        $_SESSION['error'] = "Lỗi khi xóa hóa đơn.";
    }

    $stmt_delete->close();
    header('Location: view_payments.php?room_id=' . urlencode($room_id));
    exit();
} else {
    $_SESSION['error'] = "Thông tin không hợp lệ.";
    header('Location: payments_list.php');
    exit();
}
?>
