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
    <title>Quản lý Trạng thái Phòng</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/manage_rooms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="floor-plan-container">
                <div class="dashboard-header">
                    <h2><i class="fas fa-building"></i> Quản lý trạng thái phòng</h2>
                    <p class="subtitle">Xem và cập nhật trạng thái các phòng trong KTX</p>
                </div>

                <div class="control-panel">
                    <div class="selector-group">
                        <div class="select-wrapper">
                            <label for="building-select"><i class="fas fa-home"></i> Tòa nhà</label>
                            <select id="building-select" class="custom-select">
                                <option value="">Chọn tòa nhà</option>
                                <?php foreach ($buildings as $building): ?>
                                    <option value="<?php echo $building; ?>">Tòa nhà <?php echo $building; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="select-wrapper">
                            <label for="floor-select"><i class="fas fa-layer-group"></i> Tầng</label>
                            <select id="floor-select" class="custom-select">
                                <option value="">Chọn tầng</option>
                                <?php foreach ($floors as $floor): ?>
                                    <option value="<?php echo $floor; ?>">Tầng <?php echo $floor; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button id="load-rooms" class="load-btn">
                            <i class="fas fa-sync-alt"></i> Tải Phòng
                        </button>
                    </div>
                </div>

                <div class="floor-map">
                    <div class="floor-info">
                        <h3>
                            <i class="fas fa-map-marker-alt"></i>
                            Tòa nhà <span id="current-building" class="highlight">A</span> - 
                            Tầng <span id="current-floor" class="highlight">1</span>
                        </h3>
                    </div>

                    <div class="rooms-container">
                        <!-- Phòng sẽ được tạo động bằng JavaScript -->
                    </div>
                </div>

                <div class="room-legend">
                    <h4><i class="fas fa-info-circle"></i> Chú thích</h4>
                    <div class="legend-items">
                        <div class="legend-item">
                            <div class="room-indicator available"></div>
                            <span>Phòng trống</span>
                        </div>
                        <div class="legend-item">
                            <div class="room-indicator occupied"></div>
                            <span>Đã có người ở</span>
                        </div>
                        <div class="legend-item">
                            <div class="room-indicator maintenance"></div>
                            <span>Đang sửa chữa</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="notification" class="notification"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/search.js"></script>
    <script src="../../assets/js/manage_rooms.js"></script>
</body>
</html>