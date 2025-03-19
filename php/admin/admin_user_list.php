<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Chỉ admin mới truy cập.");
}
include '../config/db_connect.php';

$adminId = $_SESSION['user_id'];
// Truy vấn đơn giản hơn, không cần join bảng avatars
$sql = "SELECT user_id, username, role FROM Users WHERE user_id != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()){
    $users[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách Chat - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Bootstrap CSS -->
    <!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"> -->
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/admin_user_list.css">
</head>
<body>
<?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="user-list-container">
                 <div class="search-bar">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Tìm kiếm người dùng...">
                </div>
                <ul class="user-list">
                    <?php foreach ($users as $u): ?>
                    <li class="user-item" data-user-id="<?php echo $u['user_id']; ?>">
                        <div class="user-avatar">
                            <!-- Hiển thị ảnh mặc định -->
                            <img src="../../assets/img/default-avatar.png" alt="Default Avatar">
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($u['username']); ?></div>
                            <div class="user-role"><?php echo htmlspecialchars($u['role']); ?></div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </main>
    </div>

<!-- Bootstrap JS (optional) -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<?php include 'layout/js.php'; ?>
<script>
// ... (JavaScript giữ nguyên như trước) ...
$(document).ready(function() {
    // Xử lý click vào user
    $('.user-item').click(function() {
        let userId = $(this).data('user-id');
        window.location.href = "admin_chat_room.php?receiver_id=" + userId;
    });

      // Thêm active class khi click
      $('.user-item').click(function() {
        $('.user-item').removeClass('active');  // Xóa active class ở tất cả
        $(this).addClass('active');  // Thêm active class vào item được click
    });

    // Tìm kiếm
     $('.search-input').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $('.user-item').filter(function() {
            // Tìm trong tên và role
            let userName = $(this).find('.user-name').text().toLowerCase();
            let userRole = $(this).find('.user-role').text().toLowerCase();
            $(this).toggle(userName.indexOf(value) > -1 || userRole.indexOf(value) > -1);
        });
    });

});
</script>
</body>
</html>