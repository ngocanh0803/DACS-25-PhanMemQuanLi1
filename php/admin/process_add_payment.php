<?php
session_start();
include '../config/db_connect.php';

// Kiểm tra quyền truy cập
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['manager', 'student_manager', 'accountant', 'admin'])) {
    $_SESSION['error'] = "Bạn không có quyền truy cập.";
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Làm sạch và xác thực dữ liệu đầu vào
    $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
    $month_input = filter_input(INPUT_POST, 'month', FILTER_SANITIZE_STRING);
    $electricity_usage = filter_input(INPUT_POST, 'electricity_usage', FILTER_VALIDATE_FLOAT);
    $water_usage = filter_input(INPUT_POST, 'water_usage', FILTER_VALIDATE_FLOAT);

    // Kiểm tra dữ liệu đầu vào
    if ($room_id === false || empty($month_input) || $electricity_usage === false || $water_usage === false) {
        $_SESSION['error'] = "Dữ liệu không hợp lệ.";
        header('Location: add_payment.php?room_id=' . urlencode($room_id));
        exit();
    }

    // Chuyển đổi tháng thành định dạng chuẩn (ngày đầu tiên của tháng)
    $month = date('Y-m-01', strtotime($month_input));
    $month_number = date('m', strtotime($month_input)); // Lấy số tháng
    $year_number = date('Y', strtotime($month_input)); // Lấy năm

    // Lấy thông tin phòng
    $sql_room = "SELECT * FROM Rooms WHERE room_id = ?";
    $stmt_room = $conn->prepare($sql_room);
    $stmt_room->bind_param("i", $room_id);
    $stmt_room->execute();
    $result_room = $stmt_room->get_result();
    $room = $result_room->fetch_assoc();

    if (!$room) {
        $_SESSION['error'] = "Phòng không tồn tại.";
        header('Location: add_payment.php?room_id=' . urlencode($room_id));
        exit();
    }

    // Kiểm tra trạng thái phòng
    if ($room['status'] != 'occupied') {
        $_SESSION['error'] = "Phòng không có người ở hoặc đang bảo trì. Không thể tạo hóa đơn.";
        header('Location: add_payment.php?room_id=' . urlencode($room_id));
        exit();
    }

    // Kiểm tra xem hóa đơn cho phòng và tháng này đã tồn tại chưa
    $sql_check = "SELECT COUNT(*) as count FROM Payments WHERE room_id = ? AND DATE_FORMAT(payment_date, '%Y-%m-01') = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("is", $room_id, $month);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();

    if ($row_check['count'] > 0) {
        $_SESSION['error'] = "Hóa đơn cho tháng này đã tồn tại.";
        header('Location: add_payment.php?room_id=' . urlencode($room_id));
        exit();
    }

    // Định nghĩa hệ số giá
    $electricity_rate = 3000; // VNĐ/kWh
    $water_rate = 15000; // VNĐ/m³
    $room_price = $room['price']; // Lấy từ bảng Rooms

    // Tính toán chi phí
    $electricity_cost = $electricity_usage * $electricity_rate;
    $water_cost = $water_usage * $water_rate;
    $total_amount = $electricity_cost + $water_cost + $room_price;

    // Tạo mã hóa đơn
    $payment_code = 'HD' . $room_id . '_' . $month_number . '_' . $year_number;

    // Thêm hóa đơn vào bảng Payments
    // $sql_insert = "INSERT INTO Payments (payment_code, room_id, electricity_usage, water_usage, total_amount, payment_status) VALUES (?, ?, ?, ?, ?, 'unpaid')";
    $sql_insert = "INSERT INTO Payments (payment_code, room_id, electricity_usage, water_usage, total_amount, payment_status, payment_date) VALUES (?, ?, ?, ?, ?, 'unpaid', ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    // $stmt_insert->bind_param("siidd", $payment_code, $room_id, $electricity_usage, $water_usage, $total_amount);
    $stmt_insert->bind_param("siidds", $payment_code, $room_id, $electricity_usage, $water_usage, $total_amount, $month);
    
    if ($stmt_insert->execute()) {
        // Lưu thông báo cho các sinh viên trong phòng
        $message = "Hóa đơn tháng $month_input cho phòng " . $room['room_code'] . " đã được tạo. Tổng tiền: " . number_format($total_amount, 2, ',', '.') . " VNĐ. Vui lòng kiểm tra và thanh toán đúng hạn.";
        $title = "Hóa đơn mới";
        $notification_type = "payment";

        // Lấy danh sách sinh viên trong phòng
        $sqlStudents = "SELECT s.student_code FROM Students s WHERE s.room_id = ?";
        $stmtStudents = $conn->prepare($sqlStudents);
        $stmtStudents->bind_param("i", $room_id);
        $stmtStudents->execute();
        $resultStudents = $stmtStudents->get_result();
        while ($student = $resultStudents->fetch_assoc()) {
            $student_code = $student['student_code'];
            // Lấy user_id từ bảng Users (với username == student_code)
            $sqlUser = "SELECT user_id FROM Users WHERE username = ?";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bind_param("s", $student_code);
            $stmtUser->execute();
            $resultUser = $stmtUser->get_result();
            if ($resultUser->num_rows > 0) {
                $user = $resultUser->fetch_assoc();
                $user_id = $user['user_id'];
                // INSERT thông báo
                $sqlNotif = "INSERT INTO Notifications (user_id, title, message, notification_type) VALUES (?, ?, ?, ?)";
                $stmtNotif = $conn->prepare($sqlNotif);
                $stmtNotif->bind_param("isss", $user_id, $title, $message, $notification_type);
                $stmtNotif->execute();
                $stmtNotif->close();
            }
            $stmtUser->close();
        }
        $stmtStudents->close();

        $_SESSION['success'] = "Tạo hóa đơn thành công và thông báo đã được gửi đến sinh viên.";
        header('Location: view_payments.php?room_id=' . urlencode($room_id));
    } else {
        $_SESSION['error'] = "Lỗi khi tạo hóa đơn.";
        header('Location: add_payment.php?room_id=' . urlencode($room_id));
    }

    // Đóng các kết nối
    $stmt_check->close();
    $stmt_room->close();
    $stmt_insert->close();
    $conn->close();
} else {
    $_SESSION['error'] = "Phương thức không hợp lệ.";
    header('Location: payments_list.php');
    exit();
}
?>
