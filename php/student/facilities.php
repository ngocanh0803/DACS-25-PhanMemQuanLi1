<?php
session_start();

// Kiểm tra đăng nhập và role: chỉ cho phép sinh viên truy cập
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

$student_code = $_SESSION['username'];

// Kết nối CSDL
include '../config/db_connect.php';

// Lấy thông tin sinh viên để biết room_id
$sql_student = "SELECT student_id, room_id FROM Students WHERE student_code = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result_student = $stmt->get_result();

if ($result_student->num_rows > 0) {
    $student = $result_student->fetch_assoc();
    $room_id = $student['room_id'];
    $student_id = $student['student_id'];
} else {
    die("Không tìm thấy thông tin sinh viên.");
}
$stmt->close();

$facilities = [];
if ($room_id) {
    // Truy vấn lấy danh sách cơ sở vật chất từ bảng Facilities theo room_id
    $sql_facilities = "SELECT facility_id, facility_code, facility_name, quantity, status FROM Facilities WHERE room_id = ?";
    $stmt = $conn->prepare($sql_facilities);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result_facilities = $stmt->get_result();
    while ($row = $result_facilities->fetch_assoc()) {
        $facilities[] = $row;
    }
    $stmt->close();
}
$conn->close();

// Hàm chuyển đổi trạng thái sang tiếng Việt
function convertFacilityStatus($status) {
    switch ($status) {
        case 'good':
            return 'Tốt';
        case 'broken':
            return 'Hỏng';
        default:
            return $status;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cơ sở vật chất - Sinh viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- CSS chung cho giao diện sinh viên -->
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <!-- CSS riêng cho trang cơ sở vật chất -->
    <link rel="stylesheet" href="../../assets/css/facilities_student.css">
</head>
<body>

    <!-- Include Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Nội dung chính -->
    <div class="main-content">
        <!-- Include Header -->
        <?php include 'layout/header.php'; ?>

        <div class="content">
            <h2>Cơ sở vật chất trong phòng</h2>
            <!-- Nút liên kết tới trang yêu cầu thiết bị -->
            <div class="action-bar">
                <a href="equipment_request.php" class="btn request-btn">
                    <i class="fas fa-plus-circle"></i> Yêu cầu thiết bị
                </a>
            </div>
            <?php if(count($facilities) > 0): ?>
                <table class="facilities-table">
                    <thead>
                        <tr>
                            <th>Mã thiết bị</th>
                            <th>Tên thiết bị</th>
                            <th>Số lượng</th>
                            <th>Tình trạng</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($facilities as $facility): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($facility['facility_code']); ?></td>
                                <td><?php echo htmlspecialchars($facility['facility_name']); ?></td>
                                <td><?php echo htmlspecialchars($facility['quantity']); ?></td>
                                <td><?php echo convertFacilityStatus($facility['status']); ?></td>
                                <td>
                                    <!-- Luôn hiển thị nút báo cáo -->
                                    <button class="btn report-btn" data-facility-id="<?php echo htmlspecialchars($facility['facility_id']); ?>" data-facility-code="<?php echo htmlspecialchars($facility['facility_code']); ?>">Báo cáo sự cố</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-facilities">Không có thông tin cơ sở vật chất nào.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal báo cáo sự cố -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Báo cáo sự cố</h3>
            <form id="reportForm">
                <input type="hidden" name="facility_id" id="facility_id">
                <div class="form-group">
                    <label for="facility_code">Mã thiết bị:</label>
                    <input type="text" id="facility_code" name="facility_code" readonly>
                </div>
                <div class="form-group">
                    <label for="reported_quantity">Số lượng bị lỗi:</label>
                    <input type="number" id="reported_quantity" name="reported_quantity" required min="1">
                </div>
                <div class="form-group">
                    <label for="reported_condition">Mô tả tình trạng:</label>
                    <textarea id="reported_condition" name="reported_condition" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn">Gửi báo cáo</button>
            </form>
        </div>
    </div>

    <!-- File JS dành cho trang cơ sở vật chất -->
    <script src="../../assets/js/facilities_student.js"></script>
</body>
</html>
