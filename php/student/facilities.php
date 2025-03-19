<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

$student_code = $_SESSION['username'];
include '../config/db_connect.php';

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

function convertFacilityStatus($status) {
    switch ($status) {
        case 'good':
            return '<span class="status-badge good">Tốt</span>';
        case 'broken':
            return '<span class="status-badge broken">Hỏng</span>';
        default:
            return htmlspecialchars($status);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cơ sở vật chất - Sinh viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <link rel="stylesheet" href="../../assets/css/facilities_student.css">
    <style>
        .facilities-table tr {
        /* display: flex; Sử dụng flexbox cho mỗi hàng            */
        /* width: 100%;   Hàng chiếm toàn bộ chiều rộng */
        /* justify-content: space-between; Quan trọng: Căn chỉnh các ô con                                       */
        }
    </style>
</head>
<body>

<?php include 'layout/sidebar.php'; ?>

<div class="main-content">
    <?php include 'layout/header.php'; ?>

    <div class="content">
      <div class="facilities-container">
        <h2><i class="fas fa-tools"></i> Cơ sở vật chất trong phòng</h2>
        <?php if(count($facilities) > 0): ?>
            <table class="facilities-table">
                <thead>
                    <tr>
                        <th class = "hide-on-mobile"><i class="fas fa-barcode"></i> Mã</th>
                        <th><i class="fas fa-couch"></i> Tên thiết bị</th>
                        <th class = "hide-on-mobile"><i class="fas fa-sort-numeric-up"></i> Số lượng</th>
                        <th><i class="fas fa-info-circle"></i> Tình trạng</th>
                        <th><i class="fas fa-cog"></i> Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($facilities as $facility): ?>
                        <tr>
                            <td class = "hide-on-mobile"><?php echo htmlspecialchars($facility['facility_code']); ?></td>
                            <td><?php echo htmlspecialchars($facility['facility_name']); ?></td>
                             <!-- Thêm class để lấy giá trị số lượng -->
                            <td class="hide-on-mobile quantity-value"><?php echo htmlspecialchars($facility['quantity']); ?></td>
                            <td><?php echo convertFacilityStatus($facility['status']); ?></td>
                            <td>
                                <button class="btn report-btn" data-facility-id="<?php echo htmlspecialchars($facility['facility_id']); ?>" data-facility-code="<?php echo htmlspecialchars($facility['facility_code']); ?>"><i class="fas fa-exclamation-triangle"></i> Báo cáo</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="action-bar">
            <a href="#" class="btn request-btn" id="request-equipment-btn">
                <i class="fas fa-plus-circle"></i> Yêu cầu thiết bị
            </a>
             <a href="status_dashboard.php" class="btn request-btn">
                <i class="fas fa-clipboard-list"></i> Trạng thái YC/BC
            </a>
        </div>
        <?php else: ?>
            <p class="no-facilities">Không có thông tin cơ sở vật chất nào.</p>
        <?php endif; ?>
       </div>
    </div>

    <!-- Modal báo cáo sự cố -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <span class="close">×</span>
            <h3><i class="fas fa-exclamation-triangle"></i> Báo cáo sự cố</h3>
            <form id="reportForm" action="process_report.php" method="POST">
                <input type="hidden" name="facility_id" id="facility_id">
                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
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
    <!-- Modal yêu cầu thiết bị -->
    <div id="requestModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeRequestModal">×</span>
            <h3><i class="fas fa-plus-square"></i> Yêu cầu thiết bị</h3>
            <form id="requestForm" action="process_request.php" method="POST">
                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
                <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
                <div class="form-group">
                    <label for="request_type">Loại yêu cầu:</label>
                    <select name="request_type" id="request_type" required>
                        <option value="additional">Thêm thiết bị chung</option>
                        <option value="personal">Chuyển thêm thiết bị cá nhân</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="facility_name">Tên thiết bị:</label>
                    <input type="text" name="facility_name" id="facility_name" required>
                </div>
                <div class="form-group">
                    <label for="quantity">Số lượng:</label>
                    <input type="number" name="quantity" id="quantity" required min="1">
                </div>
                <div class="form-group">
                    <label for="description">Lý do / Mô tả yêu cầu:</label>
                    <textarea name="description" id="description" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn">Gửi yêu cầu</button>
            </form>
        </div>
    </div>
</div>
 <!-- Include Chatbox -->
 <?php include 'chatbox.php'; ?>
 <script src="../../assets/js/facilities_student.js"></script>
</body>
</html>