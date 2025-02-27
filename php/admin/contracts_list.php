<?php
// session_start();
include '../config/db_connect.php';

// (Tuỳ ý) Kiểm tra xem user đã đăng nhập chưa, hoặc có quyền truy cập trang này không
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit;
// }

// Thiết lập các biến phân trang
$limit = 10; // số bản ghi trên mỗi trang
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $limit;

// Lấy từ khoá tìm kiếm từ GET
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Xây dựng câu truy vấn đếm tổng số hợp đồng (để phục vụ phân trang)
$sql_count = "SELECT COUNT(*) AS total
              FROM Contracts c
              LEFT JOIN Students s ON c.student_id = s.student_id
              LEFT JOIN Rooms r ON c.room_id = r.room_id
              WHERE 1 "; 

// Xây dựng câu truy vấn chính để lấy dữ liệu hợp đồng
$sql_main = "SELECT c.contract_id, c.contract_code,
                    s.full_name,
                    r.building, r.room_number,
                    c.start_date, c.end_date, c.status
             FROM Contracts c
             LEFT JOIN Students s ON c.student_id = s.student_id
             LEFT JOIN Rooms r ON c.room_id = r.room_id
             WHERE 1 ";

// Nếu có từ khoá tìm kiếm, thêm điều kiện vào WHERE
// Tìm kiếm trên: tên sinh viên, mã hợp đồng, tòa, số phòng
if ($searchTerm !== '') {
    // Sử dụng prepared statement để tránh SQL injection
    $searchClause = " AND (
        s.full_name LIKE ? 
        OR c.contract_code LIKE ?
        OR r.building LIKE ?
        OR r.room_number LIKE ?
    )";
    $sql_count .= $searchClause;
    $sql_main  .= $searchClause;
}

// Thêm phần ORDER BY + LIMIT/OFFSET
$sql_main .= " ORDER BY c.contract_id DESC 
               LIMIT ? OFFSET ?";

