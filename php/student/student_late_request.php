<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

include '../config/db_connect.php';

// Lấy thông tin sinh viên
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
    <style>
        .main-content { padding: 20px; }
        .form-container {
            max-width: 600px; margin: 0 auto; background: #fff;
            padding: 30px; border: 1px solid #ccc; box-shadow: 0 0 8px rgba(0,0,0,0.1);
            font-family: 'Times New Roman', serif;
        }
        h2 { text-align: center; margin-bottom: 15px; }
        label { font-weight: bold; }
        textarea { width: 100%; height: 80px; padding: 6px; }
        button {
            background: #007bff; color: #fff; border: none;
            padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-right: 10px;
        }
        button:hover { background: #0056b3; }
        .btn-group { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>

<?php include 'layout/sidebar.php'; ?>
<?php include 'layout/header.php'; ?>

<div style="margin-top: 100px" class="main-content">
    <div class="form-container">
        <h2>Gửi Yêu Cầu Về Muộn</h2>
        <?php
        if (isset($_SESSION['success'])) {
            echo "<p style='color: green;'>{$_SESSION['success']}</p>";
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo "<p style='color: red;'>{$_SESSION['error']}</p>";
            unset($_SESSION['error']);
        }
        ?>
        <p><strong>Mã SV:</strong> <?php echo htmlspecialchars($student_code); ?></p>
        <p><strong>Họ Tên:</strong> <?php echo htmlspecialchars($student['full_name']); ?></p>

        <form action="process_late_request.php" method="POST">
            <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
            <div style="margin-bottom: 15px;">
                <label for="reason">Lý do về muộn:</label><br>
                <textarea name="reason" id="reason" required></textarea>
            </div>
            <div class="btn-group">
                <button type="submit">Gửi yêu cầu</button>
                <button type="button" onclick="window.location.href='late_status.php'">Xem Trạng Thái Đơn</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
