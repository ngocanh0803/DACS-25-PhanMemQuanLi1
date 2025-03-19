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
$sql = "SELECT student_id FROM Students WHERE student_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Không tìm thấy thông tin sinh viên.");  // Hoặc chuyển hướng đến trang lỗi
}

$student = $result->fetch_assoc();
$student_id = $student['student_id'];
$stmt->close();

// Lấy danh sách đơn xin rời phòng
$sqlDep = "SELECT departure_id, request_date, reason, documents, status, processed_date 
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
    <style>
        /* General Styles */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            /* max-width: 1000px; Increased max-width */
            width: 100%;
            padding: 30px; /* Increased padding */
            overflow-x: auto; /* Horizontal scroll for table */
        }
        h2 {
            color: #333;
            font-size: 1.8em;  /* Larger font size */
            margin-bottom: 30px;
            text-align: center;
            border-bottom: 2px solid #007bff; /* Added underline */
            padding-bottom: 10px;
        }
        /* Table Styles */
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px 15px; /* More padding */
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: #fff;
            font-weight: 600; /* Bolder font weight */
        }
        tr:nth-child(even) { background-color: #f2f2f2; }
        tr:hover { background-color: #ddd; }

        /* Link Styles */
        a {
            color: #007bff;
            display: inline-block;  /* Allows padding/margin */
            margin-bottom: 5px; /* Space between links */
            text-decoration: none;
        }

        /* Status Colors */
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-approved { color: #28a745; font-weight: bold; }
        .status-rejected { color: #dc3545; font-weight: bold; }

        /* No Request Message */
        .no-request {
            color: #666;
            font-size: 1.1em;
            margin-top: 20px;
            text-align: center;
        }
         /* Responsive Table */
        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%;
                text-align: right; /* Align data to right */

            }
           td:before {
                position: absolute;
                top: 6px;
                left: 6px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left; /* Align labels to left */
                font-weight: bold;
            }
           /* Label the data */
            td:before { content: attr(data-label); }
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
                            <th>Trạng thái</th>
                            <th>Ngày xử lý</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
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
                                <td data-label="Trạng thái">
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
                                <td data-label="Ngày xử lý"><?php echo htmlspecialchars($req['processed_date'] ?? 'Chưa xử lý'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-request">Bạn chưa gửi đơn xin rời phòng nào.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>