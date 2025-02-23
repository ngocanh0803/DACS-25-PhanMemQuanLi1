<?php
// include 'db_connect.php';

// // // Kiểm tra quyền truy cập
// // if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['manager', 'student_manager', 'accountant', 'admin'])) {
// //     $_SESSION['error'] = "Bạn không có quyền truy cập.";
// //     header('Location: login.php');
// //     exit();
// // }

session_start(); // Khởi động session
include 'db_connect.php';


// Làm sạch và xác thực dữ liệu đầu vào
$room_id = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT);
if ($room_id === false) {
    $_SESSION['error'] = "ID phòng không hợp lệ.";
    header('Location: payments_list.php');
    exit();
}

// Lấy thông tin phòng
$sql_room = "SELECT room_id, building, room_number, status, price FROM Rooms WHERE room_id = ?";
$stmt_room = $conn->prepare($sql_room);
$stmt_room->bind_param("i", $room_id);
$stmt_room->execute();
$result_room = $stmt_room->get_result();
$room = $result_room->fetch_assoc();

if (!$room) {
    $_SESSION['error'] = "Phòng không tồn tại.";
    header('Location: payments_list.php');
    exit();
}

// Kiểm tra trạng thái phòng
if ($room['status'] != 'occupied') {
    $_SESSION['error'] = "Phòng không có người ở hoặc đang bảo trì. Không thể tạo hóa đơn.";
    header('Location: payments_list.php');
    exit();
}

// Định nghĩa hệ số giá
$electricity_rate = 3000; // VNĐ/kWh
$water_rate = 15000; // VNĐ/m³
$room_price = $room['price']; // Lấy từ bảng Rooms
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tạo Hóa Đơn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assest/css/main.css">
    <link rel="stylesheet" href="../assest/css/payments.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container1">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="add-payment-container">
                <h2>Tạo Hóa Đơn - Tòa <?php echo htmlspecialchars($room['building']); ?> Phòng <?php echo htmlspecialchars($room['room_number']); ?></h2>
                <?php
                if (isset($_SESSION['error'])) {
                    echo '<div class="alert alert-danger">'.htmlspecialchars($_SESSION['error']).'</div>';
                    unset($_SESSION['error']);
                }
                ?>
                <form action="process_add_payment.php" method="POST" class="mt-3">
                    <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
                    <div class="mb-3">
                        <label for="month" class="form-label">Tháng <span class="text-danger">*</span></label>
                        <input type="month" name="month" id="month" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="electricity_usage" class="form-label">Số điện tiêu thụ (kWh) <span class="text-danger">*</span></label>
                        <input type="number" name="electricity_usage" id="electricity_usage" class="form-control" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="water_usage" class="form-label">Số nước tiêu thụ (m³) <span class="text-danger">*</span></label>
                        <input type="number" name="water_usage" id="water_usage" class="form-control" min="0" required>
                    </div>
                    <div class="mb-3">
                        <span id="total_amount" class="fw-bold">Tổng tiền: <?php echo $room_price?> VNĐ</span>
                    </div>
                    <button type="submit" class="btn btn-primary">Tạo Hóa Đơn</button>
                    <a href="view_payments.php?room_id=<?php echo htmlspecialchars($room_id); ?>" class="btn btn-secondary ms-2">Quay lại</a>
                </form>
            </div>
        </main>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="../assest/js/main.js"></script>
    <script src="../assest/js/search.js"></script>
    <script>
        <?php if (isset($_SESSION['success'])): ?>
            toastr.success("<?php echo htmlspecialchars($_SESSION['success']); ?>");
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            toastr.error("<?php echo htmlspecialchars($_SESSION['error']); ?>");
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        const electricityRate = <?php echo $electricity_rate; ?>;
        const waterRate = <?php echo $water_rate; ?>;
        const roomPrice = <?php echo $room_price; ?>;

        function calculateTotal() {
            const electricity = parseFloat(document.getElementById('electricity_usage').value) || 0;
            const water = parseFloat(document.getElementById('water_usage').value) || 0;
            const total = (electricity * electricityRate) + (water * waterRate) + roomPrice;
            document.getElementById('total_amount').innerText = 'Tổng tiền: ' + total.toLocaleString('vi-VN') + ' VNĐ';
        }

        document.getElementById('electricity_usage').addEventListener('input', calculateTotal);
        document.getElementById('water_usage').addEventListener('input', calculateTotal);
    </script>
</body>
</html>
