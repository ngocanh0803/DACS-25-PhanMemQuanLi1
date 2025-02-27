<?php
// facilities.php
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
    <title>Quản lý Cơ sở Vật chất</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/manage_facilities.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="facilities-container">
                <div class="dashboard-header">
                    <h2><i class="fas fa-tools"></i> Quản lý Cơ sở Vật chất theo Phòng</h2>
                    <p class="subtitle">Thêm, sửa, xóa cơ sở vật chất cho các phòng trong KTX</p>
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

                        <div class="select-wrapper">
                            <label for="room-select"><i class="fas fa-door-closed"></i> Phòng</label>
                            <select id="room-select" class="custom-select">
                                <option value="">Chọn phòng</option>
                                <!-- Các phòng sẽ được tải động bằng JavaScript -->
                            </select>
                        </div>

                        <button id="load-facilities" class="load-btn">
                            <i class="fas fa-sync-alt"></i> Tải Cơ sở Vật chất
                        </button>
                    </div>
                </div>

                <div class="facilities-list">
                    <h3>
                        <i class="fas fa-list"></i>
                        Danh sách Cơ sở Vật chất 
                        <span id="current-room" class="highlight"></span>
                    </h3>

                    <button id="add-facility-btn" class="add-btn">
                        <i class="fas fa-plus"></i> Thêm Cơ sở Vật chất
                    </button>

                    <table id="facilities-table">
                        <thead>
                            <tr>
                                <th>Mã thiết bị</th>
                                <th>Tên thiết bị</th>
                                <th>Số lượng</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dữ liệu sẽ được tải động bằng JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Thêm/Sửa Cơ sở Vật chất -->
    <div id="facility-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="modal-title"></h2>
            <form id="facility-form">
                <input type="hidden" id="facility-id">
                <div class="form-group">
                    <label for="facility-code">Mã thiết bị</label>
                    <input type="text" id="facility-code" name="facility_code" required>
                </div>
                <div class="form-group">
                    <label for="facility-name">Tên thiết bị</label>
                    <input type="text" id="facility-name" name="facility_name" required>
                </div>
                <div class="form-group">
                    <label for="quantity">Số lượng</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>
                <div class="form-group">
                    <label for="status">Trạng thái</label>
                    <select id="status" name="status">
                        <option value="good">Tốt</option>
                        <option value="broken">Hỏng</option>
                    </select>
                </div>
                <button type="submit" id="save-facility-btn" class="save-btn">Lưu</button>
            </form>
        </div>
    </div>
    <div id="confirm-delete-popup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <h3>Xác nhận Xóa</h3>
            <p>Bạn có chắc chắn muốn xóa cơ sở vật chất này không?</p>
            <button id="confirm-delete-btn">Xác nhận</button>
            <button id="cancel-delete-btn">Hủy</button>
        </div>
    </div>

    <div id="notification" class="notification"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/search.js"></script>
    <script src="../../assets/js/manage_facilities.js"></script>
</body>
</html>
