<?php
// rooms_list.php
include '../config/db_connect.php';

// Lấy danh sách phòng
$sql_rooms = "SELECT * FROM Rooms ORDER BY building, floor, room_number";
$result_rooms = $conn->query($sql_rooms);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Phòng</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/rooms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="rooms-list-container">
                <div class="page-header">
                    <h2><i class="fas fa-door-open"></i> Danh Sách Phòng</h2>
                </div>

                <div class="table-responsive">
                    <table class="rooms-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-building"></i> Tòa nhà</th>
                                <th><i class="fas fa-stairs"></i> Tầng</th>
                                <th><i class="fas fa-door-closed"></i> Phòng</th>
                                <th><i class="fas fa-users"></i> Sức chứa</th>
                                <th><i class="fas fa-info-circle"></i> Trạng thái</th>
                                <th><i class="fas fa-cogs"></i> Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($room = $result_rooms->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($room['building']); ?></td>
                                <td><?php echo htmlspecialchars($room['floor']); ?></td>
                                <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                <td>
                                    <div class="capacity-badge">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($room['capacity']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $room['status']; ?>">
                                        <?php
                                        switch ($room['status']) {
                                            case 'occupied':
                                                echo '<i class="fas fa-user-check"></i> Đang có người ở';
                                                break;
                                            case 'available':
                                                echo '<i class="fas fa-check-circle"></i> Phòng trống';
                                                break;
                                            case 'maintenance':
                                                echo '<i class="fas fa-tools"></i> Đang bảo trì';
                                                break;
                                            default:
                                                echo '<i class="fas fa-question-circle"></i> Không xác định';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <?php if ($room['status'] == 'occupied'): ?>
                                        <a href="view_room_students.php?room_id=<?php echo $room['room_id']; ?>" 
                                           class="action-btn view-btn">
                                            <i class="fas fa-eye"></i>
                                            <span>Xem sinh viên</span>
                                        </a>
                                    <?php else: ?>
                                        <button class="action-btn disabled-btn">
                                            <i class="fas fa-eye-slash"></i>
                                            <span>Xem sinh viên</span>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <?php include 'layout/js.php'; ?>
</body>
</html>