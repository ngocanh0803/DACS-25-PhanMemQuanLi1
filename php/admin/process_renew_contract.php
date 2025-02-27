<?php
// process_renew_contract.php
include '../config/db_connect.php';
session_start();

// if (!isset($_SESSION['...'])) { ... }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: contracts_list.php?message=Phương thức không hợp lệ&type=error");
    exit;
}

$contract_id = intval($_POST['contract_id'] ?? 0);
if ($contract_id <= 0) {
    header("Location: contracts_list.php?message=Thiếu contract_id&type=error");
    exit;
}

$end_date = $_POST['end_date'] ?? '';
$deposit  = floatval($_POST['deposit'] ?? 0);
$terms    = $_POST['terms'] ?? '';

// Bắt đầu transaction
$conn->begin_transaction();
try {
    // 1. Kiểm tra HĐ cũ
    $sql_check = "SELECT status FROM Contracts WHERE contract_id = ?";
    $stmt = $conn->prepare($sql_check);
    $stmt->bind_param("i", $contract_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception("Hợp đồng không tồn tại.");
    }
    if ($row['status'] !== 'active') {
        throw new Exception("Chỉ có thể gia hạn hợp đồng đang 'active'.");
    }

    // 2. UPDATE
    $sql_up = "UPDATE Contracts 
               SET end_date = ?, deposit = ?, terms = ?
               WHERE contract_id = ?";
    $stmt_up = $conn->prepare($sql_up);
    if (!$stmt_up) {
        throw new Exception("Lỗi prepare UPDATE: ".$conn->error);
    }
    $stmt_up->bind_param("sdsi", $end_date, $deposit, $terms, $contract_id);
    if (!$stmt_up->execute()) {
        throw new Exception("Lỗi UPDATE: ".$stmt_up->error);
    }
    $stmt_up->close();

    // 3. (Tuỳ chọn) Ghi log
    // Tạo 1 record trong contract_history (nếu bạn có)
    // or ignore

    // commit
    $conn->commit();

    header("Location: contracts_list.php?message=Gia hạn thành công&type=success");
    exit;
} catch (Exception $ex) {
    $conn->rollback();
    header("Location: renew_contract.php?contract_id=$contract_id&message=".urlencode($ex->getMessage())."&type=error");
    exit;
}
