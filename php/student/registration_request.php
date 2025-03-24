<?php
session_start();
// Kiểm tra sinh viên đã đăng nhập chưa
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

$student_code = $_SESSION['username'];

include '../config/db_connect.php';

// Lấy thông tin sinh viên để tự động điền (ví dụ: full_name, email, phone, address, …)
$sql = "SELECT student_code, full_name, email, phone, address 
        FROM Students 
        WHERE student_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đơn xin vào ký túc xá</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- jsPDF & html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <link rel="stylesheet" href="../../assets/css/registration_request.css">
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'layout/sidebar.php'; ?>

    <!-- Nội dung chính -->
    <div class="main-content">
        <!-- Include Header -->
        <?php include 'layout/header.php'; ?>

        <div class="content">
            <div class="contract-container" id="contract-content">
                <div class="formal-header">
                    <p><strong>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</strong></p>
                    <p>Độc lập - Tự do - Hạnh phúc</p>
                    <hr style="border: 1px solid #000; width: 80%;">
                </div>
                <div class="contract-header">
                    <h1>ĐƠN XIN VÀO KÝ TÚC XÁ</h1>
                    <p>Kính gửi: Phòng Công tác Sinh viên Ký túc xá Phenikaa</p>
                </div>
                <!-- Phần I: Thông tin cá nhân -->
                <div class="section">
                    <h2>I. Thông tin cá nhân</h2>
                    <div class="form-group">
                        <label for="student_code">Mã sinh viên:</label>
                        <input type="text" id="student_code" name="student_code" value="<?php echo htmlspecialchars($student['student_code'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Họ và tên:</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($student['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Số điện thoại:</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Địa chỉ thường trú:</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($student['address'] ?? ''); ?>" required>
                    </div>
                </div>
                <!-- Phần II: Thông tin gia đình (bạn có thể bổ sung nếu cần) -->
                <div class="section">
                    <h2>II. Thông tin gia đình</h2>
                    <p>(Thông tin này có thể bổ sung sau nếu cần)</p>
                </div>
                <!-- Phần III: Thông tin hợp đồng dự kiến -->
                <div class="section">
                    <h2>III. Thông tin hợp đồng dự kiến</h2>
                    <div class="form-group">
                        <label for="start_date">Ngày nhận phòng dự kiến:</label>
                        <input type="date" id="start_date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">Ngày kết thúc thuê dự kiến:</label>
                        <input type="date" id="end_date" name="end_date" required>
                    </div>
                    <div class="form-group">
                        <label for="deposit">Số tiền đặt cọc (VNĐ):</label>
                        <input type="number" id="deposit" name="deposit" min="0" required readonly value="1000000">
                    </div>
                    <div class="form-group">
                        <label for="documents">Giấy tờ kèm theo (Ảnh CCCD) (Upload file scan):</label>
                        <input type="file" id="documents" name="documents[]" multiple>
                        <p class="file-limit-info" style="font-size: 0.9em; color: #777; margin-top: 5px;">
                            (Các file được phép: jpg, png, gif, pdf, doc, docx. Kích thước tối đa mỗi file: 5MB)
                        </p>
                    </div>
                </div>
                <!-- Phần IV: Điều khoản & Cam kết -->
                <div class="section">
                    <h2>IV. Điều khoản & Cam kết</h2>
                    <p>
                        Tôi cam kết rằng các thông tin đã điền là đúng sự thật và đầy đủ. Tôi chịu trách nhiệm trước pháp luật về tính chính xác của các thông tin này. 
                        Tôi đồng ý chấp hành các nội quy, quy định của Ký túc xá Phenikaa và chịu mọi hậu quả nếu vi phạm.
                    </p>
                </div>
                <!-- Phần V: Xác nhận -->
                <div class="section">
                    <p>
                        Tôi làm đơn này kính đề nghị Ban Công tác Sinh viên xem xét và cho tôi được vào ở Ký túc xá. Nếu được giải quyết, tôi cam kết thực hiện đầy đủ các quy định liên quan.
                    </p>
                </div>
                <!-- Phần chữ ký -->
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
                <!-- Nút xuất PDF và gửi đơn -->
                <div class="btn-group">
                    <button type="button" class="btn-submit" id="submitRequest">Gửi đơn đăng ký</button>
                    <button type="button" class="btn-export" id="exportPDF">Xuất PDF</button>
                    <button type="button" class="btn-export" onclick = "window.location.href='status_dashboard2.php';">Xem trạng thái</button>
                </div>
            </div>
        </div>
    <script src="../../assets/js/student_registration_request.js"></script>
</body>
</html>
