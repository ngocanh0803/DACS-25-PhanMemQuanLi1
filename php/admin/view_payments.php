<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Khởi động session nếu chưa được khởi động
}
include '../config/db_connect.php';


// Làm sạch và xác thực dữ liệu đầu vào
$room_id = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT);
if ($room_id === false) {
    $_SESSION['error'] = "ID phòng không hợp lệ.";
    header('Location: payments_list.php');
    exit();
}

// Thiết lập phân trang
$records_per_page = 10;

// Xác định trang hiện tại
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Tính vị trí bắt đầu
$start_from = ($current_page - 1) * $records_per_page;

// Xử lý các lọc
$filters = [];
$params = [];
$types = "i"; // room_id là integer

$filters[] = "room_id = ?";
$params[] = $room_id;

// Kiểm tra và thêm bộ lọc theo tháng
if (isset($_GET['filter_month']) && !empty($_GET['filter_month'])) {
    $filter_month = date('Y-m-01', strtotime($_GET['filter_month']));
    $filters[] = "DATE_FORMAT(payment_date, '%Y-%m-01') = ?";
    $params[] = $filter_month;
    $types .= "s";
}

// Kiểm tra và thêm bộ lọc theo trạng thái
if (isset($_GET['filter_status']) && !empty($_GET['filter_status'])) {
    $filter_status = $_GET['filter_status'];
    $filters[] = "payment_status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// Xây dựng câu truy vấn với các điều kiện lọc
$where_clause = implode(" AND ", $filters);

// Truy vấn tổng số hóa đơn để tính phân trang
$sql_total = "SELECT COUNT(*) as total FROM Payments WHERE $where_clause";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->bind_param($types, ...$params);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$row_total = $result_total->fetch_assoc();
$total_records = $row_total['total'];
$total_pages = ceil($total_records / $records_per_page);
$stmt_total->close();

// Truy vấn thông tin phòng
$sql_room = "SELECT room_id, building, room_number FROM Rooms WHERE room_id = ?";
$stmt_room = $conn->prepare($sql_room);
$stmt_room->bind_param("i", $room_id);
$stmt_room->execute();
$result_room = $stmt_room->get_result();
$room = $result_room->fetch_assoc();
$stmt_room->close();

if (!$room) {
    $_SESSION['error'] = "Phòng không tồn tại.";
    header('Location: payments_list.php');
    exit();
}

// Truy vấn danh sách hóa đơn với phân trang
$sql_payments = "SELECT * FROM Payments WHERE $where_clause ORDER BY payment_date DESC LIMIT ? OFFSET ?";
$stmt_payments = $conn->prepare($sql_payments);

// Mở rộng mảng tham số để bao gồm LIMIT và OFFSET
$types_paged = $types . "ii";
$params_paged = $params;
$params_paged[] = $records_per_page;
$params_paged[] = $start_from;

// Bind các tham số cho câu truy vấn phân trang
$stmt_payments->bind_param($types_paged, ...$params_paged);
$stmt_payments->execute();
$result_payments = $stmt_payments->get_result();
$stmt_payments->close();

// Truy vấn tổng tiền đã thanh toán và chưa thanh toán
$sql_summary = "SELECT 
                    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) AS total_paid,
                    SUM(CASE WHEN payment_status = 'unpaid' THEN total_amount ELSE 0 END) AS total_unpaid
                FROM Payments
                WHERE room_id = ?";
$stmt_summary = $conn->prepare($sql_summary);
$stmt_summary->bind_param("i", $room_id);
$stmt_summary->execute();
$result_summary = $stmt_summary->get_result();
$summary = $result_summary->fetch_assoc();
$stmt_summary->close();

// Truy vấn dữ liệu cho biểu đồ
$sql_chart = "SELECT DATE_FORMAT(payment_date, '%m/%Y') as month, SUM(electricity_usage) as total_electricity, SUM(water_usage) as total_water 
             FROM Payments 
             WHERE room_id = ? 
             GROUP BY month 
             ORDER BY payment_date ASC";
$stmt_chart = $conn->prepare($sql_chart);
$stmt_chart->bind_param("i", $room_id);
$stmt_chart->execute();
$result_chart = $stmt_chart->get_result();

$months = [];
$electricity = [];
$water = [];

while ($row = $result_chart->fetch_assoc()) {
    $months[] = $row['month'];
    $electricity[] = $row['total_electricity'];
    $water[] = $row['total_water'];
}

