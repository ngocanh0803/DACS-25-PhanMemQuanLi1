<?php
header('Content-Type: application/json');
session_start();

// Kiểm tra quyền admin (admin hoặc manager)
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager')) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['request_id'], $_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    exit();
}

$request_id = intval($_POST['request_id']);
$action = $_POST['action'];

include '../../config/db_connect.php';

// Lấy thông tin đơn yêu cầu từ bảng Equipment_Requests
$sql = "SELECT * FROM Equipment_Requests WHERE request_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Đơn yêu cầu không tồn tại.']);
    exit();
}
$request = $result->fetch_assoc();
$stmt->close();

if ($action === 'approve') {
    if ($request['request_type'] === 'personal') {
        // Yêu cầu cá nhân: thêm một dòng mới vào bảng Facilities với is_student_device = 1
        $facility_code = "STD-" . time() . rand(100,999);
        $facility_name = $request['facility_name'];
        $quantity = $request['quantity'];
        $room_id = $request['room_id'];
        $status_facility = 'good'; // mặc định là tốt
        $is_student_device = 1;
        
        $sql_insert = "INSERT INTO Facilities (facility_code, room_id, facility_name, quantity, status, is_student_device) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("sisiii", $facility_code, $room_id, $facility_name, $quantity, $status_facility, $is_student_device);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm thiết bị cá nhân.']);
            exit();
        }
        $stmt->close();
    } elseif ($request['request_type'] === 'additional') {
        // Yêu cầu thêm chung: cập nhật số lượng của thiết bị hiện có trong Facilities (is_student_device = 0)
        $facility_name = $request['facility_name'];
        $quantity_requested = $request['quantity'];
        $room_id = $request['room_id'];
        // Tìm thiết bị có tên trùng khớp trong phòng và không thuộc thiết bị cá nhân
        $sql_select = "SELECT facility_id, quantity FROM Facilities WHERE room_id = ? AND facility_name = ? AND is_student_device = 0 LIMIT 1";
        $stmt = $conn->prepare($sql_select);
        $stmt->bind_param("is", $room_id, $facility_name);
        $stmt->execute();
        $result_fac = $stmt->get_result();
        if ($result_fac->num_rows > 0) {
            // Cập nhật số lượng: cộng dồn
            $facility = $result_fac->fetch_assoc();
            $new_quantity = $facility['quantity'] + $quantity_requested;
            $sql_update = "UPDATE Facilities SET quantity = ? WHERE facility_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ii", $new_quantity, $facility['facility_id']);
            if (!$stmt_update->execute()) {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật số lượng thiết bị chung.']);
                exit();
            }
            $stmt_update->close();
        } else {
            // Nếu không tìm thấy, tùy chọn: có thể chèn một dòng mới (hoặc trả về lỗi)
            $facility_code = "COM-" . time() . rand(100,999);
            $status_facility = 'good';
            $is_student_device = 0;
            $sql_insert = "INSERT INTO Facilities (facility_code, room_id, facility_name, quantity, status, is_student_device) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("sisiii", $facility_code, $room_id, $facility_name, $quantity_requested, $status_facility, $is_student_device);
            if (!$stmt_insert->execute()) {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm thiết bị chung.']);
                exit();
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
    
    // Cập nhật trạng thái đơn yêu cầu thành 'approved'
    $sql_update = "UPDATE Equipment_Requests SET status = 'approved' WHERE request_id = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("i", $request_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Yêu cầu đã được duyệt thành công.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái yêu cầu.']);
    }
    $stmt->close();
} elseif ($action === 'reject') {
    // Từ chối đơn: cập nhật trạng thái của đơn yêu cầu thành 'rejected'
    $sql_update = "UPDATE Equipment_Requests SET status = 'rejected' WHERE request_id = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("i", $request_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Yêu cầu đã bị từ chối.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái yêu cầu.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
}
$conn->close();
?>
