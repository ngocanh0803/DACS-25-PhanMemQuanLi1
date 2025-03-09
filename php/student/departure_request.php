<?php
session_start();
// Kiểm tra quyền truy cập: chỉ cho phép sinh viên
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

$student_code = $_SESSION['username'];
include '../config/db_connect.php';

// Lấy thông tin hợp đồng hiện hành của sinh viên (chỉ lấy hợp đồng đang active)
$sql_contract = "SELECT contract_id, contract_code, start_date, end_date 
                 FROM Contracts 
                 WHERE student_id = (SELECT student_id FROM Students WHERE student_code = ?)
                 AND status = 'active' LIMIT 1";
$stmt = $conn->prepare($sql_contract);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result_contract = $stmt->get_result();
if ($result_contract->num_rows > 0) {
    $contract = $result_contract->fetch_assoc();
    $contract_id = $contract['contract_id'];
} else {
    // Nếu không có hợp đồng active, sinh viên không thể xin rời phòng
    die("Bạn không có hợp đồng hiện hành, không thể gửi đơn xin rời phòng.");
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đơn xin rời phòng trước hạn hợp đồng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Include CSS chung của sinh viên -->
    <link rel="stylesheet" href="../../assets/css/main_student.css">
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
        .form-group textarea, .form-group input[type="file"] {
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
        /* Phần cam kết, chữ ký */
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
            <div class="form-container" id="departure-form-container">
                <h1>ĐƠN XIN RỜI PHÒNG TRƯỚC HẠN HỢP ĐỒNG</h1>
                <p style="text-align: center; font-style: italic;">(Mẫu đơn theo phong cách hợp đồng giấy)</p>
                <!-- Thông tin hợp đồng hiện hành (chỉ hiển thị để tham khảo) -->
                <div class="section">
                    <h2>Thông tin hợp đồng hiện hành</h2>
                    <p><strong>Mã hợp đồng:</strong> <?php echo htmlspecialchars($contract['contract_code']); ?></p>
                    <p><strong>Ngày nhận phòng:</strong> <?php echo htmlspecialchars($contract['start_date']); ?></p>
                    <p><strong>Ngày kết thúc dự kiến:</strong> <?php echo htmlspecialchars($contract['end_date']); ?></p>
                </div>
                <!-- Form đơn xin rời phòng -->
                <form id="departureRequestForm" action="process_departure_request.php" method="POST" enctype="multipart/form-data">
                    <!-- Ẩn contract_id -->
                    <input type="hidden" name="contract_id" value="<?php echo htmlspecialchars($contract_id); ?>">
                    <div class="section">
                        <h2>I. Lý do xin rời phòng</h2>
                        <div class="form-group">
                            <label for="reason">Lý do:</label>
                            <textarea id="reason" name="reason" rows="4" placeholder="Nhập lý do xin rời phòng..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="documents">Tài liệu kèm theo (nếu có):</label>
                            <input type="file" id="documents" name="documents[]" multiple>
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
                        <button type="button" class="btn-export" onclick = "window.location.href='departure_status.php';">Trạng thái đơn</button>
                    </div>
                </form>
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

        // Gửi đơn xin rời phòng: Sử dụng AJAX để gửi dữ liệu
        document.getElementById('submitRequest').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('departureRequestForm'));
            fetch('ajax/process_departure_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if(data.success) {
                    window.location.href = 'status_dashboard.php';
                }
            })
            .catch(error => {
                console.error("Lỗi gửi đơn:", error);
                alert("Lỗi khi gửi đơn xin rời phòng.");
            });
        });
    </script>
</body>
</html>
