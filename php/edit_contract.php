<?php
// edit_contract.php
include 'db_connect.php';
session_start();

// (Tuỳ chọn) Kiểm tra quyền
// if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['manager', 'admin', 'student_manager', 'accountant'])) {
//     header("Location: login.php?message=Bạn không có quyền truy cập&type=error");
//     exit();
// }

// Lấy contract_id từ GET
if (!isset($_GET['contract_id'])) {
    echo "Thiếu contract_id.";
    exit;
}
$contract_id = intval($_GET['contract_id']);

// 1) Lấy thông tin hợp đồng cũ
$sql = "
    SELECT 
        c.contract_id,
        c.contract_code,
        c.student_id,
        c.room_id,
        c.signed_date,
        c.start_date,
        c.end_date,
        c.deposit,
        c.terms,
        c.status,

        s.student_code,
        s.full_name,

        r.building,
        r.floor,
        r.room_number,
        r.capacity

    FROM Contracts c
    LEFT JOIN Students s ON c.student_id = s.student_id
    LEFT JOIN Rooms r ON c.room_id = r.room_id
    WHERE c.contract_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $contract_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "Không tìm thấy hợp đồng.";
    exit;
}
$contract = $result->fetch_assoc();
$stmt->close();

// 2) Lấy danh sách phòng với truy vấn theo yêu cầu
$sql_rooms = "
    SELECT 
        r.room_id,
        r.building,
        r.floor,
        r.room_number,
        (CASE r.capacity
            WHEN '2' THEN 2
            WHEN '4' THEN 4
            WHEN '8' THEN 8
        END) AS capacity_num,
        r.price,
        r.status,
        COUNT(st.student_id) AS used_count
    FROM Rooms r
    LEFT JOIN Students st 
        ON st.room_id = r.room_id 
        AND st.status = 'Active'
    WHERE r.status IN ('available','occupied')
    GROUP BY 
        r.room_id, 
        r.building, 
        r.floor,
        r.room_number,
        r.capacity, 
        r.price, 
        r.status
    HAVING 
        r.status = 'available'
        OR (
            r.status = 'occupied'
            AND used_count < (
                CASE r.capacity
                    WHEN '2' THEN 2
                    WHEN '4' THEN 4
                    WHEN '8' THEN 8
                END
            )
        )
    ORDER BY r.building, r.floor, r.room_number
";
$result_rooms = $conn->query($sql_rooms);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa Hợp Đồng</title>
    <link rel="stylesheet" href="../assest/css/main.css">
    <link rel="stylesheet" href="../assest/css/manage_contracts.css">
</head>
<body>
<?php include 'layout/header.php'; ?>
<div class="container">
    <?php include 'layout/menu.php'; ?>

    <main class="content">
        <h2>Sửa Hợp Đồng (ID: <?php echo $contract_id; ?>)</h2>

        <!-- Thông báo (nếu có) -->
        <?php if (isset($_GET['message'])): ?>
            <div class="notification <?php echo (isset($_GET['type']) && $_GET['type'] === 'error') ? 'error' : ''; ?>">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <form action="process_edit_contract.php" method="POST" id="edit-contract-form">
            <!-- Ẩn contract_id để POST -->
            <input type="hidden" name="contract_id" value="<?php echo $contract_id; ?>">

            <!-- Hiển thị contract_code (không sửa) -->
            <div class="form-group">
                <label>Mã Hợp Đồng</label>
                <input type="text" value="<?php echo htmlspecialchars($contract['contract_code']); ?>" 
                       readonly style="background-color:#eee;">
            </div>

            <!-- Sinh viên (không đổi, chỉ hiển thị) -->
            <div class="form-group">
                <label>Sinh viên</label>
                <input type="text" readonly style="background-color:#eee;"
                       value="<?php echo htmlspecialchars($contract['student_code'].' - '.$contract['full_name']); ?>">
            </div>

            <!-- Chọn Phòng (có thể cho đổi) -->
            <div class="form-group">
                <label for="room_id">Phòng</label>
                <select name="room_id" id="room_id" required>
                    <?php
                    // Phòng hiện tại
                    $current_room_id = $contract['room_id'];
                    $current_label = "Tòa ".$contract['building']
                                   ." - Tầng ".$contract['floor']
                                   ." - Phòng ".$contract['room_number']
                                   ." (Hiện tại)";
                    ?>
                    <option value="<?php echo $current_room_id; ?>">
                        <?php echo htmlspecialchars($current_label); ?>
                    </option>
                    <option disabled>──────────</option>

                    <?php if ($result_rooms && $result_rooms->num_rows > 0): ?>
                        <?php while($r = $result_rooms->fetch_assoc()):
                            $left = (int)$r['capacity_num'] - (int)$r['used_count'];
                            // Nếu trùng với room_id hiện tại => bỏ qua
                            if ($r['room_id'] == $current_room_id) continue;
                            ?>
                            <option value="<?php echo $r['room_id']; ?>">
                                <?php 
                                echo "Tòa ".$r['building']
                                    ." - Tầng ".$r['floor']
                                    ." - Phòng ".$r['room_number']
                                    ." (Còn ".$left." chỗ)";
                                ?>
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="">Không có phòng khả dụng</option>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Ngày bắt đầu -->
            <div class="form-group">
                <label for="start_date">Ngày bắt đầu</label>
                <input type="date" name="start_date" id="start_date" required
                       value="<?php echo htmlspecialchars($contract['start_date']); ?>">
            </div>

            <!-- Ngày kết thúc -->
            <div class="form-group">
                <label for="end_date">Ngày kết thúc</label>
                <input type="date" name="end_date" id="end_date" required
                       value="<?php echo htmlspecialchars($contract['end_date']); ?>">
            </div>

            <!-- Tiền cọc -->
            <div class="form-group">
                <label for="deposit">Tiền đặt cọc (VNĐ)</label>
                <input type="number" name="deposit" id="deposit" min="0" required
                       value="<?php echo htmlspecialchars($contract['deposit']); ?>">
            </div>

            <!-- Điều khoản -->
            <div class="form-group">
                <label for="terms">Điều khoản</label>
                <textarea name="terms" id="terms" rows="4" required><?php 
                    echo htmlspecialchars($contract['terms']); 
                ?></textarea>
            </div>

            <!-- Trạng thái (có thể cho đổi) -->
            <div class="form-group">
                <label for="status">Trạng thái</label>
                <select name="status" id="status">
                    <option value="active" <?php if($contract['status'] == 'active') echo 'selected'; ?>>
                        Hoạt động
                    </option>
                    <option value="terminated" <?php if($contract['status'] == 'terminated') echo 'selected'; ?>>
                        Đã chấm dứt
                    </option>
                    <option value="expired" <?php if($contract['status'] == 'expired') echo 'selected'; ?>>
                        Hết hạn
                    </option>
                </select>
            </div>

            <button type="submit" class="submit-btn">Cập Nhật Hợp Đồng</button>
            <a href="contracts_list.php" class="cancel-btn">Hủy</a>
        </form>
    </main>
</div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assest/js/search.js"></script>
    <script src="../assest/js/main.js"></script>
    <script src="../assest/js/manage_contracts.js"></script>
</body>
</html>
