<?php
include '../config/db_connect.php';

// Lấy danh sách sinh viên cùng thông tin phòng
$sql = "SELECT s.*, r.room_number, r.building 
        FROM Students s 
        LEFT JOIN Rooms r ON s.room_id = r.room_id";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách Sinh viên</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/manage_students.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="students-list-container">
                <h2>Danh sách Sinh viên</h2>
                <a href="add_student.php" class="add-btn">Thêm Sinh viên</a>
                
                <!-- Trường tìm kiếm -->
                <div class="search-container">
                    <input type="text" id="search-input" placeholder="Tìm kiếm sinh viên...">
                </div>

                <table id="students-table">
                    <thead>
                        <tr>
                            <th>Mã sinh viên</th>
                            <th>Họ và tên</th>
                            <th>Email</th>
                            <th>Số điện thoại</th>
                            <th>Phòng</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['student_code']); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td>
                                    <?php
                                    if ($row['room_number']) {
                                        echo 'Tòa ' . htmlspecialchars($row['building']) . ' - Phòng ' . htmlspecialchars($row['room_number']);
                                    } else {
                                        echo 'Chưa phân phòng';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="edit_student.php?student_id=<?php echo $row['student_id']; ?>" class="edit-btn">Sửa</a>
                                    <a href="#" class="delete-btn" data-student-id="<?php echo $row['student_id']; ?>">Xóa</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Container cho phân trang -->
                <div id="pagination" class="pagination-container"></div>
            </div>
        </main>
    </div>

    <!-- Modal xác nhận xóa -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p>Bạn có chắc chắn muốn xóa sinh viên này không?</p>
            <button id="confirmDelete" class="modal-button confirm-btn">Xác nhận</button>
            <button id="cancelDelete" class="modal-button cancel-btn">Hủy</button>
        </div>
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
