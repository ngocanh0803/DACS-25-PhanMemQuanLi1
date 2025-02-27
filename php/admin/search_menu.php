<?php
// search_menu.php
header('Content-Type: application/json');
include '../config/db_connect.php';

// Kiểm tra yêu cầu là GET và có tham số 'query'
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['query'])) {
    $query = trim($_GET['query']);
    
    // Kiểm tra nếu từ khóa rỗng
    if (empty($query)) {
        echo json_encode(['results' => []]);
        exit();
    }
    
    // Sử dụng Prepared Statements để tránh SQL Injection
    $sql = "SELECT name, url, icon FROM MenuItems WHERE name LIKE ? LIMIT 10";
    $stmt = $conn->prepare($sql);
    $like_query = '%' . $query . '%';
    $stmt->bind_param("s", $like_query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = [
            'name' => $row['name'],
            'url' => $row['url'],
            'icon' => $row['icon']
        ];
    }
    
    echo json_encode(['results' => $results]);
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['results' => []]);
    exit();
}
?>
