<?php
session_start();

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

$student_code = $_SESSION['username'];
include '../config/db_connect.php';

// Lấy student_id
$sqlStudent = "SELECT student_id FROM Students WHERE student_code = ?";
$stmtStudent = $conn->prepare($sqlStudent);
$stmtStudent->bind_param("s", $student_code);
$stmtStudent->execute();
$resultStudent = $stmtStudent->get_result();

if ($resultStudent->num_rows == 0) {
    die("Không tìm thấy thông tin sinh viên.");
}

$student = $resultStudent->fetch_assoc();
$student_id = $student['student_id'];
$stmtStudent->close();

// Lấy danh sách đơn xin rời phòng và deposit_refund_status
$sqlDep = "SELECT departure_id, request_date, reason, documents, status, processed_date, deposit_refund_status
           FROM Departure_Requests
           WHERE student_id = ?
           ORDER BY request_date DESC";
$stmtDep = $conn->prepare($sqlDep);
$stmtDep->bind_param("i", $student_id);
$stmtDep->execute();
$resultDep = $stmtDep->get_result();

$requests = [];
while ($row = $resultDep->fetch_assoc()) {
    $requests[] = $row;
}
$stmtDep->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trạng thái Đơn xin rời phòng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <link rel="stylesheet" href="../../assets/css/student_departure_status.css">
    <style>
        .refund-status {
            font-weight: bold;
        }
        .refund-confirmed {
            color: green;
        }
        .refund-pending-admin {
            color: orange;
        }
        .refund-initiated {
            color: blue;
        }
        .btn-confirm-refund {
            background-color: #28a745; /* Màu xanh lá cây */
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
        }
        .btn-confirm-refund:hover {
            background-color: #218838;
        }

    </style>
</head>
<body>
    <?php include 'layout/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'layout/header.php'; ?>

        <div class="container">
            <h2>Trạng thái Đơn xin rời phòng</h2>

            <?php if (count($requests) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Ngày gửi</th>
                            <th>Lý do</th>
                            <th>Tài liệu</th>
                            <th>Trạng thái đơn</th>
                            <th>Ngày xử lý</th> <!-- **Ngày xử lý - RE-ADDED** -->
                            <th>Trạng thái cọc</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req):
                            $refundStatusText = '';
                            $refundConfirmationButton = '';
                            $refundStatusClass = '';

                            switch ($req['deposit_refund_status']) {
                                case 'pending_admin_action':
                                    $refundStatusText = 'Chờ duyệt cọc';
                                    $refundStatusClass = 'refund-pending-admin';
                                    break;
                                case 'refund_initiated':
                                    $refundStatusText = 'Đã gửi yêu cầu trả cọc';
                                    $refundStatusClass = 'refund-initiated';
                                    $refundConfirmationButton = '<button class="btn-confirm-refund" data-id="' . htmlspecialchars($req['departure_id']) . '">Xác nhận nhận cọc</button>';
                                    break;
                                case 'refund_confirmed_student':
                                    $refundStatusText = 'Đã nhận cọc';
                                    $refundStatusClass = 'refund-confirmed';
                                    break;
                                case 'refunded':
                                    $refundStatusText = 'Đã hoàn cọc';
                                    $refundStatusClass = 'refund-confirmed'; // or a different class if you want
                                    break;
                                default:
                                    $refundStatusText = htmlspecialchars($req['deposit_refund_status']);
                            }


                            ?>
                            <tr>
                                <td data-label="Mã đơn"><?php echo htmlspecialchars($req['departure_id']); ?></td>
                                <td data-label="Ngày gửi"><?php echo htmlspecialchars($req['request_date']); ?></td>
                                <td data-label="Lý do"><?php echo nl2br(htmlspecialchars($req['reason'])); ?></td>
                                <td data-label="Tài liệu">
                                    <?php
                                    if (!empty($req['documents'])) {
                                        $docs = json_decode($req['documents'], true);
                                        if ($docs) {
                                            foreach ($docs as $doc) {
                                                // Sửa đường dẫn ở đây:
                                                echo "<a href='" . htmlspecialchars($doc) . "' target='_blank'>Xem file</a>";
                                            }
                                        }
                                    } else {
                                        echo "Không có";
                                    }
                                    ?>
                                </td>
                                <td data-label="Trạng thái đơn">
                                    <?php
                                    $statusClass = '';
                                    switch ($req['status']) {
                                        case 'pending':
                                            $statusClass = 'status-pending';
                                            echo '<span class="' . $statusClass . '">Chờ xử lý</span>';
                                            break;
                                        case 'approved':
                                            $statusClass = 'status-approved';
                                            echo '<span class="' . $statusClass . '">Đã duyệt</span>';
                                            break;
                                        case 'rejected':
                                            $statusClass = 'status-rejected';
                                            echo '<span class="' . $statusClass . '">Bị từ chối</span>';
                                            break;
                                        default:
                                            echo htmlspecialchars($req['status']);
                                    }
                                    ?>
                                </td>
                                 <td data-label="Ngày xử lý"><?php echo htmlspecialchars($req['processed_date'] ?? 'Chưa xử lý'); ?></td> <!-- **Ngày xử lý - RE-ADDED** -->
                                 <td data-label="Trạng thái cọc"><span class="refund-status <?php echo $refundStatusClass; ?>"><?php echo $refundStatusText; ?></span></td>
                                <td data-label="Hành động">
                                    <?php echo $refundConfirmationButton; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-request">Bạn chưa gửi đơn xin rời phòng nào.</p>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        $(document).ready(function(){
            // Event listener for "Confirm Refund Received" buttons
            $(document).on('click', '.btn-confirm-refund', function() {
                const departureId = $(this).data('id');
                if (confirm("Bạn xác nhận đã nhận được tiền cọc trả lại?")) {
                    $.ajax({
                        url: 'ajax/process_deposit_refund_confirmation.php', // Adjust URL if needed
                        type: 'POST',
                        data: { departure_id: departureId },
                        dataType: 'json',
                        success: function(response) {
                            alert(response.message);
                            if (response.success) {
                                window.location.reload(); // Reload page to update status
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Error confirming refund:", error);
                            alert("Lỗi xác nhận nhận cọc. Vui lòng thử lại.");
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>