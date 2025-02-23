<?php
// process_terminate_contract.php
include 'db_connect.php';
session_start();

// (Tùy chọn) Kiểm tra quyền
// if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['manager','admin','student_manager','accountant'])) {
//     header("Location: login.php?message=Bạn không có quyền&type=error");
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    // Nếu không phải GET, chuyển hướng về list
    header("Location: contracts_list.php?message=Phương thức không hợp lệ&type=error");
    exit;
}

$contract_id = intval($_GET['contract_id'] ?? 0);
if ($contract_id <= 0) {
    // Thiếu/không hợp lệ contract_id
    header("Location: contracts_list.php?message=Thiếu contract_id&type=error");
    exit;
}

$conn->begin_transaction();

try {
    // 1) Lấy thông tin hợp đồng => student_id, room_id
    $sql_contract = "SELECT student_id, room_id FROM Contracts WHERE contract_id = ?";
    $stmt = $conn->prepare($sql_contract);
    $stmt->bind_param("i", $contract_id);
    $stmt->execute();
    $resC = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$resC) {
        throw new Exception("Không tìm thấy hợp đồng ID " . $contract_id);
    }

    $student_id = intval($resC['student_id']);
    $room_id    = intval($resC['room_id']);

    // 2) Cập nhật status = 'terminated', set end_date = CURDATE() nếu đang NULL
    $sql_update = "
        UPDATE Contracts
        SET status = 'terminated',
            end_date = IF(end_date IS NULL, CURDATE(), end_date)
        WHERE contract_id = ?
    ";
    $stmt_up = $conn->prepare($sql_update);
    $stmt_up->bind_param("i", $contract_id);
    if (!$stmt_up->execute()) {
        throw new Exception("Lỗi cập nhật hợp đồng: " . $stmt_up->error);
    }
    $stmt_up->close();

    // 3) Cập nhật Students: gán room_id = NULL (sinh viên rời phòng)
    $sql_up_stu = "UPDATE Students SET room_id = NULL WHERE student_id = ?";
    $stmt_stu = $conn->prepare($sql_up_stu);
    $stmt_stu->bind_param("i", $student_id);
    if (!$stmt_stu->execute()) {
        throw new Exception("Lỗi khi cập nhật room_id SV: " . $stmt_stu->error);
    }
    $stmt_stu->close();

    // 4) Kiểm tra phòng cũ còn ai?
    $sql_count = "
        SELECT COUNT(*) AS used_count
        FROM Students
        WHERE room_id = ?
          AND status = 'Active'
    ";
    $stmt_cnt = $conn->prepare($sql_count);
    $stmt_cnt->bind_param("i", $room_id);
    $stmt_cnt->execute();
    $used_count = $stmt_cnt->get_result()->fetch_assoc()['used_count'] ?? 0;
    $stmt_cnt->close();

    // used_count > 0 => 'occupied', ngược lại => 'available'
    $new_room_status = ($used_count > 0) ? 'occupied' : 'available';
    $stmt_up_r = $conn->prepare("UPDATE Rooms SET status=? WHERE room_id=?");
    $stmt_up_r->bind_param("si", $new_room_status, $room_id);
    if (!$stmt_up_r->execute()) {
        throw new Exception("Lỗi khi cập nhật trạng thái phòng: " . $stmt_up_r->error);
    }
    $stmt_up_r->close();

    // 5) Cập nhật Room_Status (tùy logic)
    $sql_rs = "
        UPDATE Room_Status
        SET end_date = CURDATE()
        WHERE room_id = ?
          AND student_id = ?
          AND (end_date IS NULL OR end_date > CURDATE())
    ";
    $stmt_rs = $conn->prepare($sql_rs);
    $stmt_rs->bind_param("ii", $room_id, $student_id);
    $stmt_rs->execute();
    $stmt_rs->close();

    // Commit
    $conn->commit();

    // Cuối cùng: chuyển hướng về trang danh sách, kèm thông báo
    $msg = "Hợp đồng ID $contract_id đã được chấm dứt thành công.";
    header("Location: contracts_list.php?message=" . urlencode($msg) . "&type=success");
    exit;
} catch (Exception $ex) {
    // Rollback nếu có lỗi
    $conn->rollback();
    // Quay lại list kèm thông báo lỗi
    header("Location: contracts_list.php?message=" . urlencode($ex->getMessage()) . "&type=error");
    exit;
}
