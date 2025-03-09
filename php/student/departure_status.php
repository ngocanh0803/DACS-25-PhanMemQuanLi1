<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'student') {
    header("Location: ../php/login.php");
    exit();
}

$student_code = $_SESSION['username'];
include '../config/db_connect.php';

// Lấy student_id từ bảng Students dựa trên student_code
$sql = "SELECT student_id FROM Students WHERE student_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    die("Không tìm thấy thông tin sinh viên.");
}
$student = $result->fetch_assoc();
$student_id = $student['student_id'];
$stmt->close();

// Truy vấn danh sách đơn xin rời phòng của sinh viên từ bảng Departure_Requests
$sqlDep = "SELECT departure_id, request_date, reason, documents, status, processed_date 
           FROM Departure_Requests 
           WHERE student_id = ? 
           ORDER BY request_date DESC";
$stmtDep = $conn->prepare($sqlDep);
$stmtDep->bind_param("i", $student_id);
$stmtDep->execute();
$resultDep = $stmtDep->get_result();
$requests = [];
while ($row = $resultDep->fetch_assoc()){
    $requests[] = $row;
}
$stmtDep->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trạng thái Đơn xin rời phòng - Sinh viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- CSS chung cho giao diện sinh viên -->
    <link rel="stylesheet" href="../../assets/css/main_student.css">
    <style>
        .container {
            padding: 20px;
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ccc;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-family: 'Times New Roman', serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        table th {
            background-color: #007bff;
            color: #fff;
        }
        .no-request {
            text-align: center;
            font-size: 18px;
            margin-top: 20px;
            font-family: 'Times New Roman', serif;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
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
        <div class="container">
            <h2>Trạng thái Đơn xin rời phòng</h2>
            <?php if (count($requests) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Ngày gửi</th>
                            <th>Lý do</th>
                            <th>Tài liệu kèm</th>
                            <th>Trạng thái</th>
                            <th>Ngày xử lý</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($req['departure_id']); ?></td>
                                <td><?php echo htmlspecialchars($req['request_date']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($req['reason'])); ?></td>
                                <td>
                                    <?php 
                                    if (!empty($req['documents'])) {
                                        $docs = json_decode($req['documents'], true);
                                        if ($docs) {
                                            foreach ($docs as $doc) {
                                                echo "<a href='" . htmlspecialchars($doc) . "' target='_blank'>Xem file</a><br>";
                                            }
                                        }
                                    } else {
                                        echo "Không có";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        switch ($req['status']) {
                                            case 'pending': echo 'Chờ xử lý'; break;
                                            case 'approved': echo 'Đã duyệt'; break;
                                            case 'rejected': echo 'Bị từ chối'; break;
                                            default: echo $req['status'];
                                        }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($req['processed_date'] ?? ''); ?></td>
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
