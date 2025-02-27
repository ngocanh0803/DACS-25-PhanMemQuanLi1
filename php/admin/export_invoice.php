<?php
ob_start(); // Bắt đầu output buffering

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/../config/db_connect.php';
require '../vendor/autoload.php';

// Bỏ dòng use TCPDF; nếu không cần thiết, vì TCPDF không sử dụng namespace như vậy
// use TCPDF;

if (isset($_GET['payment_id'])) {
    $payment_id = filter_input(INPUT_GET, 'payment_id', FILTER_VALIDATE_INT);
    if ($payment_id === false) {
        $_SESSION['error'] = "ID hóa đơn không hợp lệ.";
        header('Location: view_payments.php?room_id=' . urlencode($_GET['room_id']));
        exit();
    }

    // Truy vấn hóa đơn
    $sql_payment = "SELECT p.*, r.building, r.room_number FROM Payments p JOIN Rooms r ON p.room_id = r.room_id WHERE p.payment_id = ?";
    $stmt_payment = $conn->prepare($sql_payment);
    $stmt_payment->bind_param("i", $payment_id);
    $stmt_payment->execute();
    $result_payment = $stmt_payment->get_result();
    $payment = $result_payment->fetch_assoc();

    if (!$payment) {
        $_SESSION['error'] = "Hóa đơn không tồn tại.";
        header('Location: view_payments.php?room_id=' . urlencode($_GET['room_id']));
        exit();
    }

    // Tạo PDF
    $pdf = new TCPDF();
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();

    // Đặt font cho tiếng Việt (Sử dụng DejaVu Sans)
    $pdf->SetFont('dejavusans', '', 12);

    // Đường dẫn logo tuyệt đối
    $logoPath = '/Applications/XAMPP/xamppfiles/htdocs/KTX_PTTKPM_ADMIN/assest/img/logohoadon.png';

    // Nội dung HTML
    $html = '
    <div style="text-align: center;">
        <h1 style="font-size: 22px; color: #333; margin-bottom: 20px;">HÓA ĐƠN THANH TOÁN</h1>
    </div>
    <br>
    <p style="font-size: 14px; color: #555; margin: 5px 0;">
        <strong>Hóa đơn số:</strong> ' . htmlspecialchars($payment['payment_code']) . '
    </p>
    <p style="font-size: 14px; color: #555; margin: 5px 0;">
        <strong>Tòa nhà:</strong> ' . htmlspecialchars($payment['building']) . '
    </p>
    <p style="font-size: 14px; color: #555; margin: 5px 0;">
        <strong>Phòng:</strong> ' . htmlspecialchars($payment['room_number']) . '
    </p>
    <p style="font-size: 14px; color: #555; margin: 5px 0;">
        <strong>Tháng:</strong> ' . date('m/Y', strtotime($payment['payment_date'])) . '
    </p>
    <table style="width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px;">
        <thead>
            <tr style="background-color: #4CAF50; color: white;">
                <th style="border: 1px solid #ddd; padding: 25px; text-align: center;">Số điện (kWh)</th>
                <th style="border: 1px solid #ddd; padding: 25px; text-align: center;">Số nước (m³)</th>
                <th style="border: 1px solid #ddd; padding: 25px; text-align: center;">Tổng tiền (VNĐ)</th>
                <th style="border: 1px solid #ddd; padding: 25px; text-align: center;">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: 1px solid #ddd; padding: 25px; text-align: center;">' . htmlspecialchars($payment['electricity_usage']) . '</td>
                <td style="border: 1px solid #ddd; padding: 25px; text-align: center;">' . htmlspecialchars($payment['water_usage']) . '</td>
                <td style="border: 1px solid #ddd; padding: 25px; text-align: center;">' . number_format($payment['total_amount'], 0, ',', '.') . '</td>
                <td style="border: 1px solid #ddd; padding: 25px; text-align: center; color: ' . ($payment['payment_status'] == 'paid' ? '#4CAF50' : '#f44336') . ';">' . ($payment['payment_status'] == 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán') . '</td>
            </tr>
        </tbody>
    </table>
    <br>
    <p style="font-size: 14px; color: #555; text-align: right;">Ngày xuất hóa đơn: ' . date('d/m/Y') . '</p>
    <div style="text-align: center; margin-top: 30px;">
        <p style="font-size: 14px; color: #555;">Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi!</p>
    </div>
    ';

    // Ghi nội dung HTML vào PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    // Chèn logo vào cuối trang nếu tồn tại
    if (file_exists($logoPath)) {
        $logoWidth = 50;
        $logoHeight = 30;
        $pageWidth = $pdf->getPageWidth();
        $pageHeight = $pdf->getPageHeight();
        $xPosition = ($pageWidth - $logoWidth) / 2;
        $yPosition = $pageHeight - $pdf->getMargins()['bottom'] - $logoHeight - 10;
        $pdf->Image($logoPath, $xPosition, $yPosition, $logoWidth, $logoHeight, '', '', '', false, 300, '', false, false, 0, false, false, false);
    } else {
        error_log("Không tìm thấy logo tại đường dẫn: $logoPath");
    }

    // Xóa output buffer để tránh lỗi dữ liệu đã được xuất
    ob_end_clean();

    // Xuất file PDF
    $pdf->Output('invoice_' . $payment['payment_code'] . '.pdf', 'D');

} else {
    $_SESSION['error'] = "Không tìm thấy ID hóa đơn.";
    header('Location: payments_list.php');
    exit();
}
?>
