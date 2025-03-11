<?php
session_start();
// Kiểm tra sinh viên
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

include '../config/db_connect.php';

$student_code = $_SESSION['username'];

// Lấy thông tin sinh viên và phòng
$sql = "SELECT s.student_id, s.full_name, s.major, s.year_of_study, 
               s.room_id, r.room_code, r.building, r.floor, r.room_number
        FROM Students s
        LEFT JOIN Rooms r ON s.room_id = r.room_id
        WHERE s.student_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Không tìm thấy thông tin sinh viên.");
}
$student = $result->fetch_assoc();
$stmt->close();

// Lấy hợp đồng active
$sql_contract = "SELECT contract_id, contract_code, start_date, end_date
                 FROM Contracts
                 WHERE student_id = ? AND status = 'active'
                 LIMIT 1";
$stmt_ct = $conn->prepare($sql_contract);
$stmt_ct->bind_param("i", $student['student_id']);
$stmt_ct->execute();
$result_ct = $stmt_ct->get_result();
$contract = $result_ct->fetch_assoc();
$stmt_ct->close();
$conn->close();

// Nếu không có hợp đồng active, hiển thị cảnh báo
if (!$contract) {
    $no_contract_warning = "Bạn chưa có hợp đồng active hoặc hợp đồng đã hết hiệu lực.";
} else {
    $no_contract_warning = null;
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đơn Rời Phòng - Hết hạn hợp đồng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- CSS chung dành cho sinh viên -->
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <style>
        body {
            background-color: #f2f2f2;
        }
        .departure-container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            padding: 30px;
            border: 1px solid #ddd;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
            font-family: 'Times New Roman', serif;
        }
        .departure-container h2 {
            text-align: center;
            margin-bottom: 10px;
        }
        .departure-container p.subtitle {
            text-align: center;
            font-style: italic;
            margin-bottom: 20px;
        }
        .info-block {
            margin-bottom: 20px;
            background: #fafafa;
            border: 1px solid #eee;
            padding: 15px;
            border-radius: 4px;
        }
        .info-block h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        .info-item {
            margin-bottom: 6px;
        }
        label {
            font-weight: bold;
        }
        textarea {
            width: 100%;
            height: 80px;
            resize: vertical;
            padding: 8px;
            font-size: 16px;
        }
        .btn-group {
            margin-top: 20px;
            text-align: center;
        }
        button {
            background: #007bff;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #0056b3;
        }
        .status-btn {
            background: #6c757d;
        }
        .status-btn:hover {
            background: #5a6268;
        }
        .warning {
            color: red;
            font-style: italic;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/header.php'; ?>

<div class="main-content">
    <div class="departure-container">
        <h2>ĐƠN XIN RỜI PHÒNG</h2>
        <p class="subtitle">(Khi hết hạn hợp đồng)</p>

        <?php if ($no_contract_warning): ?>
            <p class="warning"><?php echo $no_contract_warning; ?></p>
        <?php else: ?>
            <!-- Thông tin sinh viên -->
            <div class="info-block">
                <h3>Thông tin Sinh viên</h3>
                <div class="info-item"><strong>Mã SV:</strong> <?php echo htmlspecialchars($student_code); ?></div>
                <div class="info-item"><strong>Họ và Tên:</strong> <?php echo htmlspecialchars($student['full_name']); ?></div>
                <div class="info-item"><strong>Ngành/Lớp:</strong> <?php echo htmlspecialchars($student['major']); ?></div>
                <div class="info-item"><strong>Khóa học (Năm thứ):</strong> <?php echo htmlspecialchars($student['year_of_study']); ?></div>
            </div>

            <!-- Thông tin phòng -->
            <div class="info-block">
                <h3>Phòng hiện tại</h3>
                <?php if ($student['room_code']): ?>
                    <div class="info-item">
                        <strong>Tòa nhà:</strong> <?php echo htmlspecialchars($student['building']); ?>,
                        <strong>Tầng:</strong> <?php echo htmlspecialchars($student['floor']); ?>,
                        <strong>Phòng:</strong> <?php echo htmlspecialchars($student['room_number']); ?> (Mã: <?php echo htmlspecialchars($student['room_code']); ?>)
                    </div>
                <?php else: ?>
                    <p class="warning">Bạn chưa được phân phòng hoặc phòng không tồn tại.</p>
                <?php endif; ?>
            </div>

            <!-- Thông tin hợp đồng -->
            <div class="info-block">
                <h3>Hợp đồng đang active</h3>
                <div class="info-item"><strong>Mã HĐ:</strong> <?php echo htmlspecialchars($contract['contract_code']); ?></div>
                <div class="info-item"><strong>Ngày bắt đầu:</strong> <?php echo htmlspecialchars($contract['start_date']); ?></div>
                <div class="info-item"><strong>Ngày kết thúc:</strong> <?php echo htmlspecialchars($contract['end_date']); ?></div>
            </div>

            <form action="ajax/process_departure_expire.php" method="POST">
                <!-- Ẩn student_id và contract_id -->
                <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                <input type="hidden" name="contract_id" value="<?php echo $contract['contract_id']; ?>">

                <div class="info-block">
                    <label for="reason">Lý do không gia hạn / rời phòng:</label>
                    <textarea name="reason" id="reason" required placeholder="VD: Tôi sẽ chuyển đến chỗ ở khác..."></textarea>
                </div>

                <div class="btn-group">
                    <button type="submit">Gửi đơn rời phòng</button>
                    <button type="button" class="status-btn" onclick="window.location.href='departure_status2.php'">Xem trạng thái đơn</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
