<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Import Sinh Viên từ Excel</title>
    <link rel="stylesheet" href="../assest/css/main.css">
    <link rel="stylesheet" href="../assest/css/import_students.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'layout/header.php'; ?>
    <div class="container">
        <?php include 'layout/menu.php'; ?>
        <main class="content">
            <div class="import-container">
                <h2><i class="fas fa-file-import"></i> Import Sinh Viên từ File Excel</h2>
                <form action="process_import.php" method="POST" enctype="multipart/form-data" class="import-form">
                    <div class="form-group">
                        <label for="excel_file">Chọn File Excel:</label>
                        <input type="file" name="excel_file" id="excel_file" accept=".xlsx, .xls" required>
                    </div>
                    <button type="submit" name="import" class="import-btn"><i class="fas fa-upload"></i> Import</button>
                </form>
                <div class="note">
                    <p><strong>Ghi chú:</strong></p>
                    <ul>
                        <li>File Excel phải có định dạng <code>.xlsx</code> hoặc <code>.xls</code>.</li>
                        <li>Cấu trúc cột trong Excel phải tương ứng với cấu trúc bảng <code>Students</code> trong cơ sở dữ liệu.</li>
                        <li>Ví dụ: <code>Mã sinh viên</code>, <code>tên sinh viên</code>, <code>email</code>, <code>phone</code>, <code>giới tính</code>, <code>ngày sinh</code>, <code>địa chỉ</code>, <code>quốc gia</code>, <code>môn học</code>, <code>sinh viên năm</code>, <code>GPA</code>, <code>Phòng</code>, <code>Trạng thái</code>.</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assest/js/main.js"></script>
    <script src="../assest/js/search.js"></script>
</body>
</html>
