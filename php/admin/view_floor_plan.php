<?php 
include '../config/db_connect.php';

// Lấy danh sách tòa nhà
$sql_building = "SELECT DISTINCT building FROM Rooms";
$result_building = $conn->query($sql_building);
$buildings = [];
while ($row = $result_building->fetch_assoc()) {
    $buildings[] = $row['building'];
}

// Lấy danh sách tầng
$sql_floor = "SELECT DISTINCT floor FROM Rooms ORDER BY floor ASC";
$result_floor = $conn->query($sql_floor);
$floors = [];
while ($row = $result_floor->fetch_assoc()) {
    $floors[] = $row['floor'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xem Sơ Đồ Phòng</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/view_floor_plan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="floor-plan-container">
                <div class="building-selector">
                    <h2>Sơ đồ phòng ký túc xá</h2>
                    <select id="building-select">
                        <option value="">Chọn tòa nhà</option>
                        <?php foreach ($buildings as $building): ?>
                            <option value="<?php echo $building; ?>"><?php echo 'Tòa nhà ' . $building; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="floor-select">
                        <option value="">Chọn tầng</option>
                        <?php foreach ($floors as $floor): ?>
                            <option value="<?php echo $floor; ?>"><?php echo 'Tầng ' . $floor; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Thêm floor-map và floor-info vào đúng vị trí -->
                <div class="floor-map">
                    <div class="floor-info">
                        <h3>Tòa nhà <span id="current-building">A</span> - Tầng <span id="current-floor">1</span></h3>
                    </div>
                    <div class="rooms-container">
                        <!-- Phòng sẽ được tạo động bằng JavaScript -->
                    </div>
                </div>
                <div id="room-popup" class="room-popup">
                    <div class="popup-content">
                        <span class="close-btn">&times;</span>
                        <h3>Số phòng: <span id="popup-room-number"></span></h3>
                        <p>Sức chứa: <span id="popup-room-capacity"></span> người</p>
                        <p>Trạng thái: <span id="popup-room-status"></span></p>
                        <button id="view-details-btn">Chi tiết</button>
                    </div>
                </div>
                <div id="student-popup" class="room-popup">
                    <div class="popup-content">
                        <span style="margin-left: 98%; cursor:pointer" class="close-btn-student">&times;</span>
                        <h3>Danh sách sinh viên trong phòng</h3>
                        <div id="student-list">
                            <!-- Thông tin sinh viên sẽ được chèn vào đây bằng JavaScript -->
                        </div>
                    </div>
                </div>

                <div class="room-legend">
                    <div class="legend-item">
                        <div class="room available"></div>
                        <span>Phòng trống</span>
                    </div>
                    <div class="legend-item">
                        <div class="room occupied"></div>
                        <span>Đã có người ở</span>
                    </div>
                    <div class="legend-item">
                        <div class="room maintenance"></div>
                        <span>Đang sửa chữa</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <?php include 'layout/js.php'; ?>
    <script src="../../assets/js/view_floor_plan.js"></script>
</body>
</html>
