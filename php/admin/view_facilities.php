<?php
// facilities.php
include '../config/db_connect.php'; // Bao gồm tệp kết nối cơ sở dữ liệu
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xem Cơ Sở Vật Chất</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/facilities.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="facility-management-container">
                <div class="facility-management-header">
                    <h2><i class="fas fa-tools"></i> Quản Lý Cơ Sở Vật Chất Theo Phòng</h2>
                    <p>Chọn phòng để xem và quản lý cơ sở vật chất hiện có.</p>
                </div>
                <label for="roomSelect">Chọn phòng:</label>
                <select id="roomSelect">
                    <option value="" disabled selected>-- Vui lòng chọn phòng --</option>
                    <!-- Các option phòng sẽ được điền từ cơ sở dữ liệu -->
                    <?php
                    $sql = "SELECT room_id, room_code FROM Rooms";
                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['room_id']}'>{$row['room_code']}</option>";
                    }
                    ?>
                </select>

                <div id="facilityContainer" class="facility-container">
                    <p>Vui lòng chọn phòng để xem cơ sở vật chất.</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Thông Báo -->
    <div id="notification" class="notification"></div>
    
    <?php include 'layout/js.php'; ?>
    <script src="../../assets/js/facilities.js"></script>
</body>
</html>
