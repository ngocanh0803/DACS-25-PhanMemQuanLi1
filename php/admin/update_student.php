<?php
include '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $sql = "UPDATE Students SET full_name = ?, email = ?, phone = ?, address = ? WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $full_name, $email, $phone, $address, $student_id);

    if ($stmt->execute()) {
        // Lấy thông tin sinh viên vừa cập nhật để lấy student_code
        $sql_student = "SELECT student_code FROM Students WHERE student_id = ?";
        $stmt_student = $conn->prepare($sql_student);
        $stmt_student->bind_param("i", $student_id);
        $stmt_student->execute();
        $result_student = $stmt_student->get_result();
        if ($result_student->num_rows > 0) {
            $student = $result_student->fetch_assoc();
            $student_code = $student['student_code'];

            // Lấy user_id từ bảng Users (với username = student_code)
            $sql_user = "SELECT user_id FROM Users WHERE username = ?";
            $stmt_user = $conn->prepare($sql_user);
            $stmt_user->bind_param("s", $student_code);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            if ($result_user->num_rows > 0) {
                $user = $result_user->fetch_assoc();
                $user_id = $user['user_id'];

                // Tạo thông báo
                $title = "Cập nhật thông tin cá nhân";
                $message = "Thông tin cá nhân của bạn đã được cập nhật thành công.";
                $notification_type = "general";

                $sql_notif = "INSERT INTO Notifications (user_id, title, message, notification_type) VALUES (?, ?, ?, ?)";
                $stmt_notif = $conn->prepare($sql_notif);
                $stmt_notif->bind_param("isss", $user_id, $title, $message, $notification_type);
                $stmt_notif->execute();
                $stmt_notif->close();
            }
            $stmt_user->close();
        }
        $stmt_student->close();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật dữ liệu']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
}
?>
