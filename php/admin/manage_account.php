<?php

include '../config/db_connect.php';

$sql = "SELECT user_id, username, password, role, is_approved FROM Users";
$result = $conn->query($sql);

// Kiểm tra lỗi truy vấn
if (!$result) {
    die("Lỗi truy vấn: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài khoản</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/account.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="account-list-container">
                <h1>Quản lý tài khoản</h1>
                <button id="add-account-btn" class="add-btn">
                    <i class="fas fa-plus"></i> Thêm tài khoản
                </button>
                <table border="1">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên người dùng</th>
                             <th>Mật khẩu</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if ($result->num_rows > 0) :
                            while ($row = $result->fetch_assoc()):
                                ?>
                                <tr data-id="<?php echo htmlspecialchars($row['user_id']); ?>">
                                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                     <td class="password-cell"><?php echo htmlspecialchars($row['password']); ?></td>
                                    <td><?php echo htmlspecialchars($row['role']); ?></td>
                                    <td><?php echo $row['is_approved'] ? 'Đã phê duyệt' : 'Chưa phê duyệt'; ?></td>
                                    <td>
                                        <button class="edit-btn" data-id="<?php echo htmlspecialchars($row['user_id']); ?>">
                                            <i class="fas fa-edit"></i> Sửa
                                        </button>
                                        <?php if (!$row['is_approved']): ?>
                                        <button class="approve-btn" data-id="<?php echo htmlspecialchars($row['user_id']); ?>">
                                            <i class="fas fa-check"></i> Phê duyệt
                                        </button>
                                        <?php endif; ?>
                                        <button class="delete-btn" data-id="<?php echo htmlspecialchars($row['user_id']); ?>">
                                            <i class="fas fa-trash-alt"></i> Xóa
                                        </button>
                                    </td>
                                </tr>
                                <?php
                            endwhile;
                        else :
                            ?>
                            <tr>
                                <td colspan="6">Không có tài khoản nào.</td>
                            </tr>
                         <?php  endif;
                         ?>
                    </tbody>
                </table>
            </div>
            <div id="notification" class="notification"></div>
            <div id="modal" class="modal">
                  <div class="modal-content">
                    <span class="close">×</span>
                     <h2 id="modal-title">Thêm/Sửa tài khoản</h2>
                    <form id="account-form">
                        <input type="hidden" id="user-id" name="user_id">
                        <label for="username">Tên người dùng:</label>
                        <!-- <input type="text" id="username" name="username" required> -->
                        <input type="text" id="username" name="username" autocomplete="off" required style="color: black; background: white;">

                        <!-- <input type="text" id="username" name="username" required style="color: black; background: white;"> -->
                        <label for="password">Mật khẩu:</label>
                        <input type="password" id="password" name="password">
                        <label for="role">Vai trò:</label>
                        <select id="role" name="role" required>
                            <option value="manager">Quản lý</option>
                            <option value="student_manager">Quản lý sinh viên</option>
                             <option value="accountant">Kế toán</option>
                            <option value="admin">Admin</option>
                        </select>
                         <label for="is_approved">Trạng thái:</label>
                         <select id="is_approved" name="is_approved" required>
                            <option value="1">Đã phê duyệt</option>
                            <option value="0">Chưa phê duyệt</option>
                        </select>
                        <button type="submit" class="submit-btn">Lưu</button>
                    </form>
                 </div>
            </div>
        </main>
    </div>
    <!-- <div id="test-modal" style="display:block; position:fixed; top:50px; left:50px; background:#fff; padding:20px;">
        <label for="username">Tên người dùng:</label>
        <input type="text" id="username" name="username" required>
    </div>
    <script>
        document.getElementById('username').value = "ketoan";
    </script> -->
    <?php include 'layout/js.php'; ?>
    <script src="../../assets/js/manage_account.js"></script>
</body>
</html>