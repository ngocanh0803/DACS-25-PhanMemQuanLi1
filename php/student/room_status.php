<?php
session_start();

// Kiểm tra đăng nhập và role: chỉ cho phép sinh viên truy cập
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

$student_code = $_SESSION['username'];
include '../config/db_connect.php';

// Lấy thông tin sinh viên (để lấy student_id)
$sql_student = "SELECT student_id, room_id FROM Students WHERE student_code = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result_student = $stmt->get_result();
if($result_student->num_rows > 0){
    $student = $result_student->fetch_assoc();
    $student_id = $student['student_id'];
} else {
    die("Không tìm thấy thông tin sinh viên.");
}
$stmt->close();

// Truy vấn lịch sử tình trạng phòng của sinh viên từ bảng Room_Status
$sql_status = "SELECT rs.room_status_id, rs.start_date, rs.end_date, r.room_code
               FROM Room_Status rs
               JOIN Rooms r ON rs.room_id = r.room_id
               WHERE rs.student_id = ?
               ORDER BY rs.start_date DESC";
$stmt = $conn->prepare($sql_status);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result_status = $stmt->get_result();
$roomStatuses = [];
while($row = $result_status->fetch_assoc()){
    $roomStatuses[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tình trạng phòng - Sinh viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- CSS chung của sinh viên -->
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <!-- CSS riêng cho trang tình trạng phòng -->
    <link rel="stylesheet" href="../../assets/css/room_status.css">
</head>
<body>
    <?php include 'layout/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'layout/header.php'; ?>
        <div class="content">
            <h2>Tình trạng phòng</h2>
            <?php if(count($roomStatuses) > 0): ?>
            <div class="timeline">
                <?php foreach($roomStatuses as $status): ?>
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div class="timeline-content">
                        <h3>Phòng <?php echo htmlspecialchars($status['room_code']); ?></h3>
                        <p><strong>Bắt đầu:</strong> <?php echo htmlspecialchars($status['start_date']); ?></p>
                        <p>
                            <strong>Kết thúc:</strong> 
                            <?php echo !empty($status['end_date']) ? htmlspecialchars($status['end_date']) : 'Đang ở'; ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <p>Chưa có thông tin về tình trạng phòng.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
