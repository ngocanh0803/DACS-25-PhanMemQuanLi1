<?php
include 'db_connect.php';

$electricity_rate = 3000; // VNĐ/kWh
$water_rate = 15000;
// Lấy danh sách tòa nhà
$sql_buildings = "SELECT DISTINCT building FROM Rooms ORDER BY building";
$result_buildings = $conn->query($sql_buildings);

// Xử lý dữ liệu từ biểu mẫu lọc
$building_filter = isset($_GET['building']) ? $_GET['building'] : '';
$floor_filter = isset($_GET['floor']) ? $_GET['floor'] : '';
$room_filter = isset($_GET['room_id']) ? $_GET['room_id'] : '';

// Tạo câu lệnh SQL để lấy danh sách hóa đơn với các bộ lọc
$sql_payments = "
    SELECT p.*, r.building, r.floor, r.room_number
    FROM Payments p
    INNER JOIN Rooms r ON p.room_id = r.room_id
    WHERE 1=1
";

$params = [];
$types = '';

if ($building_filter !== '') {
    $sql_payments .= " AND r.building = ?";
    $params[] = $building_filter;
    $types .= 's';
}

if ($floor_filter !== '') {
    $sql_payments .= " AND r.floor = ?";
    $params[] = $floor_filter;
    $types .= 'i';
}

if ($room_filter !== '') {
    $sql_payments .= " AND r.room_id = ?";
    $params[] = $room_filter;
    $types .= 'i';
}

$sql_payments .= " ORDER BY p.payment_date DESC";

$stmt_payments = $conn->prepare($sql_payments);

if (!empty($params)) {
    $stmt_payments->bind_param($types, ...$params);
}

$stmt_payments->execute();
$result_payments = $stmt_payments->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh Sách Hóa Đơn</title>
    <link rel="stylesheet" href="../assest/css/main.css">
    <link rel="stylesheet" href="../assest/css/payments.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="payments-all-container">
                <h2>Danh Sách Hóa Đơn</h2>
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="building">Tòa nhà</label>
                        <select name="building" id="building">
                            <option value="">Tất cả</option>
                            <?php while ($building = $result_buildings->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($building['building']); ?>" <?php if ($building_filter == $building['building']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($building['building']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="floor">Tầng</label>
                        <select name="floor" id="floor">
                            <option value="">Tất cả</option>
                            <?php
                            // Nếu $floor_filter đã được đặt, hiển thị tầng đã chọn
                            if ($floor_filter !== '') {
                                echo '<option value="' . htmlspecialchars($floor_filter) . '" selected>' . htmlspecialchars($floor_filter) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="room_id">Phòng</label>
                        <select name="room_id" id="room_id">
                            <option value="">Tất cả</option>
                            <?php
                            // Nếu $room_filter đã được đặt, hiển thị phòng đã chọn
                            if ($room_filter !== '') {
                                // Lấy số phòng cho room_id đã chọn
                                $sql_room = "SELECT room_number FROM Rooms WHERE room_id = ?";
                                $stmt_room = $conn->prepare($sql_room);
                                $stmt_room->bind_param("i", $room_filter);
                                $stmt_room->execute();
                                $result_room = $stmt_room->get_result();
                                if ($result_room->num_rows > 0) {
                                    $room = $result_room->fetch_assoc();
                                    echo '<option value="' . htmlspecialchars($room_filter) . '" selected>' . htmlspecialchars($room['room_number']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="filter-btn">Lọc</button>
                </form>
                <table>
                    <thead>
                        <tr>
                            <th>Mã Hóa Đơn</th>
                            <th>Tháng</th>
                            <th>Tòa nhà</th>
                            <th>Tầng</th>
                            <th>Phòng</th>
                            <th>Số điện (kWh)</th>
                            <th>Đơn giá điện/(kWh)</th>
                            <th>Số nước (m³)</th>
                            <th>Đơn giá nước/(m³)</th>
                            <th>Tổng tiền (VNĐ)</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($payment = $result_payments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['payment_code']); ?></td>
                                <td><?php echo date('m/Y', strtotime($payment['payment_date'])); ?></td>
                                <td><?php echo htmlspecialchars($payment['building']); ?></td>
                                <td><?php echo htmlspecialchars($payment['floor']); ?></td>
                                <td><?php echo htmlspecialchars($payment['room_number']); ?></td>
                                <td><?php echo htmlspecialchars($payment['electricity_usage']); ?></td>
                                <td><?php echo number_format($electricity_rate, 0, ',', '.');?></td>
                                <td><?php echo htmlspecialchars($payment['water_usage']); ?></td>
                                <td><?php echo number_format($water_rate, 0, ',', '.'); ?></td>
                                <td><?php echo number_format($payment['total_amount'], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_status'] == 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assest/js/main.js"></script>
    <script src="../assest/js/search.js"></script>>
    <script src="../assest/js/main.js"></script>
    <script>
        $(document).ready(function() {
            $('#building').change(function() {
                var building = $(this).val();

                // Xóa danh sách tầng và phòng
                $('#floor').html('<option value="">Tất cả</option>');
                $('#room_id').html('<option value="">Tất cả</option>');

                if (building !== '') {
                    $.ajax({
                        url: 'get_floors.php',
                        type: 'GET',
                        dataType: 'json', // Mong đợi dữ liệu JSON
                        data: {building: building},
                        success: function(response) {
                            var floors = response.floors;
                            var options = '<option value="">Tất cả</option>';

                            for (var i = 0; i < floors.length; i++) {
                                options += '<option value="' + floors[i] + '">' + floors[i] + '</option>';
                            }

                            $('#floor').html(options);
                        }
                    });
                }
            });

            $('#floor').change(function() {
                var building = $('#building').val();
                var floor = $(this).val();

                // Xóa danh sách phòng
                $('#room_id').html('<option value="">Tất cả</option>');

                if (building !== '' && floor !== '') {
                    $.ajax({
                        url: 'get_rooms.php',
                        type: 'GET',
                        dataType: 'json', // Mong đợi dữ liệu JSON
                        data: {building: building, floor: floor},
                        success: function(response) {
                            var rooms = response.rooms;
                            var options = '<option value="">Tất cả</option>';

                            for (var i = 0; i < rooms.length; i++) {
                                options += '<option value="' + rooms[i].room_id + '">' + rooms[i].room_number + '</option>';
                            }

                            $('#room_id').html(options);
                        }
                    });
                }
            });
        });
    </script>

</body>
</html>
