<?php
include '../config/db_connect.php';

// Lấy tổng số sinh viên đang ở
$sql_students = "SELECT COUNT(*) AS total_students FROM Students WHERE room_id IS NOT NULL";
$result_students = $conn->query($sql_students);
$total_students = $result_students->fetch_assoc()['total_students'];

// Lấy số phòng theo trạng thái
$sql_rooms = "SELECT status, COUNT(*) AS count FROM Rooms GROUP BY status";
$result_rooms = $conn->query($sql_rooms);

$room_status = [
    'occupied' => 0,
    'maintenance' => 0,
    'available' => 0
];
while ($row = $result_rooms->fetch_assoc()) {
    $room_status[$row['status']] = $row['count'];
}

// Lấy tỉ lệ lấp đầy của từng loại phòng
$sql_capacity = "
    SELECT capacity, 
           SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) AS occupied_rooms,
           COUNT(*) AS total_rooms
    FROM Rooms
    GROUP BY capacity";
$result_capacity = $conn->query($sql_capacity);

$capacity_occupancy = [];
while ($row = $result_capacity->fetch_assoc()) {
    $occupancy_rate = ($row['occupied_rooms'] / $row['total_rooms']) * 100;
    $capacity_occupancy[] = [
        'capacity' => $row['capacity'],
        'occupancy_rate' => round($occupancy_rate, 2)
    ];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê KTX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/statistics.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="statistics-container">
                <h2 class="dashboard-title">Dashboard Thống Kê</h2>

                <div class="stats-overview">
                    <!-- Tổng số sinh viên -->
                    <div class="stat-card students">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Tổng sinh viên</h3>
                            <p class="stat-number"><?php echo $total_students; ?></p>
                            <span class="stat-label">Đang ở KTX</span>
                        </div>
                    </div>

                    <!-- Phòng đang ở -->
                    <div class="stat-card occupied">
                        <div class="stat-icon">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Phòng đang ở</h3>
                            <p class="stat-number"><?php echo $room_status['occupied']; ?></p>
                            <span class="stat-label">Phòng</span>
                        </div>
                    </div>

                    <!-- Phòng bảo trì -->
                    <div class="stat-card maintenance">
                        <div class="stat-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Đang bảo trì</h3>
                            <p class="stat-number"><?php echo $room_status['maintenance']; ?></p>
                            <span class="stat-label">Phòng</span>
                        </div>
                    </div>

                    <!-- Phòng trống -->
                    <div class="stat-card available">
                        <div class="stat-icon">
                            <i class="fas fa-door-closed"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Phòng trống</h3>
                            <p class="stat-number"><?php echo $room_status['available']; ?></p>
                            <span class="stat-label">Phòng</span>
                        </div>
                    </div>
                </div>

                <!-- Biểu đồ tỉ lệ lấp đầy -->
                <div class="occupancy-chart-container">
                    <h3>Tỉ lệ lấp đầy theo loại phòng</h3>
                    <div class="occupancy-grid">
                        <?php foreach ($capacity_occupancy as $occupancy): ?>
                            <div class="occupancy-card">
                                <div class="occupancy-info">
                                    <h4>Phòng <?php echo $occupancy['capacity']; ?> người</h4>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo $occupancy['occupancy_rate']; ?>%"></div>
                                    </div>
                                    <span class="occupancy-rate"><?php echo $occupancy['occupancy_rate']; ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/search.js"></script>
    <script src="../../assets/js/statistics.js"></script>
</body>
</html>

