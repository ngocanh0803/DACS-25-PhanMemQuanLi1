<?php
    header('Content-Type: application/json');
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Không có quyền']);
        exit();
    }

    include '../config/db_connect.php';

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['user_id']) || !isset($data['username']) || !isset($data['role']) || !isset($data['is_approved'])) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        exit();
    }

    $user_id = intval($data['user_id']);
    $username = trim($data['username']);
    $role = $data['role'];
    $is_approved = intval($data['is_approved']);
    // $password = isset($data['password']) && !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
    $password = isset($data['password']) && !empty($data['password']) ? $data['password'] : null;
    
    $sql = "UPDATE Users SET username = ?, role = ?, is_approved = ? ";
    if($password !== null) {
        $sql .= ", password = ?";
    }
    $sql .= " WHERE user_id = ?";


    $stmt = $conn->prepare($sql);

    if($password !== null) {
        // Sửa chuỗi định dạng kiểu dữ liệu thành "ssisi" (thêm 'i' cho $user_id)
        $stmt->bind_param("ssisi", $username, $role, $is_approved, $password, $user_id);

    } else {
        $stmt->bind_param("ssii", $username, $role, $is_approved, $user_id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tài khoản đã được cập nhật thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật tài khoản']);
    }

    $stmt->close();
    $conn->close();
?>