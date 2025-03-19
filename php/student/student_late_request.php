<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

include '../config/db_connect.php';

$student_code = $_SESSION['username'];
$sql = "SELECT student_id, full_name FROM Students WHERE student_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$student) {
    die("Không tìm thấy thông tin sinh viên.");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Gửi Yêu Cầu Về Muộn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <link rel="stylesheet" href="../../assets/css/student_late_request.css">
</head>
<body>

<?php include 'layout/sidebar.php'; ?>

<div class="main-content">
    <?php include 'layout/header.php'; ?>

    <div class="content">
        <div class="form-container">
            <h2><i class="fas fa-user-clock"></i> Gửi Yêu Cầu Về Muộn</h2>
            <?php
            // Hiển thị thông báo (nếu có)
            if (isset($_SESSION['success'])) {
                echo "<p class='success-message'>{$_SESSION['success']}</p>";
                unset($_SESSION['success']); // Xóa thông báo sau khi hiển thị
            }
            if (isset($_SESSION['error'])) {
                echo "<p class='error-message'>{$_SESSION['error']}</p>";
                unset($_SESSION['error']);
            }
            ?>
             <!-- Thông tin sinh viên -->
            <p><strong><i class="fas fa-id-card"></i> Mã SV:</strong> <?php echo htmlspecialchars($student_code); ?></p>
            <p><strong><i class="fas fa-user"></i> Họ Tên:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>

            <form action="ajax/process_late_request.php" method="POST">
                <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                <div>
                    <label for="reason"><i class="fas fa-comment"></i> Lý do về muộn:</label>
                    <textarea name="reason" id="reason" required></textarea>
                </div>
                <div class="btn-group">
                    <button type="submit"><i class="fas fa-paper-plane"></i> Gửi yêu cầu</button>
                    <a href="late_status.php" class="btn"><i class="fas fa-history"></i> Xem Trạng Thái</a>
                </div>
            </form>
        </div>
    </div>
</div>
   <!-- Include Chatbox -->
   <?php include 'chatbox.php'; ?>
</body>
</html>