$stmt_chart->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh Sách Hóa Đơn</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/payments.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container1">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="payments-history-container">
                <h2>Danh Sách Hóa Đơn - Tòa <?php echo htmlspecialchars($room['building']); ?> Phòng <?php echo htmlspecialchars($room['room_number']); ?></h2>

                <!-- Thêm form lọc -->
                <form method="GET" action="view_payments.php" class="mb-3">
                    <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room_id); ?>">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="filter_month" class="form-label">Lọc theo tháng</label>
                            <input type="month" name="filter_month" id="filter_month" class="form-control" value="<?php echo isset($_GET['filter_month']) ? htmlspecialchars($_GET['filter_month']) : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="filter_status" class="form-label">Lọc theo trạng thái</label>
                            <select name="filter_status" id="filter_status" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="paid" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] === 'paid') ? 'selected' : ''; ?>>Đã thanh toán</option>
                                <option value="unpaid" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] === 'unpaid') ? 'selected' : ''; ?>>Chưa thanh toán</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Lọc</button>
                            <a href="view_payments.php?room_id=<?php echo htmlspecialchars($room_id); ?>" class="btn btn-secondary ms-2">Reset</a>
                        </div>
                    </div>
                </form>

                <!-- Hiển thị bảng danh sách hóa đơn -->
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Tháng</th>
                                <th>Số điện (kWh)</th>
                                <th>Số nước (m³)</th>
                                <th>Tổng tiền (VNĐ)</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_payments->num_rows > 0): ?>
                                <?php while ($payment = $result_payments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('m/Y', strtotime($payment['payment_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($payment['electricity_usage']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['water_usage']); ?></td>
                                        <td><?php echo number_format($payment['total_amount'], 0, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['payment_status'] == 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán'); ?></td>
                                        <td>
                                            <a href="export_invoice.php?payment_id=<?php echo $payment['payment_id']; ?>" class="btn btn-sm btn-success">Xuất PDF</a>
                                            <a href="view_invoice.php?payment_id=<?php echo $payment['payment_id']; ?>" class="btn btn-sm btn-info">Xem Chi Tiết</a>
                                            <a href="update_payment_status.php?payment_id=<?php echo $payment['payment_id']; ?>&room_id=<?php echo $room_id; ?>&status=<?php echo ($payment['payment_status'] === 'paid') ? 'unpaid' : 'paid'; ?>" class="btn btn-sm btn-warning">
                                                <?php echo ($payment['payment_status'] === 'paid') ? 'Đánh dấu chưa thanh toán' : 'Đánh dấu đã thanh toán'; ?>
                                            </a>
                                            <a href="delete_payment.php?payment_id=<?php echo $payment['payment_id']; ?>&room_id=<?php echo $room_id; ?>" class="btn btn-sm btn-danger delete-payment-btn">Xóa</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Không có hóa đơn nào.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Thêm phân trang -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="view_payments.php?room_id=<?php echo $room_id; ?>&page=<?php echo $current_page - 1; ?>&filter_month=<?php echo isset($filter_month) ? htmlspecialchars($_GET['filter_month']) : ''; ?>&filter_status=<?php echo isset($filter_status) ? htmlspecialchars($_GET['filter_status']) : ''; ?>">Previous</a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php if ($i == $current_page) echo 'active'; ?>">
                                    <a class="page-link" href="view_payments.php?room_id=<?php echo $room_id; ?>&page=<?php echo $i; ?>&filter_month=<?php echo isset($filter_month) ? htmlspecialchars($_GET['filter_month']) : ''; ?>&filter_status=<?php echo isset($filter_status) ? htmlspecialchars($_GET['filter_status']) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="view_payments.php?room_id=<?php echo $room_id; ?>&page=<?php echo $current_page + 1; ?>&filter_month=<?php echo isset($filter_month) ? htmlspecialchars($_GET['filter_month']) : ''; ?>&filter_status=<?php echo isset($filter_status) ? htmlspecialchars($_GET['filter_status']) : ''; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

                <!-- Thêm phần thống kê -->
                <div class="summary-container my-4">
                    <h4>Tổng Hóa Đơn</h4>
                    <p>Tổng tiền đã thanh toán: <?php echo number_format($summary['total_paid'], 0, ',', '.'); ?> VNĐ</p>
                    <p>Tổng tiền chưa thanh toán: <?php echo number_format($summary['total_unpaid'], 0, ',', '.'); ?> VNĐ</p>
                </div>

                <!-- Thêm biểu đồ tiêu thụ -->
                <canvas id="consumptionChart" width="400" height="200"></canvas>
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    var ctx = document.getElementById('consumptionChart').getContext('2d');
                    var consumptionChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($months); ?>,
                            datasets: [{
                                label: 'Điện tiêu thụ (kWh)',
                                data: <?php echo json_encode($electricity); ?>,
                                borderColor: 'rgba(75, 192, 192, 1)',
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                fill: false,
                            }, {
                                label: 'Nước tiêu thụ (m³)',
                                data: <?php echo json_encode($water); ?>,
                                borderColor: 'rgba(153, 102, 255, 1)',
                                backgroundColor: 'rgba(153, 102, 255, 0.2)',
                                fill: false,
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                </script>

                <a href="payments_list.php" class="btn btn-secondary mt-3">Quay lại</a>
            </div>
        </main>
    </div>

    <!-- Modal xác nhận xóa hóa đơn -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div style="margin-top: 250px;" class="modal-content">
          <div class="modal-header"> 
            <h5 class="modal-title" id="confirmDeleteModalLabel">Xác Nhận Xóa</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Bạn có chắc chắn muốn xóa hóa đơn này không?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Xóa</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="../../assets/js/manage_payments.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/search.js"></script>
    <script>
        <?php if (isset($_SESSION['success'])): ?>
            toastr.success("<?php echo htmlspecialchars($_SESSION['success']); ?>");
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            toastr.error("<?php echo htmlspecialchars($_SESSION['error']); ?>");
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        // Xử lý modal xác nhận xóa hóa đơn
        $('.delete-payment-btn').on('click', function(e) {
            e.preventDefault();
            var link = $(this).attr('href');
            $('#confirmDeleteModal').data('link', link).modal('show');
        });

        $('#confirmDeleteBtn').on('click', function() {
            var link = $('#confirmDeleteModal').data('link');
            window.location.href = link;
        });
    </script>
</body>
</html>
