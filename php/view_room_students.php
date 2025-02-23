<?php
// view_room_students.php
include 'db_connect.php';

if (isset($_GET['room_id'])) {
    $room_id = $_GET['room_id'];

    // Kiểm tra room_id có hợp lệ không
    if (!is_numeric($room_id)) {
        echo '<script>alert("ID phòng không hợp lệ."); window.location.href="rooms_list.php";</script>';
        exit;
    }

    // Lấy thông tin phòng
    $sql_room = "SELECT * FROM Rooms WHERE room_id = ?";
    $stmt_room = $conn->prepare($sql_room);
    $stmt_room->bind_param("i", $room_id);
    $stmt_room->execute();
    $result_room = $stmt_room->get_result();
    $room = $result_room->fetch_assoc();

    if (!$room) {
        echo '<script>alert("Phòng không tồn tại."); window.location.href="rooms_list.php";</script>';
        exit;
    }

    // Lấy danh sách sinh viên trong phòng
    $sql_students = "SELECT * FROM Students WHERE room_id = ?";
    $stmt_students = $conn->prepare($sql_students);
    $stmt_students->bind_param("i", $room_id);
    $stmt_students->execute();
    $result_students = $stmt_students->get_result();
} else {
    echo '<script>alert("ID phòng không hợp lệ."); window.location.href="rooms_list.php";</script>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Sinh Viên - Phòng <?php echo htmlspecialchars($room['room_number']); ?></title>
    <link rel="stylesheet" href="../assest/css/main.css">
    <link rel="stylesheet" href="../assest/css/rooms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="room-students-container">
                <div class="page-header">
                    <div class="header-content">
                        <h2>
                            <i class="fas fa-users"></i>
                            Danh Sách Sinh Viên
                        </h2>
                        <div class="room-info">
                            <span class="building-info">
                                <i class="fas fa-building"></i>
                                Tòa <?php echo htmlspecialchars($room['building']); ?>
                            </span>
                            <span class="room-number">
                                <i class="fas fa-door-closed"></i>
                                Phòng <?php echo htmlspecialchars($room['room_number']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="header-actions">
                        <a href="rooms_list.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i>
                            Quay lại
                        </a>
                    </div>
                </div>

                <div class="room-summary">
                    <div class="summary-card">
                        <i class="fas fa-bed"></i>
                        <div class="summary-info">
                            <span class="label">Sức chứa</span>
                            <span class="value"><?php echo htmlspecialchars($room['capacity']); ?> người</span>
                        </div>
                    </div>
                    <div class="summary-card">
                        <i class="fas fa-user-check"></i>
                        <div class="summary-info">
                            <span class="label">Đang ở</span>
                            <span class="value"><?php echo $result_students->num_rows; ?> sinh viên</span>
                        </div>
                    </div>
                    <div class="summary-card">
                        <i class="fas fa-door-open"></i>
                        <div class="summary-info">
                            <span class="label">Còn trống</span>
                            <span class="value"><?php echo $room['capacity'] - $result_students->num_rows; ?> chỗ</span>
                        </div>
                    </div>
                </div>

                <?php if ($result_students->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-id-card"></i> Mã Sinh Viên</th>
                                    <th><i class="fas fa-user"></i> Họ và Tên</th>
                                    <th><i class="fas fa-envelope"></i> Email</th>
                                    <th><i class="fas fa-phone"></i> Số điện thoại</th>
                                    <th><i class="fas fa-cogs"></i> Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($student = $result_students->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="student-code">
                                                <?php echo htmlspecialchars($student['student_code']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="student-name">
                                                <?php echo htmlspecialchars($student['full_name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>" class="email-link">
                                                <i class="fas fa-envelope"></i>
                                                <?php echo htmlspecialchars($student['email']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="tel:<?php echo htmlspecialchars($student['phone']); ?>" class="phone-link">
                                                <i class="fas fa-phone"></i>
                                                <?php echo htmlspecialchars($student['phone']); ?>
                                            </a>
                                        </td>
                                        <td class="action-buttons">
                                            <button class="action-btn view-btn" title="Xem chi tiết" onclick="viewStudentDetails('<?php echo $student['student_id']; ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button> 
                                            <button class="action-btn edit-btn" onclick="showEditPopup(<?php echo $student['student_id']; ?>)" title="Chỉnh sửa"> 
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <!-- <button class="action-btn delete-btn" onclick="window.location.href='delete_student.php?student_id=<?php echo urlencode($student['student_id']); ?>'" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button> -->
                                            <button class="action-btn delete-btn" onclick="confirmDeleteStudent(<?php echo $student['student_id']; ?>)" title="Xóa"> 
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users-slash"></i>
                        <p>Không có sinh viên nào trong phòng này.</p>
                        <button class="add-student-btn">
                            <i class="fas fa-user-plus"></i>
                            Thêm sinh viên mới
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        <div id="student-popup" style="display: none;"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assest/js/main.js"></script>
    <script src="../assest/js/search.js"></script>
    <script src="../assest/js/room_students.js"></script>
</body>
</html>