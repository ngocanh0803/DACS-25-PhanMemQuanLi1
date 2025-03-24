<?php
session_start();
// Xác thực người dùng: Chỉ sinh viên mới được truy cập
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

$studentCode = $_SESSION['username'];

// Kết nối database
include '../config/db_connect.php';

// Lấy thông tin sinh viên và phòng
$sqlStudent = "SELECT s.student_id, s.full_name, s.major, s.year_of_study,
               s.room_id, r.room_code, r.building, r.floor, r.room_number
        FROM Students s
        LEFT JOIN Rooms r ON s.room_id = r.room_id
        WHERE s.student_code = ?";
$stmtStudent = $conn->prepare($sqlStudent);

if ($stmtStudent === false) {
    die("Lỗi SQL: " . $conn->error);
}

$stmtStudent->bind_param("s", $studentCode);
$stmtStudent->execute();
$resultStudent = $stmtStudent->get_result();

if ($resultStudent->num_rows === 0) {
    die("Không tìm thấy thông tin sinh viên.");
}

$student = $resultStudent->fetch_assoc();
$stmtStudent->close();

// Lấy hợp đồng active
$sqlContract = "SELECT contract_id, contract_code, start_date, end_date
                 FROM Contracts
                 WHERE student_id = ? AND status = 'active'
                 LIMIT 1";
$stmtContract = $conn->prepare($sqlContract);

if ($stmtContract === false) {
    die("Lỗi SQL: " . $conn->error);
}

$stmtContract->bind_param("i", $student['student_id']);
$stmtContract->execute();
$resultContract = $stmtContract->get_result();
$contract = $resultContract->fetch_assoc();
$stmtContract->close();
$conn->close();

