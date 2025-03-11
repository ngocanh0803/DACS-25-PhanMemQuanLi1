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
    <style>
    /* ... (CSS giữ nguyên như trước) ... */
     /* Giao diện danh sách chat giống Telegram/Messenger/WhatsApp */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f0f2f5; /* Nền nhạt */
    }

    .user-list-container {
      /* Thêm container bao quanh danh sách */
       border-radius: 10px; /* Bo tròn */
       overflow: hidden; /* Đảm bảo các phần tử con không tràn ra ngoài */
    }

    .user-list {
        list-style: none; /* Loại bỏ dấu chấm đầu dòng */
        padding: 0;
        margin: 0;
    }

    .user-item {
        display: flex; /* Flexbox cho item */
        align-items: center; /* Căn giữa theo chiều dọc */
        padding: 10px 15px;
        border-bottom: 1px solid #ddd; /* Đường kẻ ngăn cách */
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .user-item:hover {
        background-color: #f0f0f0;
    }
    .user-item.active {
        background-color: #e9e9e9; /* Màu nền khi active */
    }

    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%; /* Hình tròn */
        overflow: hidden; /* Cắt ảnh nếu cần */
        margin-right: 15px;
         display: flex;
         justify-content: center;
         align-items: center;

    }
   .user-avatar img {
      max-width: 100%;
      max-height: 100%;
      object-fit: cover;  /* Căn chỉnh ảnh cho vừa khung */
   }
   .user-avatar i {
    font-size: 24px;  /* Kích thước icon nếu không có ảnh */
     color: #888;  /* Màu icon */
   }

    .user-info {
        flex: 1; /* Chiếm phần còn lại */
    }

    .user-name {
        font-weight: bold;
        margin-bottom: 3px;
    }

    .user-role {
        font-size: 0.9em;
        color: #777;
    }
     /* Ẩn bảng */
    table{
       display: none;
    }
    .search-bar {
        padding: 10px 15px;
        border-bottom: 1px solid #ddd;
         display: flex;
         align-items: center;

    }

    .search-input {
        flex: 1;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 20px; /* Bo tròn */
         outline: none;
    }
    /* Thêm icon search */
     .search-icon {
        margin-right: 8px;
        color: #aaa;
     }

    </style>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS (optional) -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="../../assets/js/main.js"></script>
<script src="../../assets/js/search.js"></script>
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