<?php
include '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id    = $_POST['student_id'];
    $student_code  = trim($_POST['student_code']);
    $full_name     = trim($_POST['full_name']);
    $email         = trim($_POST['email']);
    $phone         = trim($_POST['phone']);
    $new_room_id   = isset($_POST['room_id']) && $_POST['room_id'] !== '' ? $_POST['room_id'] : NULL;

    // Kiểm tra các trường bắt buộc
    if (empty($student_code) || empty($full_name) || empty($email)) {
        header("Location: edit_student.php?student_id=$student_id&message=Vui lòng điền đầy đủ thông tin bắt buộc.&type=error");
        exit;
    }

    // Kiểm tra định dạng email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: edit_student.php?student_id=$student_id&message=Định dạng email không hợp lệ.&type=error");
        exit;
    }

    // Kiểm tra sinh viên có tồn tại không
    $sql_check_student = "SELECT * FROM Students WHERE student_id = ?";
    $stmt_check_student = $conn->prepare($sql_check_student);
    $stmt_check_student->bind_param("i", $student_id);
    $stmt_check_student->execute();
    $result_check_student = $stmt_check_student->get_result();

    if ($result_check_student->num_rows == 0) {
        header("Location: students_list.php?message=Sinh viên không tồn tại.&type=error");
        exit;
    }

    $student = $result_check_student->fetch_assoc();
    $current_room_id = $student['room_id'];

    // Kiểm tra mã sinh viên có tồn tại ở sinh viên khác không
    $sql_check_code = "SELECT * FROM Students WHERE student_code = ? AND student_id != ?";
    $stmt_check_code = $conn->prepare($sql_check_code);
    $stmt_check_code->bind_param("si", $student_code, $student_id);
    $stmt_check_code->execute();
    $result_check_code = $stmt_check_code->get_result();

    if ($result_check_code->num_rows > 0) {
        header("Location: edit_student.php?student_id=$student_id&message=Mã sinh viên đã tồn tại ở sinh viên khác.&type=error");
        exit;
    }

    // Nếu room_id thay đổi
    if ($new_room_id != $current_room_id) {
        // Xử lý rời phòng cũ (nếu có)
        if ($current_room_id !== NULL) {
            // Cập nhật end_date trong bảng Room_Status
            $sql_update_room_status = "UPDATE Room_Status SET end_date = ? WHERE student_id = ? AND room_id = ? AND end_date IS NULL";
            $stmt_update_room_status = $conn->prepare($sql_update_room_status);
            $end_date = date('Y-m-d');
            $stmt_update_room_status->bind_param("sii", $end_date, $student_id, $current_room_id);
            $stmt_update_room_status->execute();
            $stmt_update_room_status->close();

            // Kiểm tra số lượng sinh viên còn lại trong phòng cũ
            $sql_check_old_room = "
                SELECT r.*, 
                    (SELECT COUNT(*) FROM Students s WHERE s.room_id = r.room_id AND s.student_id != ?) AS remaining_students
                FROM Rooms r
                WHERE r.room_id = ?
            ";
            $stmt_check_old_room = $conn->prepare($sql_check_old_room);
            $stmt_check_old_room->bind_param("ii", $student_id, $current_room_id);
            $stmt_check_old_room->execute();
            $result_check_old_room = $stmt_check_old_room->get_result();
            $old_room = $result_check_old_room->fetch_assoc();

            if ($old_room['remaining_students'] == 0) {
                // Nếu phòng cũ không còn sinh viên, cập nhật trạng thái phòng thành 'available'
                $sql_update_old_room_status = "UPDATE Rooms SET status = 'available' WHERE room_id = ?";
                $stmt_update_old_room_status = $conn->prepare($sql_update_old_room_status);
                $stmt_update_old_room_status->bind_param("i", $current_room_id);
                $stmt_update_old_room_status->execute();
                $stmt_update_old_room_status->close();
            }

            $stmt_check_old_room->close();
        }

        // Xử lý vào phòng mới (nếu có)
        if ($new_room_id !== NULL) {
            // Kiểm tra phòng mới có tồn tại và còn chỗ trống không
            $sql_new_room = "
                SELECT r.*, 
                    (SELECT COUNT(*) FROM Students s WHERE s.room_id = r.room_id) AS current_occupancy
                FROM Rooms r
                WHERE r.room_id = ?
            ";
            $stmt_new_room = $conn->prepare($sql_new_room);
            $stmt_new_room->bind_param("i", $new_room_id);
            $stmt_new_room->execute();
            $result_new_room = $stmt_new_room->get_result();

            if ($result_new_room->num_rows == 0) {
                header("Location: edit_student.php?student_id=$student_id&message=Phòng mới không tồn tại.&type=error");
                exit;
            }

            $new_room = $result_new_room->fetch_assoc();
            $remaining_capacity = $new_room['capacity'] - $new_room['current_occupancy'];

            // Nếu phòng mới là phòng hiện tại của sinh viên, đã trừ sinh viên này nên cần +1 vào remaining_capacity
            if ($new_room['room_id'] == $current_room_id) {
                $remaining_capacity +=1;
            }

            if ($remaining_capacity <= 0) {
                header("Location: edit_student.php?student_id=$student_id&message=Phòng mới đã đầy.&type=error");
                exit;
            }

            // Kiểm tra trạng thái phòng mới
            if ($new_room['status'] === 'maintenance') {
                header("Location: edit_student.php?student_id=$student_id&message=Phòng mới đang bảo trì, không thể chuyển vào.&type=error");
                exit;
            }

            // Cập nhật bảng Room_Status
            $sql_insert_room_status = "INSERT INTO Room_Status (room_id, student_id, start_date) VALUES (?, ?, ?)";
            $stmt_insert_room_status = $conn->prepare($sql_insert_room_status);
            $start_date = date('Y-m-d');
            $stmt_insert_room_status->bind_param("iis", $new_room_id, $student_id, $start_date);
            $stmt_insert_room_status->execute();
            $stmt_insert_room_status->close();

            // Cập nhật trạng thái phòng mới nếu cần
            if ($new_room['status'] === 'available') {
                $sql_update_new_room_status = "UPDATE Rooms SET status = 'occupied' WHERE room_id = ?";
                $stmt_update_new_room_status = $conn->prepare($sql_update_new_room_status);
                $stmt_update_new_room_status->bind_param("i", $new_room_id);
                $stmt_update_new_room_status->execute();
                $stmt_update_new_room_status->close();
            }

            $stmt_new_room->close();
        }
    }

    // Cập nhật thông tin sinh viên
    $sql_update_student = "UPDATE Students SET student_code = ?, full_name = ?, email = ?, phone = ?, room_id = ? WHERE student_id = ?";
    $stmt_update_student = $conn->prepare($sql_update_student);
    $stmt_update_student->bind_param("ssssii", $student_code, $full_name, $email, $phone, $new_room_id, $student_id);

    if ($stmt_update_student->execute()) {
        header("Location: students_list.php?message=Cập nhật sinh viên thành công.");
    } else {
        header("Location: edit_student.php?student_id=$student_id&message=Lỗi khi cập nhật sinh viên.&type=error");
    }

    // Đóng các statement và kết nối
    $stmt_check_student->close();
    $stmt_check_code->close();
    $stmt_update_student->close();
    $conn->close();
} else {
    header("Location: students_list.php?message=Phương thức không hợp lệ.&type=error");
    exit;
}
?>