// Chuẩn bị statement cho đếm tổng
$stmt_count = $conn->prepare($sql_count);
if ($searchTerm !== '') {
    $likeStr = "%{$searchTerm}%";
    $stmt_count->bind_param('ssss',
        $likeStr, $likeStr, $likeStr, $likeStr
    );
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$totalRow = $result_count->fetch_assoc()['total'] ?? 0;
$stmt_count->close();

// Tổng số trang (dùng ceil để làm tròn lên)
$totalPage = $totalRow > 0 ? ceil($totalRow / $limit) : 1;

// Chuẩn bị statement cho lấy dữ liệu chính
$stmt_main = $conn->prepare($sql_main);
if ($searchTerm !== '') {
    // Với LIMIT và OFFSET là kiểu integer, ta bind theo i (integer)
    $likeStr = "%{$searchTerm}%";
    $stmt_main->bind_param('ssssii',
        $likeStr, $likeStr, $likeStr, $likeStr,
        $limit, $offset
    );
} else {
    $stmt_main->bind_param('ii', $limit, $offset);
}
$stmt_main->execute();
$result_contracts = $stmt_main->get_result();
$stmt_main->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách Hợp đồng</title>
    <!-- Link tới CSS -->   
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/manage_contracts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <?php include 'layout/header.php'; ?>

    <div class="container">
        <!-- Menu -->
        <?php include 'layout/menu.php'; ?>

        <main class="content">
            <div class="contracts-list-container">
                <h2>Danh sách Hợp đồng</h2>
                <a href="create_contract.php" class="add-btn">
                    <i class="fas fa-plus"></i> Thêm Hợp Đồng
                </a>

                <!-- Form tìm kiếm -->
                <form method="GET" class="search-form">
                    <input type="text" name="search" 
                           placeholder="Tìm kiếm theo tên SV, mã HĐ, tòa, phòng..."
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>

                <!-- Bảng danh sách hợp đồng -->
                <table id="contracts-table">
                    <thead>
                        <tr>
                            <th>Mã HĐ</th>
                            <th>Sinh viên</th>
                            <th>Tòa - Phòng</th>
                            <th>Ngày bắt đầu</th>
                            <th>Ngày kết thúc</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_contracts->num_rows > 0): ?>
                            <?php while ($contract = $result_contracts->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($contract['contract_code']); ?></td>
                                    <td><?php echo htmlspecialchars($contract['full_name']); ?></td>
                                    <td>
                                        <?php
                                            $building = $contract['building'];
                                            $roomNum  = $contract['room_number'];
                                            if ($building && $roomNum) {
                                                echo "Tòa " . htmlspecialchars($building) 
                                                   . " - Phòng " . htmlspecialchars($roomNum);
                                            } else {
                                                echo 'Chưa phân phòng';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $startDate = date("d/m/Y", strtotime($contract['start_date']));
                                            echo htmlspecialchars($startDate);
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $endDate = date("d/m/Y", strtotime($contract['end_date']));
                                            echo htmlspecialchars($endDate);
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        switch ($contract['status']) {
                                            case 'active':
                                                echo '<span class="status active">Hoạt động</span>';
                                                break;
                                            case 'terminated':
                                                echo '<span class="status terminated">Chấm dứt</span>';
                                                break;
                                            case 'expired':
                                                echo '<span class="status expired">Hết hạn</span>';
                                                break;
                                            default:
                                                echo '<span class="status unknown">Không xác định</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="edit_contract.php?contract_id=<?php echo $contract['contract_id']; ?>"
                                           class="edit-btn" title="Sửa Hợp Đồng">
                                           <i class="fas fa-edit"></i> Sửa
                                        </a>
                                        <!-- Chấm dứt hợp đồng (cần JS + server xử lý) -->
                                        <a href="javascript:void(0);" 
                                           class="terminate-btn" 
                                           data-contract-id="<?php echo $contract['contract_id']; ?>"
                                           title="Chấm dứt Hợp Đồng">
                                           <i class="fas fa-trash-alt"></i> Chấm dứt
                                        </a>
                                        <a href="renew_contract.php?contract_id=<?php echo $contract['contract_id']; ?>" 
                                            class="renew-btn"
                                            title="Gia hạn Hợp đồng">
                                            <i class="fas fa-calendar-plus"></i> Gia hạn
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-data">
                                    Không có hợp đồng nào để hiển thị.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Hiển thị phân trang -->
                <?php if ($totalPage > 1): ?>
                    <div class="pagination-container">
                        <!-- Nút về trang trước -->
                        <?php if ($page > 1): ?>
                            <a class="pagination-btn" 
                               href="?search=<?php echo urlencode($searchTerm); ?>&page=<?php echo $page-1; ?>">
                               <i class="fas fa-chevron-left"></i> Trước
                            </a>
                        <?php endif; ?>

                        <!-- Liệt kê các trang -->
                        <?php
                        // Có thể tuỳ chỉnh hiển thị tất cả, hoặc chỉ hiển thị một dải trang
                        for ($i = 1; $i <= $totalPage; $i++):
                            $active = ($i === $page) ? 'active' : '';
                            ?>
                            <a class="pagination-btn <?php echo $active; ?>" 
                               href="?search=<?php echo urlencode($searchTerm); ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Nút tới trang sau -->
                        <?php if ($page < $totalPage): ?>
                            <a class="pagination-btn" 
                               href="?search=<?php echo urlencode($searchTerm); ?>&page=<?php echo $page+1; ?>">
                               Sau <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal xác nhận chấm dứt (sample - tuỳ bạn muốn xử lý) -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p>Bạn có chắc chắn muốn chấm dứt hợp đồng này không?</p>
            <button id="confirmTerminate" class="modal-button confirm-btn">Xác nhận</button>
            <button id="cancelTerminate" class="modal-button cancel-btn">Hủy</button>
        </div>
    </div>

    <!-- Thông báo -->
    <div id="notification" class="notification"></div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../assets/js/search.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/manage_contracts.js"></script>
    <script>
        $(document).ready(function() {
            var notification = $('#notification');
            var confirmModal = $('#confirmModal');
            var confirmBtn   = $('#confirmTerminate');
            var cancelBtn    = $('#cancelTerminate');
            var closeSpan    = $('#confirmModal .close');
            var currentContractId = null; // nơi lưu ID hợp đồng

            // Khi click "Chấm dứt"
            $('.terminate-btn').on('click', function(e) {
                e.preventDefault();
                // Lấy ID hợp đồng từ data-attribute
                currentContractId = $(this).data('contract-id');
                // Hiển thị modal xác nhận
                confirmModal.show();
            });

            // Đóng modal (nút X hoặc nút "Hủy")
            closeSpan.on('click', function() { confirmModal.hide(); });
            cancelBtn.on('click', function() { confirmModal.hide(); });

            // Xác nhận chấm dứt
            confirmBtn.on('click', function() {
                confirmModal.hide();

                // Ở đây không thể dùng `$(this).data('contract-id')` 
                // vì `this` là nút #confirmTerminate (không chứa data).
                // Nên ta dùng biến `currentContractId`:
                if (currentContractId) {
                    $.post('process_terminate_contract.php',
                    { contract_id: currentContractId },
                    function(response) {
                        // Xử lý JSON trả về
                        if (response.success) {
                            showNotification(response.message || 'Đã chấm dứt hợp đồng!');
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            showNotification(response.message || 'Không thể chấm dứt hợp đồng!', true);
                        }
                    }, 'json')
                    .fail(function() {
                        showNotification('Lỗi server! Không thể chấm dứt hợp đồng.', true);
                    });
                } else {
                    showNotification('Không xác định được ID hợp đồng!', true);
                }
            });

            // Hàm hiển thị thông báo
            function showNotification(message, isError = false) {
                notification.text(message);
                if (isError) {
                    notification.addClass('error');
                } else {
                    notification.removeClass('error');
                }
                notification.fadeIn();

                setTimeout(function() {
                    notification.fadeOut();
                }, 3000);
            }

            // Hiển thị thông báo nếu có từ GET
            <?php
            if (isset($_GET['message'])) {
                $msg  = addslashes(htmlspecialchars($_GET['message']));
                $type = isset($_GET['type']) && $_GET['type'] === 'error' ? 'error' : '';
                echo "showNotification('$msg', " . ($type === 'error' ? 'true' : 'false') . ");";
            }
            ?>
        });

    </script>                       

</body>
</html>
