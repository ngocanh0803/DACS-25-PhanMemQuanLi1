<?php
include 'db_connect.php';

// Lấy danh sách phòng có chỗ trống
$sql_rooms = "
    SELECT r.room_id, r.building, r.room_number, r.capacity, r.status,
           (SELECT COUNT(*) FROM Students s WHERE s.room_id = r.room_id) AS current_occupancy
    FROM Rooms r
    WHERE r.status = 'available' OR r.status = 'occupied'
    ORDER BY r.building, r.room_number
";

$result_rooms = $conn->query($sql_rooms);
$rooms = [];
while ($row = $result_rooms->fetch_assoc()) {
    // Tính số chỗ trống còn lại
    $remaining_capacity = $row['capacity'] - $row['current_occupancy'];
    if ($remaining_capacity > 0) {
        $rooms[] = [
            'room_id' => $row['room_id'],
            'building' => $row['building'],
            'room_number' => $row['room_number'],
            'remaining_capacity' => $remaining_capacity
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Sinh viên</title>
    <link rel="stylesheet" href="../assest/css/main.css">
    <link rel="stylesheet" href="../assest/css/manage_students.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="add-student-container">
                <h2>Thêm Sinh viên Mới</h2>
                <form action="process_add_student.php" method="POST" id="add-student-form">
                    <div class="form-group">
                        <label for="student_code">Mã sinh viên *</label>
                        <input type="text" name="student_code" id="student_code" required>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Họ và tên *</label>
                        <input type="text" name="full_name" id="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" name="email" id="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Số điện thoại</label>
                        <input type="text" name="phone" id="phone">
                    </div>
                    <!-- Lựa chọn phòng (không bắt buộc) -->
                    <div class="form-group">
                        <label for="room_id">Phòng (không bắt buộc)</label>
                        <select style="padding: 10px;" name="room_id" id="room_id">
                            <option value="">Chọn phòng</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room['room_id']); ?>">
                                    Tòa <?php echo htmlspecialchars($room['building']); ?> - Phòng <?php echo htmlspecialchars($room['room_number']); ?> (Còn <?php echo $room['remaining_capacity']; ?> chỗ)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Thêm các trường khác nếu cần -->
                    <button type="submit" class="submit-btn">Thêm Sinh viên</button>
                </form>
            </div>
        </main>
    </div>
    <!-- Thông báo -->
    <div id="notification" class="notification"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assest/js/main.js"></script>
    <script src="../assest/js/search.js"></script>
    <script src="../assest/js/manage_students.js"></script>
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
