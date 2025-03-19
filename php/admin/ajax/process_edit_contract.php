<?php
// process_edit_contract.php
include '../../config/db_connect.php';
session_start();

// (Tuỳ chọn) Kiểm tra quyền
// if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['manager','admin','student_manager','accountant'])) {
//     header("Location: login.php?message=Không có quyền&type=error");
//     exit;
// }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Phương thức không hợp lệ.";
    exit;
}

// Lấy dữ liệu từ form
$contract_id = intval($_POST['contract_id'] ?? 0);
$room_id_new = intval($_POST['room_id'] ?? 0);
$start_date  = $_POST['start_date'] ?? '';
$end_date    = $_POST['end_date'] ?? '';
$deposit     = floatval($_POST['deposit'] ?? 0);
$terms       = $_POST['terms'] ?? '';
$status      = $_POST['status'] ?? 'active'; // "active", "terminated", "expired"...

if ($contract_id <= 0) {
    header("Location: ../contracts_list.php?message=Thiếu contract_id&type=error");
    exit;
}

// Bắt đầu transaction
$conn->begin_transaction();

try {
    // 1) Lấy thông tin hợp đồng cũ
    $sql_old = "SELECT student_id, room_id FROM Contracts WHERE contract_id = ?";
    $stmt = $conn->prepare($sql_old);
    $stmt->bind_param("i", $contract_id);
    $stmt->execute();
    $oldContract = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$oldContract) {
        throw new Exception("Không tìm thấy hợp đồng với ID = $contract_id.");
    }

    // Lấy student_id + room_id cũ
    $student_id   = intval($oldContract['student_id']);
    $room_id_old  = intval($oldContract['room_id']);

    // 2) Update bảng Contracts
    // Thêm cột status vào UPDATE
    $sql_update = "
        UPDATE Contracts
        SET 
            room_id    = ?,
            start_date = ?,
            end_date   = ?,
            deposit    = ?,
            terms      = ?,
            status     = ?
        WHERE contract_id = ?
    ";
    $stmt_up = $conn->prepare($sql_update);
    if (!$stmt_up) {
        throw new Exception("Lỗi prepare UPDATE Contracts: " . $conn->error);
    }
    $stmt_up->bind_param(
        "issdssi",
        $room_id_new,
        $start_date,
        $end_date,
        $deposit,
        $terms,
        $status,
        $contract_id
    );
    if (!$stmt_up->execute()) {
        throw new Exception("Lỗi khi UPDATE Contracts: " . $stmt_up->error);
    }
    $stmt_up->close();

    // 3) Nếu phòng không đổi => không cần di chuyển SV
    //    (có thể vẫn muốn cập nhật status hợp đồng sang 'terminated' hay 'expired')
    //    => Xong, commit + redirect.
    if ($room_id_new === $room_id_old) {
        // Bỏ qua logic chuyển phòng
        // Trường hợp user cập nhật "Trạng thái" -> DB đã lưu -> commit
        $conn->commit();
        header("Location: ../contracts_list.php?message=Đã cập nhật hợp đồng (không đổi phòng)&type=success");
        exit;
    }

    // 4) Nếu có đổi phòng, ta phải:
    // (a) Gán sinh viên qua phòng mới
    $sql_up_stu = "UPDATE Students SET room_id = ? WHERE student_id = ?";
    $stmt_stu = $conn->prepare($sql_up_stu);
    if (!$stmt_stu) {
        throw new Exception("Lỗi prepare UPDATE Students: " . $conn->error);
    }
    $stmt_stu->bind_param("ii", $room_id_new, $student_id);
    if (!$stmt_stu->execute()) {
        throw new Exception("Lỗi khi cập nhật room_id cho SV: " . $stmt_stu->error);
    }
    $stmt_stu->close();

    // (b) Cập nhật phòng cũ (nếu cũ != 0)
    //    Xem còn ai ở phòng cũ không?
    if ($room_id_old > 0) {
        $sql_check_old = "
            SELECT 
                (SELECT COUNT(*) FROM Students WHERE room_id=? AND status='Active') AS used_count
        ";
        $stmt_chk_old = $conn->prepare($sql_check_old);
        $stmt_chk_old->bind_param("i", $room_id_old);
        $stmt_chk_old->execute();
        $used_old = $stmt_chk_old->get_result()->fetch_assoc()['used_count'] ?? 0;
        $stmt_chk_old->close();

        // Logic: nếu used_old > 0 => 'occupied', = 0 => 'available'
        $new_status_old_room = ($used_old > 0) ? 'occupied' : 'available';

        $stmt_up_old = $conn->prepare("UPDATE Rooms SET status=? WHERE room_id=?");
        $stmt_up_old->bind_param("si", $new_status_old_room, $room_id_old);
        if (!$stmt_up_old->execute()) {
            throw new Exception("Lỗi cập nhật phòng cũ: " . $stmt_up_old->error);
        }
        $stmt_up_old->close();
    }

    // (c) Cập nhật phòng mới
    $sql_check_new = "
        SELECT 
            (SELECT COUNT(*) FROM Students WHERE room_id=? AND status='Active') AS used_count
    ";
    $stmt_chk_new = $conn->prepare($sql_check_new);
    $stmt_chk_new->bind_param("i", $room_id_new);
    $stmt_chk_new->execute();
    $used_new = $stmt_chk_new->get_result()->fetch_assoc()['used_count'] ?? 0;
    $stmt_chk_new->close();

    // Nếu used_new > 0 => 'occupied', =0 => 'available'
    $new_status_new_room = ($used_new > 0) ? 'occupied' : 'available';

    $stmt_up_new = $conn->prepare("UPDATE Rooms SET status=? WHERE room_id=?");
    $stmt_up_new->bind_param("si", $new_status_new_room, $room_id_new);
    if (!$stmt_up_new->execute()) {
        throw new Exception("Lỗi cập nhật phòng mới: " . $stmt_up_new->error);
    }
    $stmt_up_new->close();

    // (d) Thêm hoặc chỉnh sửa Room_Status (tuỳ logic). 
    // Ở đây, ví dụ: thêm 1 record mới, start_date = NOW(). 
    // Hoặc update record cũ => end_date = NOW(), v.v...
    $sql_rs = "
        INSERT INTO Room_Status (room_id, student_id, start_date)
        VALUES (?, ?, NOW())
    ";
    $stmt_rs = $conn->prepare($sql_rs);
    if (!$stmt_rs) {
        throw new Exception("Lỗi prepare Room_Status: " . $conn->error);
    }
    $stmt_rs->bind_param("ii", $room_id_new, $student_id);
    if (!$stmt_rs->execute()) {
        throw new Exception("Lỗi khi INSERT Room_Status: " . $stmt_rs->error);
    }
    $stmt_rs->close();

    // Xong => commit
    $conn->commit();

    header("Location: ../contracts_list.php?message=Đã sửa hợp đồng thành công&type=success");
    exit;
} catch (Exception $ex) {
    // Rollback
    $conn->rollback();
    // Quay lại trang edit, kèm message
    header("Location: ../edit_contract.php?contract_id=".$contract_id."&message=".urlencode($ex->getMessage())."&type=error");
    exit;
}
