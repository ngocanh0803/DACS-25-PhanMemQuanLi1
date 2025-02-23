<?php
// create_contract.php
include 'db_connect.php';
// session_start();

// (Tùy chọn) Kiểm tra quyền
// if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['manager', 'admin', 'student_manager', 'accountant'])) {
//     header("Location: login.php?message=Bạn không có quyền truy cập trang này.&type=error");
//     exit();
// }

/**
 * Lấy danh sách Sinh viên chưa có hợp đồng active
 */
$sql_students = "
    SELECT s.student_id, s.student_code, s.full_name 
    FROM Students s 
    WHERE s.status = 'Active'
      AND NOT EXISTS (
          SELECT 1 FROM Contracts c 
          WHERE c.student_id = s.student_id
            AND c.status = 'active'
      )
";
$result_students = $conn->query($sql_students);

/**
 * Lấy danh sách phòng:
 *  - phòng có status = 'available' hoặc 'occupied'
 *  - tính số SV đang ở => if used_count < capacity => vẫn còn chỗ
 */
$sql_rooms = "
    SELECT 
        r.room_id,
        r.building,
        r.floor,
        r.room_number,
        (CASE r.capacity
            WHEN '2' THEN 2
            WHEN '4' THEN 4
            WHEN '8' THEN 8
        END) AS capacity_num,
        r.price,
        r.status,
        COUNT(st.student_id) AS used_count
    FROM Rooms r
    LEFT JOIN Students st 
        ON st.room_id = r.room_id 
        AND st.status = 'Active'
    WHERE r.status IN ('available', 'occupied')
    GROUP BY 
        r.room_id, 
        r.building, 
        r.floor, 
        r.room_number, 
        r.capacity, 
        r.price, 
        r.status
    HAVING 
        r.status = 'available' 
        OR 
        (r.status = 'occupied' AND used_count < 
            (CASE r.capacity
                WHEN '2' THEN 2
                WHEN '4' THEN 4
                WHEN '8' THEN 8
            END)
        )
    ORDER BY r.building, r.floor, r.room_number
