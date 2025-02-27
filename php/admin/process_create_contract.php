<?php
// process_create_contract.php
include '../config/db_connect.php';
session_start();

// (Tuỳ chọn) Kiểm tra quyền
// if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['manager','admin','student_manager','accountant'])) {
//    header("Location: login.php?message=Bạn không có quyền truy cập&type=error");
//    exit;
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $student_id  = (int)$_POST['student_id'];
    $room_id     = (int)$_POST['room_id'];
    $signed_date = $_POST['signed_date'];
    $start_date  = $_POST['start_date'];
    $end_date    = $_POST['end_date'];
    $deposit     = (float)$_POST['deposit'];
    $terms       = $_POST['terms'];
    $status      = 'active'; // mặc định

    // 1) Kiểm tra SV đã có hợp đồng active chưa
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM Contracts WHERE student_id = ? AND status='active'");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $rs = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($rs['cnt'] > 0) {
        header("Location: create_contract.php?message=Sinh viên này đã có hợp đồng active&type=error");
        exit;
    }

    // 2) Lấy student_code => tạo contract_code = "CT" + student_code
    $stmt = $conn->prepare("SELECT student_code FROM Students WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $stu = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$stu || !isset($stu['student_code'])) {
        header("Location: create_contract.php?message=Không tìm thấy student_code của SV&type=error");
        exit;
    }
    $student_code  = $stu['student_code'];
    $contract_code = 'CT' . $student_code;

    // Sử dụng transaction
    $conn->begin_transaction();
    try {
        // (a) Thêm hợp đồng vào Contracts
        $sql_contract = "
            INSERT INTO Contracts 
            (contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($sql_contract);
        if (!$stmt) {
            throw new Exception("Lỗi prepare INSERT Contracts: ".$conn->error);
        }
        $stmt->bind_param("siisssdss",
            $contract_code,
            $student_id,
            $room_id,
            $signed_date,
            $start_date,
            $end_date,
            $deposit,
            $terms,
            $status
        );
        if (!$stmt->execute()) {
            throw new Exception("Lỗi khi thêm hợp đồng: ".$stmt->error);
        }
        $stmt->close();

        // (b) Update Students.room_id => đưa sinh viên vào phòng
        $stmt_up_stu = $conn->prepare("UPDATE Students SET room_id = ? WHERE student_id = ?");
        if (!$stmt_up_stu) {
            throw new Exception("Lỗi prepare UPDATE Students: ".$conn->error);
        }
        $stmt_up_stu->bind_param("ii", $room_id, $student_id);
        if (!$stmt_up_stu->execute()) {
            throw new Exception("Lỗi khi cập nhật room_id cho SV: ".$stmt_up_stu->error);
        }
        $stmt_up_stu->close();

        // (c) Kiểm tra số người trong phòng => nếu > 0 => occupied, nếu = 0 => available
        //   *BỎ QUA* capacity, theo yêu cầu: "chỉ cần có 1 người là occupied"
        $sql_count = "
            SELECT 
              (SELECT COUNT(*) FROM Students st WHERE st.room_id = r.room_id AND st.status='Active') AS used_count
            FROM Rooms r 
            WHERE r.room_id = ?
        ";
        $stmt_count = $conn->prepare($sql_count);
        if (!$stmt_count) {
            throw new Exception("Lỗi prepare count occupant: ".$conn->error);
        }
        $stmt_count->bind_param("i", $room_id);
        $stmt_count->execute();
        $used_count = $stmt_count->get_result()->fetch_assoc()['used_count'] ?? 0;
        $stmt_count->close();

        // Xác định status phòng
        $new_room_status = ($used_count > 0) ? 'occupied' : 'available';

        // Cập nhật Rooms
        $stmt_up_room = $conn->prepare("UPDATE Rooms SET status = ? WHERE room_id = ?");
        if (!$stmt_up_room) {
            throw new Exception("Lỗi prepare UPDATE Rooms: ".$conn->error);
        }
        $stmt_up_room->bind_param("si", $new_room_status, $room_id);
        if (!$stmt_up_room->execute()) {
            throw new Exception("Lỗi khi cập nhật phòng: ".$stmt_up_room->error);
        }
        $stmt_up_room->close();

        // (d) Thêm vào Room_Status
        $sql_rs = "
            INSERT INTO Room_Status (room_id, student_id, start_date, end_date)
            VALUES (?, ?, ?, ?)
        ";
        $stmt_rs = $conn->prepare($sql_rs);
        if (!$stmt_rs) {
            throw new Exception("Lỗi prepare Room_Status: ".$conn->error);
        }
        $stmt_rs->bind_param("iiss", $room_id, $student_id, $start_date, $end_date);
        if (!$stmt_rs->execute()) {
            throw new Exception("Lỗi khi thêm Room_Status: ".$stmt_rs->error);
        }
        $stmt_rs->close();

        // Commit
        $conn->commit();
        
        // Redirect success
        header("Location: contracts_list.php?message=Hợp đồng đã tạo thành công&type=success");
        exit;
    } catch (Exception $e) {
        // Rollback
        $conn->rollback();
        header("Location: create_contract.php?message=".urlencode($e->getMessage())."&type=error");
        exit;
    }
} else {
    echo "Phương thức không được hỗ trợ.";
}
?>
