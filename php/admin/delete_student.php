<?php
include '../config/db_connect.php';

if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];

    // Kiểm tra sinh viên có tồn tại không
    $sql_check = "SELECT * FROM Students WHERE student_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $student_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows == 0) {
        header("Location: students_list.php?message=Sinh viên không tồn tại.&type=error");
        exit;
    }

    // Xóa sinh viên
    $sql_delete = "DELETE FROM Students WHERE student_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $student_id);

    if ($stmt_delete->execute()) {
        header("Location: students_list.php?message=Xóa sinh viên thành công.");
    } else {
        header("Location: students_list.php?message=Xóa sinh viên thất bại.&type=error");
    }

    $stmt_check->close();
    $stmt_delete->close();
    $conn->close();
} else {
    header("Location: students_list.php?message=ID sinh viên không hợp lệ.&type=error");
    exit;
}
?>