";
$result_rooms = $conn->query($sql_rooms);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tạo Hợp đồng</title>
    <!-- CSS -->
    <link rel="stylesheet" href="../assest/css/main.css">
    <link rel="stylesheet" href="../assest/css/manage_contracts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header & Menu -->
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>

        <main class="content">
            <div class="create-contract-container">
                <h2>Tạo Hợp đồng Mới</h2>
                <form action="process_create_contract.php" method="POST" id="create-contract-form">
                    
                    <!-- CHỌN SINH VIÊN -->
                    <div class="form-group">
                        <label for="student_id">Sinh viên <span class="required">*</span></label>
                        <select id="student_id" name="student_id" required>
                            <option value="">-- Chọn Sinh viên --</option>
                            <?php
                            if ($result_students && $result_students->num_rows > 0) {
                                while ($student = $result_students->fetch_assoc()):
                                    $display = $student['student_code'].' - '.$student['full_name'];
                                    ?>
                                    <option value="<?php echo $student['student_id']; ?>">
                                        <?php echo htmlspecialchars($display); ?>
                                    </option>
                                    <?php
                                endwhile;
                            } else {
                                echo '<option value="">Không có sinh viên nào phù hợp</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- CHỌN PHÒNG -->
                    <div class="form-group">
                        <label for="room_id">Phòng <span class="required">*</span></label>
                        <select id="room_id" name="room_id" required>
                            <option value="">-- Chọn Phòng --</option>
                            <?php
                            if ($result_rooms && $result_rooms->num_rows > 0) {
                                while ($room = $result_rooms->fetch_assoc()):
                                    $capacity   = (int)$room['capacity_num'];
                                    $used_count = (int)$room['used_count'];
                                    $left_space = $capacity - $used_count; 
                                    ?>
                                    <option value="<?php echo $room['room_id']; ?>">
                                        <?php 
                                        // Ví dụ: "Tòa A - Phòng 101 (Còn 2 chỗ)"
                                        echo 'Tòa '.htmlspecialchars($room['building'])
                                             .' - Phòng '.htmlspecialchars($room['room_number'])
                                             .' (Còn '.$left_space.' chỗ)';
                                        ?>
                                    </option>
                                    <?php
                                endwhile;
                            } else {
                                echo '<option value="">Không có phòng khả dụng</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- NGÀY KÝ -->
                    <div class="form-group">
                        <label for="signed_date">Ngày ký Hợp đồng <span class="required">*</span></label>
                        <input type="date" id="signed_date" name="signed_date" required>
                    </div>
                    
                    <!-- NGÀY BẮT ĐẦU -->
                    <div class="form-group">
                        <label for="start_date">Ngày bắt đầu <span class="required">*</span></label>
                        <input type="date" id="start_date" name="start_date" required>
                    </div>
                    
                    <!-- NGÀY KẾT THÚC: Tự động +6 tháng, readonly -->
                    <div class="form-group">
                        <label for="end_date">Ngày kết thúc <span class="required">*</span></label>
                        <input type="date" id="end_date" name="end_date" required readonly>
                    </div>
                    
                    <!-- TIỀN CỌC -->
                    <div class="form-group">
                        <label for="deposit">Tiền đặt cọc (VNĐ) <span class="required">*</span></label>
                        <input type="number" id="deposit" name="deposit" min="0" required>
                    </div>
                    
                    <!-- HIỂN THỊ GIÁ PHÒNG -->
                    <div class="form-group">
                        <!-- Thay đổi label theo yêu cầu: 
                             "Giá của loại phòng có <capacity> người là ..." -->
                        <label for="rent_amount" id="rent_label">Giá của loại phòng là (VNĐ/tháng)</label>
                        <input type="text" id="rent_amount" name="rent_amount" value="" readonly>
                    </div>
                    
                    <!-- ĐIỀU KHOẢN -->
                    <div class="form-group">
                        <label for="terms">Điều khoản <span class="required">*</span></label>
                        <textarea id="terms" name="terms" rows="3" required
                                  placeholder="Điền điều khoản hợp đồng tại đây..."></textarea>
                    </div>
                    
                    <!-- NÚT LƯU -->
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Lưu Hợp Đồng
                    </button>
                </form>
            </div>
        </main>
    </div>

    <!-- Thông báo -->
    <div id="notification" class="notification"></div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assest/js/search.js"></script>
    <script src="../assest/js/main.js"></script>
    <script src="../assest/js/manage_contracts.js"></script>
    <script>
    $(document).ready(function() {

        // Hàm format tiền: 1000000 => "1.000.000"
        function formatCurrency(number) {
            if (!number) return "0";
            // Ép sang float, sau đó dùng regex thêm dấu chấm
            let num = parseFloat(number).toFixed(0); 
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // 1) Tự động tính end_date = start_date + 6 tháng
        $('#start_date').on('change', function() {
            var start = new Date($(this).val());
            start.setMonth(start.getMonth() + 6);
            var dd = ("0" + start.getDate()).slice(-2);
            var mm = ("0" + (start.getMonth() + 1)).slice(-2);
            var yy = start.getFullYear();
            var newEnd = yy + "-" + mm + "-" + dd;
            $('#end_date').val(newEnd);
            updateTerms();
        });

        // 2) Khi chọn phòng => AJAX lấy price, capacity => hiển thị
        $('#room_id').on('change', function() {
            var roomId = $(this).val();
            if (!roomId) {
                $('#rent_amount').val('');
                $('#rent_label').text("Giá của loại phòng là (VNĐ/tháng)");
                updateTerms();
                return;
            }
            $.ajax({
                url: 'get_room_price.php',
                type: 'GET',
                data: { room_id: roomId },
                dataType: 'json', // giả sử get_room_price.php trả JSON: { price: 1000000, capacity: 4 }
                success: function(res) {
                    if (res && res.price) {
                        // Format label: "Giá của loại phòng có <capacity> người là..."
                        if (res.capacity) {
                            $('#rent_label').text(
                                "Giá của loại phòng có " + res.capacity + " người là (VNĐ/tháng)"
                            );
                        }
                        // Hiển thị giá đã format
                        var formatted = formatCurrency(res.price);
                        $('#rent_amount').val(formatted);
                    } else {
                        $('#rent_amount').val('0');
                    }
                    updateTerms();
                },
                error: function() {
                    $('#rent_amount').val('0');
                    updateTerms();
                }
            });
        });

        // 3) Cập nhật điều khoản
        function updateTerms() {
            var studentText = $('#student_id option:selected').text();
            var studentName = (studentText.split(' - ')[1]) ? studentText.split(' - ')[1].trim() : '';
            var deposit     = $('#deposit').val().trim();
            var startDate   = $('#start_date').val().trim();
            var endDate     = $('#end_date').val().trim();
            var rentText    = $('#rent_amount').val().trim();

            // Format cọc
            var depositFormatted = formatCurrency(deposit);

            // Ví dụ: 
            // "Sinh viên [Ten], đã đặt cọc [xxx] VNĐ, từ ngày [start] tới [end], 
            //  giá phòng [rentText] VNĐ/tháng, cam kết tuân thủ..."
            // Tùy bạn chỉnh chu thêm
            if (studentName && deposit && startDate && endDate) {
                var terms = "Sinh viên " + studentName 
                          + " đã đặt cọc " + depositFormatted + " VNĐ. "
                          + "Thời gian ở từ ngày " + startDate + " đến ngày " + endDate + ". "
                          + "Giá phòng: " + rentText + " VNĐ/tháng. "
                          + "Sinh viên cam kết tuân thủ mọi quy định của KTX.";
                $('#terms').val(terms);
            } else {
                $('#terms').val('');
            }
        }
        // Gọi updateTerms khi thay đổi deposit, start_date, end_date, ...
        $('#student_id, #deposit, #start_date, #end_date').on('change keyup', updateTerms);

        // 4) Thông báo (nếu có từ GET)
        var notification = $('#notification');
        function showNotification(msg, isError = false) {
            notification.text(msg);
            if (isError) {
                notification.addClass('error');
            } else {
                notification.removeClass('error');
            }
            notification.fadeIn();
            setTimeout(function() { notification.fadeOut(); }, 3000);
        }
        <?php
        if (isset($_GET['message'])) {
            $msg  = addslashes(htmlspecialchars($_GET['message']));
            $type = isset($_GET['type']) && $_GET['type'] === 'error' ? 'error' : '';
            echo "showNotification('$msg', ".($type === 'error'?'true':'false').");";
        }
        ?>
    });
    </script>
</body>
</html>
