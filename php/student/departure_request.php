<?php
session_start();

// Xác thực người dùng: Chỉ sinh viên mới được truy cập
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

$studentCode = $_SESSION['username']; // Đổi tên biến cho rõ ràng hơn
$noContractWarning = ""; // Khởi tạo biến cảnh báo

// Kết nối database
include '../config/db_connect.php';

// Truy vấn thông tin hợp đồng hiện tại của sinh viên (chỉ hợp đồng active)
$sqlContract = "SELECT contract_id, contract_code, start_date, end_date
                FROM Contracts
                WHERE student_id = (SELECT student_id FROM Students WHERE student_code = ?)
                AND status = 'active'
                LIMIT 1";

$stmt = $conn->prepare($sqlContract);

if ($stmt === false) { // Kiểm tra lỗi prepare statement
    die("Lỗi SQL: " . $conn->error); // Hoặc xử lý lỗi theo cách phù hợp
}

$stmt->bind_param("s", $studentCode);
$stmt->execute();
$resultContract = $stmt->get_result();

if ($resultContract->num_rows > 0) {
    $contract = $resultContract->fetch_assoc();
    $contractId = $contract['contract_id']; // Đổi tên biến cho rõ ràng hơn
} else {
    // Nếu không có hợp đồng active, sinh viên không thể xin rời phòng
    $noContractWarning = "Bạn không có hợp đồng hiện hành, không thể gửi đơn xin rời phòng.";
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đơn xin rời phòng trước hạn hợp đồng</title>
    <!-- Thư viện và CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <link rel="stylesheet" href="../../assets/css/student_departure_request.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Nội dung chính -->
    <div class="main-content">
        <!-- Header -->
        <?php include 'layout/header.php'; ?>

        <div class="content">
            <div class="form-container" id="departure-form-container">
                <h1>ĐƠN XIN RỜI PHÒNG TRƯỚC HẠN HỢP ĐỒNG</h1>
                <p style="text-align: center; font-style: italic;">(Mẫu đơn theo phong cách hợp đồng giấy)</p>
                <!-- Hiển thị cảnh báo nếu không có hợp đồng -->
                <?php if ($noContractWarning): ?>
                    <p class="warning"><?php echo $noContractWarning; ?></p>
                <?php else: ?>
                    <!-- Thông tin hợp đồng hiện hành -->
                    <div class="section">
                        <h2>Thông tin hợp đồng hiện hành</h2>
                        <p><strong>Mã hợp đồng:</strong> <?php echo htmlspecialchars($contract['contract_code']); ?></p>
                        <p><strong>Ngày nhận phòng:</strong> <?php echo htmlspecialchars($contract['start_date']); ?></p>
                        <p><strong>Ngày kết thúc dự kiến:</strong> <?php echo htmlspecialchars($contract['end_date']); ?></p>
                    </div>
                    <!-- Form đơn xin rời phòng -->
                    <form id="departureRequestForm" action="ajax/process_departure_request.php" method="POST" enctype="multipart/form-data">
                        <!-- Input ẩn contract_id -->
                        <input type="hidden" name="contract_id" value="<?php echo htmlspecialchars($contractId); ?>">
                        <div class="section">
                            <h2>I. Lý do xin rời phòng</h2>
                            <div class="form-group">
                                <label for="reason">Lý do:</label>
                                <textarea id="reason" name="reason" rows="4" placeholder="Nhập lý do xin rời phòng..." required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="documents">Tài liệu kèm theo (nếu có):</label>
                                <input type="file" id="documents" name="documents[]" multiple>
                                <p class="file-limit-info" style="font-size: 0.9em; color: #777; margin-top: 5px;">
                                    (Các file được phép: jpg, png, gif, pdf, doc, docx. Kích thước tối đa mỗi file: 5MB)
                                </p>
                            </div>
                            
                        </div>
                        <div class="section">
                            <h2>II. Cam kết</h2>
                            <p>
                                Tôi cam kết rằng các thông tin trên là đúng sự thật và tôi hiểu rằng việc xin rời phòng trước hạn hợp đồng sẽ ảnh hưởng đến quyền lợi của mình theo quy định của ký túc xá.
                            </p>
                        </div>
                        <div class="signature">
                            <div>
                                Sinh viên<br>
                                (Ký và ghi rõ họ tên)
                            </div>
                            <div>
                                Ban Công tác Sinh viên<br>
                                (Ký và ghi rõ họ tên)
                            </div>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn-submit" id="submitRequest">Gửi đơn xin rời phòng</button>
                            <button type="button" class="btn-export" id="exportPDF">Xuất PDF</button>
                            <button type="button" class="btn-export" onclick="window.location.href='departure_status.php';">Trạng thái đơn</button>
                        </div>
                        <div id="error-messages" style="color: red; margin-top: 10px;"></div> <!-- Thêm chỗ hiển thị lỗi -->
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
                doc.save('don_xin_roi_phong.pdf');
            }).catch(function(error) {
                console.error("Lỗi xuất PDF:", error);
                alert("Lỗi xuất PDF: " + error.message);
            });
        });

        document.getElementById('submitRequest').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('departureRequestForm'));
            fetch('ajax/process_departure_request.php', { // Sửa lại đường dẫn AJAX
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