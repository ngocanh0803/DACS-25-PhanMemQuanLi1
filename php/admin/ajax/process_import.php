<?php
// process_import.php
require '../../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// Kết nối cơ sở dữ liệu
include '../../config/db_connect.php';


if (isset($_POST['import'])) {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === 0) {
        $allowed_extensions = ['xls', 'xlsx'];
        $file_info = pathinfo($_FILES['excel_file']['name']);
        $file_extension = strtolower($file_info['extension']);

        if (in_array($file_extension, $allowed_extensions)) {
            $file_tmp_path = $_FILES['excel_file']['tmp_name'];

            try {
                $spreadsheet = IOFactory::load($file_tmp_path);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray(null, true, true, true);

                // Lấy header (hàng đầu tiên) và chuyển thành chữ thường
                $header = array_map('strtolower', $rows[1]);
                unset($rows[1]);

                // Các cột bắt buộc cho Students và Users
                $required_columns = [
                    'student_code', 'full_name', 'email', 'phone', 
                    'gender', 'date_of_birth', 'address', 'nationality', 
                    'major', 'year_of_study', 'gpa', 'room_id', 'status',
                    'username', 'password'
                ];

                foreach ($required_columns as $column) {
                    if (!in_array($column, $header)) {
                        throw new Exception("Thiếu cột: " . $column);
                    }
                }

                // Chuẩn bị SQL chèn vào bảng Students (13 trường)
                $sql_student = "INSERT INTO Students (student_code, full_name, email, phone, gender, date_of_birth, address, nationality, major, year_of_study, gpa, room_id, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_student = $conn->prepare($sql_student);
                if (!$stmt_student) {
                    throw new Exception("Lỗi chuẩn bị câu lệnh SQL (Students): " . $conn->error);
                }

                // Chuẩn bị SQL chèn vào bảng Users (3 trường từ Excel: username, password, email – bạn có thể dùng email để liên kết hoặc để hiển thị, role, is_approved, activation_token được tự động thiết lập)
                // Ở đây, chúng ta sẽ lấy username và password từ file Excel, còn role sẽ đặt là 'student'
                $sql_user = "INSERT INTO Users (username, password, role, is_approved, activation_token) VALUES (?, ?, 'student', 1, ?)";
                $stmt_user = $conn->prepare($sql_user);
                if (!$stmt_user) {
                    throw new Exception("Lỗi chuẩn bị câu lệnh SQL (Users): " . $conn->error);
                }

                $inserted = 0;
                $duplicates = 0;
                $errors = [];

                foreach ($rows as $index => $row) {
                    // Lấy dữ liệu cho bảng Students
                    $student_code   = trim($row[array_search('student_code', $header)]);
                    $full_name      = trim($row[array_search('full_name', $header)]);
                    $email          = trim($row[array_search('email', $header)]);
                    $phone          = trim($row[array_search('phone', $header)]);
                    $gender         = trim($row[array_search('gender', $header)]);
                    $date_of_birth  = trim($row[array_search('date_of_birth', $header)]);
                    $address        = trim($row[array_search('address', $header)]);
                    $nationality    = trim($row[array_search('nationality', $header)]);
                    $major          = trim($row[array_search('major', $header)]);
                    $year_of_study  = trim($row[array_search('year_of_study', $header)]);
                    $gpa            = trim($row[array_search('gpa', $header)]);
                    $status         = trim($row[array_search('status', $header)]);
                    $room_id        = trim($row[array_search('room_id', $header)]);

                    // Lấy dữ liệu cho bảng Users
                    $username       = trim($row[array_search('username', $header)]);
                    $password       = trim($row[array_search('password', $header)]); // Lưu plain text theo yêu cầu

                    // Kiểm tra dữ liệu bắt buộc
                    if (empty($student_code) || empty($full_name) || empty($email) || empty($username) || empty($password)) {
                        $errors[] = "Dòng " . ($index + 2) . ": Thiếu dữ liệu bắt buộc.";
                        continue;
                    }
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Dòng " . ($index + 2) . ": Email không hợp lệ.";
                        continue;
                    }

                    // Kiểm tra phòng (nếu có)
                    if (!empty($room_id)) {
                        $room_id = intval($room_id);
                        $sql_room = "SELECT room_id FROM Rooms WHERE room_id = ?";
                        $stmt_room = $conn->prepare($sql_room);
                        if ($stmt_room) {
                            $stmt_room->bind_param("i", $room_id);
                            $stmt_room->execute();
                            $result_room = $stmt_room->get_result();
                            if ($result_room->num_rows === 0) {
                                $errors[] = "Dòng " . ($index + 2) . ": Phòng với ID $room_id không tồn tại.";
                                $room_id = NULL;
                            }
                            $stmt_room->close();
                        } else {
                            $errors[] = "Dòng " . ($index + 2) . ": Lỗi kiểm tra phòng.";
                            $room_id = NULL;
                        }
                    } else {
                        $room_id = NULL;
                    }

                    // Chèn vào bảng Students
                    $stmt_student->bind_param("sssssssssidis", $student_code, $full_name, $email, $phone, $gender, $date_of_birth, $address, $nationality, $major, $year_of_study, $gpa, $room_id, $status);
                    if ($stmt_student->execute()) {
                        $inserted++;

                        // Tạo tài khoản cho sinh viên vào bảng Users
                        // Ở đây sử dụng username và password lấy từ Excel,
                        // activation_token được tạo ngẫu nhiên.
                        $activation_token = bin2hex(random_bytes(16));
                        $stmt_user->bind_param("sss", $username, $password, $activation_token);
                        if (!$stmt_user->execute()) {
                            $errors[] = "Dòng " . ($index + 2) . ": Không thể tạo tài khoản User.";
                        }
                    } else {
                        if ($conn->errno === 1062) {
                            $duplicates++;
                        } else {
                            $errors[] = "Dòng " . ($index + 2) . ": " . $conn->error;
                        }
                    }
                }

                $stmt_student->close();
                $stmt_user->close();
                $conn->close();

                echo "<!DOCTYPE html>
                <html lang='vi'>
                <head>
                    <meta charset='UTF-8'>
                    <title>Kết Quả Import</title>
                    <link rel='stylesheet' href='../../../assets/css/main.css'>
                    <link rel='stylesheet' href='../../../assets/css/import_students.css'>
                    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
                </head>
                <body>
                    <?php include '../layout/header.php'; ?>
                    <div class='container'>
                        <?php include '../layout/menu.php'; ?>
                        <main class='content'>
                            <div class='import-result'>
                                <h2>Kết Quả Import</h2>
                                <p>Số lượng sinh viên đã thêm thành công: <strong>{$inserted}</strong></p>";
                if ($duplicates > 0) {
                    echo "<p>Số lượng sinh viên bị trùng lặp và không thêm: <strong>{$duplicates}</strong></p>";
                }
                if (!empty($errors)) {
                    echo "<div class='error-messages'>
                            <p>Các lỗi xảy ra:</p>
                            <ul>";
                    foreach ($errors as $error) {
                        echo "<li>{$error}</li>";
                    }
                    echo "</ul>
                          </div>";
                }
                echo "<a href='../import_students.php' class='back-btn'><i class='fas fa-arrow-left'></i> Quay lại</a>
                            </div>
                        </main>
                    </div>
                </body>
                </html>";
            } catch (Exception $e) {
                echo "<!DOCTYPE html>
                <html lang='vi'>
                <head>
                    <meta charset='UTF-8'>
                    <title>Lỗi Import</title>
                    <link rel='stylesheet' href='../../../assets/css/main.css'>
                    <link rel='stylesheet' href='../../../assets/css/import_students.css'>
                    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
                </head>
                <body>
                    <?php include '../layout/header.php'; ?>
                    <div class='container'>
                        <?php include '../layout/menu.php'; ?>
                        <main class='content'>
                            <div class='import-error'>
                                <h2><i class='fas fa-exclamation-triangle'></i> Lỗi Import</h2>
                                <p>{$e->getMessage()}</p>
                                <a href='../import_students.php' class='back-btn'><i class='fas fa-arrow-left'></i> Quay lại</a>
                            </div>
                        </main>
                    </div>
                </body>
                </html>";
            }
        } else {
            echo "<!DOCTYPE html>
            <html lang='vi'>
            <head>
                <meta charset='UTF-8'>
                <title>Lỗi Định Dạng File</title>
                <link rel='stylesheet' href='../../../assets/css/main.css'>
                <link rel='stylesheet' href='../../../assets/css/import_students.css'>
                <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
            </head>
            <body>
                <?php include '../layout/header.php'; ?>
                <div class='container'>
                    <?php include '../layout/menu.php'; ?>
                    <main class='content'>
                        <div class='import-error'>
                            <h2><i class='fas fa-exclamation-triangle'></i> Lỗi Định Dạng File</h2>
                            <p>Chỉ hỗ trợ các định dạng file Excel: .xlsx, .xls</p>
                            <a href='../import_students.php' class='back-btn'><i class='fas fa-arrow-left'></i> Quay lại</a>
                        </div>
                    </main>
                </div>
            </body>
            </html>";
        }
    } else {
        echo "<!DOCTYPE html>
        <html lang='vi'>
        <head>
            <meta charset='UTF-8'>
            <title>Lỗi Upload File</title>
            <link rel='stylesheet' href='../../assets/css/main.css'>
            <link rel='stylesheet' href='../../assets/css/import_students.css'>
            <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
        </head>
        <body>
            <?php include 'layout/header.php'; ?>
            <div class='container'>
                <?php include 'layout/menu.php'; ?>
                <main class='content'>
                    <div class='import-error'>
                        <h2><i class='fas fa-exclamation-triangle'></i> Lỗi Upload File</h2>
                        <p>Không thể tải file lên. Vui lòng thử lại.</p>
                        <a href='../import_students.php' class='back-btn'><i class='fas fa-arrow-left'></i> Quay lại</a>
                    </div>
                </main>
            </div>
        </body>
        </html>";
    }
} else {
    header("Location: ../import_students.php");
    exit();
}
?>
