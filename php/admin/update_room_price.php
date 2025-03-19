<?php
session_start();

header('Content-Type: application/json');

include '../config/db_connect.php';

// Nhận dữ liệu từ request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['capacity']) || !isset($data['new_price'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit();
}

$capacity = $data['capacity'];
$newPrice = floatval($data['new_price']);

// Kiểm tra giá trị hợp lệ
if ($newPrice <= 0) {
    echo json_encode(['success' => false, 'message' => 'Giá phòng phải lớn hơn 0']);
    exit();
}

// Kiểm tra capacity hợp lệ
$validCapacities = ['2', '4', '8'];
if (!in_array($capacity, $validCapacities)) {
    echo json_encode(['success' => false, 'message' => 'Loại phòng không hợp lệ']);
    exit();
}

try {
    // Bắt đầu transaction
    $conn->begin_transaction();

    // Cập nhật giá cho tất cả phòng có cùng capacity
    $sql = "UPDATE Rooms SET price = ? WHERE capacity = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ds", $newPrice, $capacity);
    
    if ($stmt->execute()) {
        // Tạo bảng log nếu chưa có
        $createLogTable = "CREATE TABLE IF NOT EXISTS PriceChangeLog (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            capacity VARCHAR(2) NOT NULL,
            old_price DECIMAL(10,2) NOT NULL,
            new_price DECIMAL(10,2) NOT NULL,
            changed_by INT NOT NULL,
            changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (changed_by) REFERENCES Users(user_id)
        )";
        $conn->query($createLogTable);

        // Lấy giá cũ
        $oldPriceSql = "SELECT price FROM Rooms WHERE capacity = ? LIMIT 1";
        $oldPriceStmt = $conn->prepare($oldPriceSql);
        $oldPriceStmt->bind_param("s", $capacity);
        $oldPriceStmt->execute();
        $oldPriceResult = $oldPriceStmt->get_result();
        $oldPrice = $oldPriceResult->fetch_assoc()['price'];

        // Ghi log thay đổi giá
        $logSql = "INSERT INTO PriceChangeLog (capacity, old_price, new_price, changed_by) 
                   VALUES (?, ?, ?, ?)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param("sddi", $capacity, $oldPrice, $newPrice, $_SESSION['user_id']);
        $logStmt->execute();

        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Lỗi khi cập nhật giá phòng");
    }
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>