<?php
include '../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Làm sạch và xác thực dữ liệu đầu vào
    $student_code = trim($_POST['student_code']);
    $full_name    = trim($_POST['full_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);
    $room_id      = isset($_POST['room_id']) && $_POST['room_id'] !== '' ? $_POST['room_id'] : NULL;

    // Kiểm tra các trường bắt buộc
    if (empty($student_code) || empty($full_name) || empty($email)) {
        header("Location: ../add_student.php?message=Vui lòng điền đầy đủ thông tin bắt buộc.&type=error");
        exit;
    }

    // Kiểm tra định dạng email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../add_student.php?message=Định dạng email không hợp lệ.&type=error");
        exit;
    }

    // Kiểm tra mã sinh viên đã tồn tại
    $sql_check = "SELECT * FROM Students WHERE student_code = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $student_code);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        header("Location: ../add_student.php?message=Mã sinh viên đã tồn tại.&type=error");
        exit;
    }

    // Nếu room_id được chọn, kiểm tra phòng có còn chỗ trống
    if ($room_id !== NULL) {
        $sql_room = "
            SELECT r.*, 
                (SELECT COUNT(*) FROM Students s WHERE s.room_id = r.room_id) AS current_occupancy
            FROM Rooms r
            WHERE r.room_id = ?
        ";
        $stmt_room = $conn->prepare($sql_room);
        $stmt_room->bind_param("i", $room_id);
        $stmt_room->execute();
        $result_room = $stmt_room->get_result();

        if ($result_room->num_rows == 0) {
            header("Location: ../add_student.php?message=Phòng không tồn tại.&type=error");
            exit;
        }

        $room = $result_room->fetch_assoc();
        $remaining_capacity = $room['capacity'] - $room['current_occupancy'];

        if ($remaining_capacity <= 0) {
            header("Location: ../add_student.php?message=Phòng đã đầy.&type=error");
            exit;
        }

        // Kiểm tra trạng thái phòng
        if ($room['status'] === 'maintenance') {
            header("Location: ../add_student.php?message=Phòng đang bảo trì, không thể thêm sinh viên vào.&type=error");
            exit;
        }
    }

    // Thêm sinh viên vào cơ sở dữ liệu với room_id (có thể là NULL)
    $sql_insert = "INSERT INTO Students (student_code, full_name, email, phone, room_id) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ssssi", $student_code, $full_name, $email, $phone, $room_id);

    if ($stmt_insert->execute()) {
        // Lấy student_id vừa thêm
        $student_id = $conn->insert_id;

        // Nếu có room_id, cập nhật Room_Status và trạng thái phòng nếu cần
        if ($room_id !== NULL) {
            // Nếu phòng đang ở trạng thái 'available', chuyển thành 'occupied'
            if ($room['status'] === 'available') {
                $sql_update_room = "UPDATE Rooms SET status = 'occupied' WHERE room_id = ?";
                $stmt_update_room = $conn->prepare($sql_update_room);
                $stmt_update_room->bind_param("i", $room_id);
                $stmt_update_room->execute();
                $stmt_update_room->close();
            }

            // Cập nhật bảng Room_Status
            $sql_insert_room_status = "INSERT INTO Room_Status (room_id, student_id, start_date) VALUES (?, ?, ?)";
            $stmt_insert_room_status = $conn->prepare($sql_insert_room_status);
            $start_date = date('Y-m-d');
            $stmt_insert_room_status->bind_param("iis", $room_id, $student_id, $start_date);
            $stmt_insert_room_status->execute();
            $stmt_insert_room_status->close();

            // Gửi thông báo đến sinh viên vừa được thêm vào phòng
            // Lấy user_id từ bảng Users (với username == student_code)
            $sqlUser = "SELECT user_id FROM Users WHERE username = ?";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bind_param("s", $student_code);
            $stmtUser->execute();
            $resultUser = $stmtUser->get_result();
            if ($resultUser->num_rows > 0) {
                $user = $resultUser->fetch_assoc();
                $user_id = $user['user_id'];
                $notif_title = "Cập nhật phòng";
                $notif_message = "Chúc mừng! Bạn đã được thêm vào phòng " . $room['room_code'] . ".";
                $notif_type = "room";
                $sqlNotif = "INSERT INTO Notifications (user_id, title, message, notification_type) VALUES (?, ?, ?, ?)";
                $stmtNotif = $conn->prepare($sqlNotif);
                $stmtNotif->bind_param("isss", $user_id, $notif_title, $notif_message, $notif_type);
                $stmtNotif->execute();
                $stmtNotif->close();
            }
            $stmtUser->close();
        }

        header("Location: ../students_list.php?message=Thêm sinh viên thành công.");
    } else {
        header("Location: ../add_student.php?message=Lỗi khi thêm sinh viên.&type=error");
    }

    $stmt_check->close();
    $stmt_insert->close();
    if (isset($stmt_room)) {
        $stmt_room->close();
    }
    $conn->close();
} else {
    header("Location: ../students_list.php?message=Phương thức không hợp lệ.&type=error");
    exit;
}
?>
