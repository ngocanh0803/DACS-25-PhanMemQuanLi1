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
    <style>
        /* Kiểu dáng hợp đồng giấy */

        .contract-container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .formal-header {
            text-align: center;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .formal-header p {
            margin: 3px 0;
        }
        .contract-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .contract-header h1 {
            margin: 0;
            font-size: 28px;
            text-transform: uppercase;
        }
        .contract-header p {
            font-style: italic;
            margin-top: 5px;
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
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 8px;
            font-size: 16px;
            border: 1px solid #999;
            border-radius: 4px;
        }
        .form-group input[type="date"],
        .form-group input[type="number"],
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="file"] {
            width: 100%;
        }
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
        .btn-group {
            text-align: center;
            margin-top: 30px;
        }
        .btn-submit, .btn-export {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            font-size: 18px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 10px;
        }
        .btn-submit:hover, .btn-export:hover {
            background-color: #0056b3;
        }
    </style>
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
                        <input type="number" id="deposit" name="deposit" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="documents">Giấy tờ kèm theo (Upload file scan):</label>
                        <input type="file" id="documents" name="documents[]" multiple>
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

    <script>
        // Xuất PDF sử dụng jsPDF và html2canvas
        document.getElementById('exportPDF').addEventListener('click', function() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'pt', 'a4');
            const content = document.getElementById('contract-content') || document.getElementById('contract-content');
            // Sử dụng html2canvas để đảm bảo chuyển đổi đúng HTML sang canvas
            html2canvas(content, { scale: 0.7 }).then(function(canvas) {
                const imgData = canvas.toDataURL('image/png');
                const imgProps = doc.getImageProperties(imgData);
                const pdfWidth = doc.internal.pageSize.getWidth() - 40;
                const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
                doc.addImage(imgData, 'PNG', 20, 20, pdfWidth, pdfHeight);
                doc.save('don_dang_ky.pdf');
            }).catch(function(error) {
                console.error("Lỗi xuất PDF:", error);
            });
        });

        // Gửi đơn đăng ký: Sử dụng AJAX gửi dữ liệu đến process_registration_request.php
        document.getElementById('submitRequest').addEventListener('click', function() {
            // Thu thập dữ liệu từ form, bao gồm cả file nếu có
            const formData = new FormData();
            formData.append('student_code', document.getElementById('student_code').value);
            formData.append('full_name', document.getElementById('full_name').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('phone', document.getElementById('phone').value);
            formData.append('address', document.getElementById('address').value);
            formData.append('start_date', document.getElementById('start_date').value);
            formData.append('end_date', document.getElementById('end_date').value);
            formData.append('deposit', document.getElementById('deposit').value);
            // Lấy các file upload
            const files = document.getElementById('documents').files;
            for (let i = 0; i < files.length; i++) {
                formData.append('documents[]', files[i]);
            }

            fetch('ajax/process_registration_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    window.location.href = 'status_dashboard.php';
                }
            })
            .catch(error => {
                console.error('Error submitting request:', error);
                alert('Lỗi khi gửi đơn đăng ký.');
            });
        });
        // Gửi đơn đăng ký: bạn có thể thay đổi hành động này để thực hiện AJAX gửi đến file xử lý
        document.getElementById('submitRequest').addEventListener('click', function() {
            alert("Đơn đăng ký đã được gửi. Vui lòng chờ phản hồi từ Ban Công tác Sinh viên.");
            // Ví dụ: window.location.href = "dashboard.php";
        });

    </script>
</body>
</html>
