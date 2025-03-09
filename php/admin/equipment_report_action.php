<?php
// equipment_report_action.php
header('Content-Type: application/json');
session_start();

// Kiểm tra quyền admin (hoặc manager)
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['report_id'], $_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    exit();
}

$report_id = intval($_POST['report_id']);
$action = $_POST['action'];

include '../../config/db_connect.php';

// Lấy facility_id từ bảng Equipment_Reports dựa trên report_id
$sql = "SELECT facility_id FROM Equipment_Reports WHERE report_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0){
    $row = $result->fetch_assoc();
    $facility_id = $row['facility_id'];
} else {
    echo json_encode(['success' => false, 'message' => 'Báo cáo không tồn tại.']);
    exit();
}
$stmt->close();

if ($action === 'verify') {
    // Cập nhật trạng thái báo cáo thành 'pending'
    $sql_update_report = "UPDATE Equipment_Reports SET status = 'pending' WHERE report_id = ?";
    $stmt = $conn->prepare($sql_update_report);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $stmt->close();
    
    // Cập nhật trạng thái của thiết bị trong bảng Facilities thành 'broken'
    $sql_update_facility = "UPDATE Facilities SET status = 'broken' WHERE facility_id = ?";
    $stmt = $conn->prepare($sql_update_facility);
    $stmt->bind_param("i", $facility_id);
    if ($stmt->execute()){
        echo json_encode(['success' => true, 'message' => 'Báo cáo đã được xác nhận, thiết bị đã được cập nhật trạng thái "Hỏng".']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái thiết bị.']);
    }
    $stmt->close();
} elseif ($action === 'processed') {
    // Cập nhật trạng thái báo cáo thành 'resolved'
    $sql_update_report = "UPDATE Equipment_Reports SET status = 'resolved' WHERE report_id = ?";
    $stmt = $conn->prepare($sql_update_report);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $stmt->close();
    
    // Cập nhật trạng thái của thiết bị thành 'good'
    $sql_update_facility = "UPDATE Facilities SET status = 'good' WHERE facility_id = ?";
    $stmt = $conn->prepare($sql_update_facility);
    $stmt->bind_param("i", $facility_id);
    if ($stmt->execute()){
        echo json_encode(['success' => true, 'message' => 'Thiết bị đã được xác nhận đã xử lý, trạng thái cập nhật thành "Tốt".']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái thiết bị.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
}
$conn->close();
?>
