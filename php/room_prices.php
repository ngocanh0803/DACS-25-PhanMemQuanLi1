<?php
include 'db_connect.php';

// Lấy giá phòng theo từng loại capacity
$sql = "SELECT DISTINCT capacity, price FROM Rooms GROUP BY capacity ORDER BY capacity";
$result = $conn->query($sql);
$prices = [];
while ($row = $result->fetch_assoc()) {
    $prices[$row['capacity']] = $row['price'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thiết lập giá phòng</title>
    <link rel="stylesheet" href="../assest/css/main.css">
    <link rel="stylesheet" href="../assest/css/room_prices.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="price-settings-container">
                <div class="page-header">
                    <h2><i class="fas fa-coins"></i>Thiết lập giá phòng KTX</h2>
                    <p class="subtitle">Quản lý giá phòng theo từng loại</p>
                </div>
                
                <div class="price-cards">
                    <!-- Phòng 2 người -->
                    <div class="price-card">
                        <div class="card-icon">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <h3>Phòng 2 người</h3>
                        <form class="price-form" data-capacity="2">
                            <div class="current-price">
                                <div class="price-label">Giá hiện tại:</div>
                                <div class="price-value">
                                    <span><?php echo number_format($prices['2']); ?> VNĐ/tháng</span>
                                </div>
                            </div>
                            <div class="price-input">
                                <label>Giá mới:</label>
                                <div class="input-group">
                                    <input type="number" name="new_price" value="<?php echo $prices['2']; ?>" step="1000" placeholder="Nhập giá mới">
                                    <span class="currency">VNĐ</span>
                                </div>
                            </div>
                            <button type="submit" class="update-btn">
                                <i class="fas fa-sync-alt"></i>Cập nhật
                            </button>
                        </form>
                    </div>

                    <!-- Phòng 4 người -->
                    <div class="price-card">
                        <div class="card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Phòng 4 người</h3>
                        <form class="price-form" data-capacity="4">
                            <div class="current-price">
                                <div class="price-label">Giá hiện tại:</div>
                                <div class="price-value">
                                    <span><?php echo number_format($prices['4']); ?> VNĐ/tháng</span>
                                </div>
                            </div>
                            <div class="price-input">
                                <label>Giá mới:</label>
                                <div class="input-group">
                                    <input type="number" name="new_price" value="<?php echo $prices['4']; ?>" step="1000" placeholder="Nhập giá mới">
                                    <span class="currency">VNĐ</span>
                                </div>
                            </div>
                            <button type="submit" class="update-btn">
                                <i class="fas fa-sync-alt"></i>Cập nhật
                            </button>
                        </form>
                    </div>

                    <!-- Phòng 8 người -->
                    <div class="price-card">
                        <div class="card-icon">
                            <i class="fas fa-hotel"></i>
                        </div>
                        <h3>Phòng 8 người</h3>
                        <form class="price-form" data-capacity="8">
                            <div class="current-price">
                                <div class="price-label">Giá hiện tại:</div>
                                <div class="price-value">
                                    <span><?php echo number_format($prices['8']); ?> VNĐ/tháng</span>
                                </div>
                            </div>
                            <div class="price-input">
                                <label>Giá mới:</label>
                                <div class="input-group">
                                    <input type="number" name="new_price" value="<?php echo $prices['8']; ?>" step="1000" placeholder="Nhập giá mới">
                                    <span class="currency">VNĐ</span>
                                </div>
                            </div>
                            <button type="submit" class="update-btn">
                                <i class="fas fa-sync-alt"></i>Cập nhật
                            </button>
                        </form>
                    </div>
                </div>

                <div id="notification" class="notification"></div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assest/js/main.js"></script>
    <script src="../assest/js/search.js"></script>
    <script src="../assest/js/room_prices.js"></script>
</body>
</html>