// Nếu không có hợp đồng active, hiển thị cảnh báo
$noContractWarning = !$contract ? "Bạn chưa có hợp đồng active hoặc hợp đồng đã hết hiệu lực." : null;

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đơn Xin Rời Phòng - Hết Hạn Hợp Đồng</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- jsPDF & html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <!-- CSS chung -->
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <!-- CSS tùy chỉnh cho form đơn -->
    <style>
        /* Kiểu dáng đơn xin rời phòng theo phong cách hợp đồng giấy */
        .form-container {
            max-width: 900px;
            margin: 20px auto;
            background: #fff;
            padding: 40px;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            font-family: 'Times New Roman', serif;
        }
        .form-container h1, .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            font-size: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 8px;
        }
        .form-group textarea, .form-group input[type="file"], .form-group p { /* Thêm style cho <p> trong form-group để thống nhất */
            padding: 10px;
            font-size: 16px;
            border: 1px solid #999;
            border-radius: 4px;
            width: 100%;
        }
        .btn-group {
            text-align: center;
            margin-top: 30px;
        }
        .btn-submit, .btn-export, .status-btn { /* Thêm .status-btn vào đây để style chung */
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            font-size: 18px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 10px;
        }
        .btn-submit:hover, .btn-export:hover, .status-btn:hover { /* Thêm .status-btn:hover */
            background-color: #0056b3;
        }
        .status-btn { /* Style riêng cho nút trạng thái nếu cần */
            background-color: #6c757d;
        }
        .status-btn:hover {
            background-color: #5a6268;
        }
        /* Phần cam kết, chữ ký (nếu cần cho form này) */
        .signature {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature div {
            width: 45%;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;
            font-style: italic;
        }
        .warning {
            color: red;
            font-style: italic;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<?php include 'layout/sidebar.php'; ?>

<div class="main-content">
    <?php include 'layout/header.php'; ?>

    <div class="content">
        <div class="form-container" id="departure-form-container">
            <h1>ĐƠN XIN RỜI PHÒNG</h1>
            <p style="text-align: center; font-style: italic;">(Khi hết hạn hợp đồng - Mẫu đơn phong cách hợp đồng giấy)</p>

            <?php if ($noContractWarning): ?>
                <p class="warning"><?php echo $noContractWarning; ?></p>
            <?php else: ?>
                <div class="section">
                    <h2>I. Thông tin Sinh viên</h2>
                    <div class="form-group">
                        <label>Mã Sinh viên:</label>
                        <p><?php echo htmlspecialchars($studentCode); ?></p>
                    </div>
                    <div class="form-group">
                        <label>Họ và Tên:</label>
                        <p><?php echo htmlspecialchars($student['full_name']); ?></p>
                    </div>
                    <div class="form-group">
                        <label>Ngành/Lớp:</label>
                        <p><?php echo htmlspecialchars($student['major']); ?></p>
                    </div>
                    <div class="form-group">
                        <label>Khóa học (Năm thứ):</label>
                        <p><?php echo htmlspecialchars($student['year_of_study']); ?></p>
                    </div>
                </div>

                <div class="section">
                    <h2>II. Thông tin Phòng Hiện Tại</h2>
                    <?php if ($student['room_code']): ?>
                        <div class="form-group">
                            <label>Thông tin phòng:</label>
                            <p>
                                Tòa nhà: <?php echo htmlspecialchars($student['building']); ?>,
                                Tầng: <?php echo htmlspecialchars($student['floor']); ?>,
                                Phòng: <?php echo htmlspecialchars($student['room_number']); ?> (Mã phòng: <?php echo htmlspecialchars($student['room_code']); ?>)
                            </p>
                        </div>
                    <?php else: ?>
                        <p class="warning">Bạn chưa được phân phòng hoặc phòng không tồn tại.</p>
                    <?php endif; ?>
                </div>

                <div class="section">
                    <h2>III. Thông tin Hợp Đồng Hiện Hành</h2>
                    <div class="form-group">
                        <label>Mã Hợp đồng:</label>
                        <p><?php echo htmlspecialchars($contract['contract_code']); ?></p>
                    </div>
                    <div class="form-group">
                        <label>Ngày bắt đầu hợp đồng:</label>
                        <p><?php echo htmlspecialchars($contract['start_date']); ?></p>
                    </div>
                    <div class="form-group">
                        <label>Ngày kết thúc hợp đồng:</label>
                        <p><?php echo htmlspecialchars($contract['end_date']); ?></p>
                    </div>
                </div>

                <form id="departureExpireForm" action="ajax/process_departure_expire.php" method="POST">
                    <!-- Input ẩn student_id và contract_id -->
                    <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                    <input type="hidden" name="contract_id" value="<?php echo $contract['contract_id']; ?>">

                    <div class="section">
                        <h2>IV. Lý do không gia hạn / rời phòng</h2>
                        <div class="form-group">
                            <label for="reason">Lý do:</label>
                            <textarea name="reason" id="reason" rows="4" placeholder="VD: Tôi sẽ chuyển đến chỗ ở khác..." required></textarea>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="button" class="btn-submit" id="submitRequest">Gửi đơn rời phòng</button>
                        <button type="button" class="btn-export" id="exportPDF">Xuất PDF</button>
                        <button type="button" class="status-btn" onclick="window.location.href='departure_status.php'">Xem trạng thái</button>
                    </div>
                    <div id="error-messages" style="color: red; margin-top: 10px;"></div> <!-- Chỗ hiển thị lỗi -->
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
        // Xuất PDF sử dụng jsPDF và html2canvas
        document.getElementById('exportPDF').addEventListener('click', function() {
            if (typeof window.jspdf === 'undefined') {
                console.error("jsPDF is not loaded!");
                alert("Lỗi: Thư viện jsPDF chưa được tải. Vui lòng tải lại trang.");
                return;
            }

            const jsPDFLib = window.jspdf.jsPDF;
            const doc = new jsPDFLib('p', 'pt', 'a4');
            const content = document.getElementById('departure-form-container');

            if (!content) {
                console.error("Không tìm thấy phần tử form với ID 'departure-form-container'");
                alert("Lỗi: Không tìm thấy form để xuất PDF.");
                return;
            }

            html2canvas(content, { scale: 0.7 }).then(function(canvas) {
                const imgData = canvas.toDataURL('image/png');
                const imgProps = doc.getImageProperties(imgData);
                const pdfWidth = doc.internal.pageSize.getWidth() - 40;
                const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
                doc.addImage(imgData, 'PNG', 20, 20, pdfWidth, pdfHeight);
                doc.save('don_xin_roi_phong_hethanhopdong.pdf');
            }).catch(function(error) {
                console.error("Lỗi xuất PDF:", error);
                alert("Lỗi xuất PDF: " + error.message);
            });
        });

        document.getElementById('submitRequest').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('departureExpireForm'));
            fetch('ajax/process_departure_expire.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    if (data.messages && data.messages.length > 0) {
                        data.messages.forEach(msg => console.log(msg));
                    }
                    window.location.href = 'departure_status.php';
                } else {
                    if (data.errors && data.errors.length > 0) {
                        data.errors.forEach(err => console.error(err));
                        let errorContainer = document.getElementById('error-messages');
                        errorContainer.innerHTML = '';
                        data.errors.forEach(err => {
                            const errorElement = document.createElement('p');
                            errorElement.textContent = err;
                            errorContainer.appendChild(errorElement);
                        });
                    }
                }
            })
            .catch(error => {
                console.error("Lỗi gửi đơn:", error);
                alert("Lỗi khi gửi đơn xin rời phòng (lỗi kết nối).");
            });
        });
</script>

</body>
</html>