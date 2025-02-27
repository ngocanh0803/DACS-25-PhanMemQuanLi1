<?php
include '../config/db_connect.php';

if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];

    // Lấy thông tin sinh viên
    $sql = "SELECT * FROM Students WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if (!$student) {
        header("Location: students_list.php?message=Sinh viên không tồn tại.&type=error");
        exit;
    }

    // Lấy danh sách phòng có chỗ trống và phòng hiện tại của sinh viên
    $sql_rooms = "
        SELECT r.room_id, r.building, r.room_number, r.capacity, r.status,
               (SELECT COUNT(*) FROM Students s WHERE s.room_id = r.room_id) AS current_occupancy
        FROM Rooms r
        WHERE r.status = 'available' OR r.status = 'occupied' OR r.room_id = ?
        ORDER BY r.building, r.room_number
    ";
    $stmt_rooms = $conn->prepare($sql_rooms);
    $stmt_rooms->bind_param("i", $student['room_id']);
    $stmt_rooms->execute();
    $result_rooms = $stmt_rooms->get_result();
    $rooms = [];
    while ($row = $result_rooms->fetch_assoc()) {
        // Tính số chỗ trống còn lại
        $remaining_capacity = $row['capacity'] - $row['current_occupancy'];
        // Nếu là phòng hiện tại của sinh viên, cho phép hiển thị dù đã đầy
        if ($row['room_id'] == $student['room_id'] || $remaining_capacity > 0) {
            $rooms[] = [
                'room_id' => $row['room_id'],
                'building' => $row['building'],
                'room_number' => $row['room_number'],
                'remaining_capacity' => $remaining_capacity + ($row['room_id'] == $student['room_id'] ? 1 : 0)
            ];
        }
    }

    // Đóng statement
    $stmt->close();
    $stmt_rooms->close();
} else {
    header("Location: students_list.php?message=ID sinh viên không hợp lệ.&type=error");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa Sinh viên</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/manage_students.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="edit-student-container">
                <h2>Chỉnh sửa Sinh viên</h2>
                <form action="process_edit_student.php" method="POST" id="edit-student-form">
                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>">
                    <div class="form-group">
                        <label for="student_code">Mã sinh viên *</label>
                        <input type="text" name="student_code" id="student_code" value="<?php echo htmlspecialchars($student['student_code']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Họ và tên *</label>
                        <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Số điện thoại</label>
                        <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($student['phone']); ?>">
                    </div>
                    <!-- Lựa chọn phòng (có thể để trống để xóa phòng của sinh viên) -->
                    <div class="form-group">
                        <label for="room_id">Phòng (có thể thay đổi)</label>
                        <select style="padding: 10px;" name="room_id" id="room_id">
                            <option value="">Chọn phòng</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room['room_id']); ?>" <?php if ($student['room_id'] == $room['room_id']) echo 'selected'; ?>>
                                    Tòa <?php echo htmlspecialchars($room['building']); ?> - Phòng <?php echo htmlspecialchars($room['room_number']); ?> (Còn <?php echo $room['remaining_capacity']; ?> chỗ)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Thêm các trường khác nếu cần -->
                    <button type="submit" class="submit-btn">Cập nhật Sinh viên</button>
                </form>
            </div>
        </main>
    </div>
    <!-- Thông báo -->
    <div id="notification" class="notification"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/search.js"></script>
    <script src="../../assets/js/manage_students.js"></script>
    <script>
        $(document).ready(function() {
            var notification = $('#notification');

            // Hàm hiển thị thông báo
            function showNotification(message, isError = false) {
                notification.text(message);
                if (isError) {
                    notification.addClass('error');
                } else {
                    notification.removeClass('error');
                }
                notification.fadeIn();

                setTimeout(function() {
                    notification.fadeOut();
                }, 3000);
            }

            // Kiểm tra nếu có thông báo từ URL
            <?php
            if (isset($_GET['message'])) {
                $msg = addslashes(htmlspecialchars($_GET['message']));
                $type = isset($_GET['type']) && $_GET['type'] == 'error' ? 'error' : '';
                echo "showNotification('$msg', " . ($type === 'error' ? 'true' : 'false') . ");";
            }
            ?>
        });
    </script>
</body>
</html>
