-- Tạo cơ sở dữ liệu
drop database if exists dormitory_management;
CREATE DATABASE dormitory_management;
USE dormitory_management;

-- Bảng Users (Tài khoản người dùng)
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('manager', 'student_manager', 'accountant', 'admin', 'student') NOT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    activation_token  VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng Rooms (Phòng ở)
CREATE TABLE Rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_code VARCHAR(50) UNIQUE NOT NULL,
    building VARCHAR(1) NOT NULL,
    floor INT NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    capacity ENUM('2', '4', '8') NOT NULL,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    price DECIMAL(10,2) NOT NULL
);

CREATE TABLE Students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    student_code VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    gender ENUM('Male', 'Female', 'Other') DEFAULT 'Other',
    date_of_birth DATE,
    address VARCHAR(255),
    nationality VARCHAR(50) DEFAULT 'Vietnam',
    major VARCHAR(100),
    year_of_study INT CHECK (year_of_study BETWEEN 1 AND 5),
    gpa DECIMAL(3, 2) CHECK (gpa BETWEEN 0.0 AND 4.0),
    room_id INT,
    status ENUM('Active', 'Inactive', 'Graduated') DEFAULT 'Active',
    FOREIGN KEY (room_id) REFERENCES Rooms(room_id) ON DELETE SET NULL
);


-- Bảng Facilities (Cơ sở vật chất)
CREATE TABLE Facilities (
    facility_id INT AUTO_INCREMENT PRIMARY KEY,
    facility_code VARCHAR(50) UNIQUE NOT NULL,
    room_id INT,
    facility_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    status ENUM('good', 'broken') DEFAULT 'good',
    FOREIGN KEY (room_id) REFERENCES Rooms(room_id) ON DELETE CASCADE
);

-- Bảng Payments (Thanh toán)
CREATE TABLE Payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    payment_code VARCHAR(50) UNIQUE NOT NULL,
    room_id INT,
    electricity_usage DECIMAL(10,2) DEFAULT 0.00,
    water_usage DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('unpaid', 'paid') DEFAULT 'unpaid',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES Rooms(room_id) ON DELETE CASCADE
);

-- Bảng Room_Status (Tình trạng phòng)
CREATE TABLE Room_Status (
    room_status_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    student_id INT,
    start_date DATE NOT NULL,
    end_date DATE,
    FOREIGN KEY (room_id) REFERENCES Rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE
);

CREATE TABLE MenuItems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,        -- Tên chức năng
    url VARCHAR(255) NOT NULL,         -- Đường dẫn đến chức năng
    icon VARCHAR(50),                   -- Tên lớp Font Awesome cho biểu tượng
    description TEXT,                   -- Mô tả ngắn về chức năng (có thể để trống)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Contracts (
    contract_id INT AUTO_INCREMENT PRIMARY KEY,
    contract_code VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    room_id INT NOT NULL,
    signed_date DATE NOT NULL,          
    start_date DATE NOT NULL,           
    end_date DATE,                      
    deposit DECIMAL(10,2) DEFAULT 0.00, 
    terms TEXT,                         
    status ENUM('active', 'terminated', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES Rooms(room_id) ON DELETE CASCADE
);

CREATE TABLE Messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    FOREIGN KEY (sender_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE Notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('payment', 'contract', 'maintenance', 'general') DEFAULT 'general',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE Feedbacks (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    status ENUM('pending', 'in_progress', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

CREATE TABLE Activity_Log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity VARCHAR(255) NOT NULL,
    activity_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
);

-- Chèn dữ liệu cho Quản lý phòng
INSERT INTO Menuitems (id, name, url, icon, description, created_at) VALUES
(1, 'Xem sơ đồ', './view_floor_plan.php', 'fa-map', 'Xem sơ đồ tòa nhà', '2024-10-27 07:29:57'),
(2, 'Xem tình trạng', './rooms_list.php', 'fa-eye', 'Xem tình trạng các phòng', '2024-10-27 07:29:57'),
(3, 'Thiết lập tình trạng', './manage_rooms.php', 'fa-cog', 'Thiết lập tình trạng phòng', '2024-10-27 07:29:57'),
(4, 'Thiết lập giá', './room_prices.php', 'fa-dollar-sign', 'Thiết lập giá phòng', '2024-10-27 07:29:57'),
(5, 'Thống kê', './statistics.php', 'fa-chart-line', 'Thống kê dữ liệu', '2024-10-27 07:29:57'),
(6, 'Xem cơ sở vật chất', './view_facilities.php', 'fa-list', 'Xem danh sách cơ sở vật chất', '2024-10-27 07:29:57'),
(7, 'Thiết lập cơ sở vật chất', './manage_facilities.php', 'fa-tools', 'Thiết lập cơ sở vật chất', '2024-10-27 07:29:57'),
(8, 'Thêm sinh viên vào danh sách cư trú', './import_students.php', 'fa-plus', 'Thêm sinh viên vào danh sách cư trú', '2024-10-27 07:29:57'),
(9, 'Thêm, sửa, xóa sinh viên theo phòng', './students_list.php', 'fa-edit', 'Quản lý sinh viên theo phòng', '2024-10-27 07:29:57'),
(10, 'Nhập số điện nước theo phòng', './payments_list.php', 'fa-tint', 'Nhập số điện nước cho phòng', '2024-10-27 07:29:57'),
(11, 'Tổng hóa đơn (theo phòng)', './all_payments.php', 'fa-file-invoice', 'Xem tổng hóa đơn theo phòng', '2024-10-27 07:29:57'),
(12, 'Quản lí người dùng', './manage_user.php', 'fa-user-cog', 'Quản lí người dùng', '2024-10-27 07:29:57');

-- Thêm dữ liệu mẫu vào bảng Users
INSERT INTO Users (username, password, role, is_approved) VALUES
('admin', 'admin', 'admin', 1),
('quanli', '123', 'manager', 1),
('ketoan', '123', 'student_manager', 0),
('sinvientest', '123', 'student', 1);

-- Thêm dữ liệu mẫu vào bảng Rooms
INSERT INTO Rooms (building, room_code, floor, room_number, capacity, status, price) VALUES
-- Tòa nhà A
('A', 'A101', 1, '101', '2', 'maintenance', 1400000),
('A', 'A102', 1, '102', '2', 'occupied', 1400000),
('A', 'A103', 1, '103', '2', 'occupied', 1400000),
('A', 'A104', 1, '104', '2', 'occupied', 1400000),
('A', 'A105', 1, '105', '2', 'occupied', 1400000),
('A', 'A106', 1, '106', '2', 'available', 1400000),
('A', 'A107', 1, '107', '2', 'available', 1400000),
('A', 'A108', 1, '108', '2', 'available', 1400000),
('A', 'A109', 1, '109', '2', 'maintenance', 1400000),
('A', 'A110', 1, '110', '2', 'maintenance', 1400000),

('A', 'A201', 2, '201', '2', 'available', 1400000),
('A', 'A202', 2, '202', '2', 'available', 1400000),
('A', 'A203', 2, '203', '2', 'maintenance', 1400000),
('A', 'A204', 2, '204', '2', 'available', 1400000),
('A', 'A205', 2, '205', '2', 'available', 1400000),
('A', 'A206', 2, '206', '2', 'available', 1400000),
('A', 'A207', 2, '207', '2', 'available', 1400000),
('A', 'A208', 2, '208', '2', 'maintenance', 1400000),
('A', 'A209', 2, '209', '2', 'available', 1400000),
('A', 'A210', 2, '210', '2', 'available', 1400000),

('A', 'A301', 3, '301', '2', 'available', 1400000),
('A', 'A302', 3, '302', '2', 'available', 1400000),
('A', 'A303', 3, '303', '2', 'maintenance', 1400000),
('A', 'A304', 3, '304', '2', 'available', 1400000),
('A', 'A305', 3, '305', '2', 'available', 1400000),
('A', 'A306', 3, '306', '2', 'available', 1400000),
('A', 'A307', 3, '307', '2', 'maintenance', 1400000),
('A', 'A308', 3, '308', '2', 'available', 1400000),
('A', 'A309', 3, '309', '2', 'maintenance', 1400000),
('A', 'A310', 3, '310', '2', 'available', 1400000),

-- Tòa nhà B
('B', 'B101', 1, '101', '4', 'available', 1600000),
('B', 'B102', 1, '102', '4', 'available', 1600000),
('B', 'B103', 1, '103', '4', 'available', 1600000),
('B', 'B104', 1, '104', '4', 'maintenance', 1600000),
('B', 'B105', 1, '105', '4', 'available', 1600000),
('B', 'B106', 1, '106', '4', 'available', 1600000),
('B', 'B107', 1, '107', '4', 'maintenance', 1600000),
('B', 'B108', 1, '108', '4', 'available', 1600000),
('B', 'B109', 1, '109', '4', 'maintenance', 1600000),
('B', 'B110', 1, '110', '4', 'maintenance', 1600000),

('B', 'B201', 2, '201', '4', 'available', 1600000),
('B', 'B202', 2, '202', '4', 'occupied', 1600000),
('B', 'B203', 2, '203', '4', 'maintenance', 1600000),
('B', 'B204', 2, '204', '4', 'available', 1600000),
('B', 'B205', 2, '205', '4', 'occupied', 1600000),
('B', 'B206', 2, '206', '4', 'available', 1600000),
('B', 'B207', 2, '207', '4', 'available', 1600000),
('B', 'B208', 2, '208', '4', 'available', 1600000),
('B', 'B209', 2, '209', '4', 'maintenance', 1600000),
('B', 'B210', 2, '210', '4', 'available', 1600000),

('B', 'B301', 3, '301', '4', 'available', 1600000),
('B', 'B302', 3, '302', '4', 'available', 1600000),
('B', 'B303', 3, '303', '4', 'maintenance', 1600000),
('B', 'B304', 3, '304', '4', 'available', 1600000),
('B', 'B305', 3, '305', '4', 'available', 1600000),
('B', 'B306', 3, '306', '4', 'available', 1600000),
('B', 'B307', 3, '307', '4', 'maintenance', 1600000),
('B', 'B308', 3, '308', '4', 'available', 1600000),
('B', 'B309', 3, '309', '4', 'maintenance', 1600000),
('B', 'B310', 3, '310', '4', 'available', 1600000),

-- Tòa nhà C
('C', 'C101', 1, '101', '8', 'available', 1700000),
('C', 'C102', 1, '102', '8', 'available', 1700000),
('C', 'C103', 1, '103', '8', 'maintenance', 1700000),
('C', 'C104', 1, '104', '8', 'available', 1700000),
('C', 'C105', 1, '105', '8', 'available', 1700000),
('C', 'C106', 1, '106', '8', 'available', 1700000),
('C', 'C107', 1, '107', '8', 'available', 1700000),
('C', 'C108', 1, '108', '8', 'available', 1700000),
('C', 'C109', 1, '109', '8', 'maintenance', 1700000),
('C', 'C110', 1, '110', '8', 'available', 1700000),

('C', 'C201', 2, '201', '8', 'available', 1700000),
('C', 'C202', 2, '202', '8', 'available', 1700000),
('C', 'C203', 2, '203', '8', 'maintenance', 1700000),
('C', 'C204', 2, '204', '8', 'available', 1700000),
('C', 'C205', 2, '205', '8', 'available', 1700000),
('C', 'C206', 2, '206', '8', 'available', 1700000),
('C', 'C207', 2, '207', '8', 'available', 1700000),
('C', 'C208', 2, '208', '8', 'available', 1700000),
('C', 'C209', 2, '209', '8', 'maintenance', 1700000),
('C', 'C210', 2, '210', '8', 'available', 1700000),

('C', 'C301', 3, '301', '8', 'available', 1700000),
('C', 'C302', 3, '302', '8', 'occupied', 1700000),
('C', 'C303', 3, '303', '8', 'maintenance', 1700000),
('C', 'C304', 3, '304', '8', 'available', 1700000),
('C', 'C305', 3, '305', '8', 'occupied', 1700000),
('C', 'C306', 3, '306', '8', 'available', 1700000),
('C', 'C307', 3, '307', '8', 'available', 1700000),
('C', 'C308', 3, '308', '8', 'available', 1700000),
('C', 'C309', 3, '309', '8', 'maintenance', 1700000),
('C', 'C310', 3, '310', '8', 'available', 1700000);


-- Thêm dữ liệu mẫu vào bảng Students, sử dụng room_id từ bảng Rooms
INSERT INTO Students (student_code, full_name, email, phone, gender, date_of_birth, address, nationality, major, year_of_study, gpa, room_id, status)
VALUES

('22010001', 'Nguyen Van A', 'a@example.com', '0901234567', 'Male', '2001-02-15', '123 Nguyen Trai, District 1', 'Vietnam', 'Computer Science', 3, 3.5, (SELECT room_id FROM Rooms WHERE room_code = 'A102' AND building = 'A'), 'Active'),
('22010002', 'Tran Thi B', 'b@example.com', '0902345678', 'Female', '2000-07-20', '456 Le Loi, District 3', 'Vietnam', 'Business Administration', 4, 3.8, (SELECT room_id FROM Rooms WHERE room_code = 'A102' AND building = 'A'), 'Active'),

('22010003', 'Le Van C', 'c@example.com', '0903456789', 'Male', '2002-05-10', '789 Cach Mang Thang 8, District 10', 'Vietnam', 'Economics', 2, 3.2, (SELECT room_id FROM Rooms WHERE room_code = 'A103' AND building = 'A'), 'Active'),
('22010004', 'Pham Thi D', 'd@example.com', '0904567890', 'Female', '2001-11-30', '101 Vo Van Tan, District 1', 'Vietnam', 'Marketing', 5, 3.9, (SELECT room_id FROM Rooms WHERE room_code = 'A103' AND building = 'A'), 'Active'),

('22010005', 'Hoang Van E', 'e@example.com', '0905678901', 'Male', '2002-09-10', '102 Tran Hung Dao, District 5', 'Vietnam', 'Information Technology', 1, 3.0, (SELECT room_id FROM Rooms WHERE room_code = 'A104' AND building = 'A'), 'Active'),

('22010006', 'Do Thi F', 'f@example.com', '0906789012', 'Female', '2000-12-12', '15 Dien Bien Phu, District 3', 'Vietnam', 'Finance', 4, 3.6, (SELECT room_id FROM Rooms WHERE room_code = 'A105' AND building = 'A'), 'Active'),
('22010007', 'Vo Van G', 'g@example.com', '0907890123', 'Male', '2001-01-15', '20 Hai Ba Trung, District 5', 'Vietnam', 'Law', 3, 3.4, (SELECT room_id FROM Rooms WHERE room_code = 'A105' AND building = 'A'), 'Active'),

('22010008', 'Nguyen Thi H', 'h@example.com', '0908901234', 'Female', '2000-03-10', '30 Pham Ngu Lao, District 1', 'Vietnam', 'Engineering', 5, 3.7, (SELECT room_id FROM Rooms WHERE room_code = 'B202' AND building = 'B'), 'Active'),
('22010009', 'Tran Van I', 'i@example.com', '0909012345', 'Male', '1999-08-25', '35 Le Van Sy, District 3', 'Vietnam', 'Graphic Design', 4, 3.3, (SELECT room_id FROM Rooms WHERE room_code = 'B202' AND building = 'B'), 'Active'),
('22010010', 'Nguyen Van J', 'j@example.com', '0900123456', 'Male', '2001-05-20', '12 Bach Dang, District 10', 'Vietnam', 'Architecture', 2, 3.1, (SELECT room_id FROM Rooms WHERE room_code = 'B202' AND building = 'B'), 'Active'),

('22010011', 'Le Thi K', 'k@example.com', '0901234567', 'Female', '2000-10-15', '15 Nguyen Hue, District 1', 'Vietnam', 'Mechanical Engineering', 4, 3.5, (SELECT room_id FROM Rooms WHERE room_code = 'B205' AND building = 'B'), 'Active'),
('22010012', 'Hoang Van L', 'l@example.com', '0902345678', 'Male', '1998-09-22', '25 Dong Khoi, District 3', 'Vietnam', 'Civil Engineering', 5, 3.0, (SELECT room_id FROM Rooms WHERE room_code = 'B205' AND building = 'B'), 'Active'),

('22010013', 'Le Thi M', 'k@example.com', '0901234567', 'Female', '2000-10-15', '15 Nguyen Hue, District 1', 'Vietnam', 'Mechanical Engineering', 4, 3.5, (SELECT room_id FROM Rooms WHERE room_code = 'C302' AND building = 'C'), 'Active'),
('22010014', 'Hoang Van N', 'l@example.com', '0902345678', 'Male', '1998-09-22', '25 Dong Khoi, District 3', 'Vietnam', 'Civil Engineering', 5, 3.0, (SELECT room_id FROM Rooms WHERE room_code = 'C302' AND building = 'C'), 'Active'),
('22010015', 'Le Thi O', 'k@example.com', '0901234567', 'Female', '2000-10-15', '15 Nguyen Hue, District 1', 'Vietnam', 'Mechanical Engineering', 4, 3.5, (SELECT room_id FROM Rooms WHERE room_code = 'C302' AND building = 'C'), 'Active'),

('22010016', 'Hoang Van P', 'l@example.com', '0902345678', 'Male', '1998-09-22', '25 Dong Khoi, District 3', 'Vietnam', 'Civil Engineering', 5, 3.0, (SELECT room_id FROM Rooms WHERE room_code = 'C305' AND building = 'C'), 'Active'),
('22010017', 'Le Thi Q', 'k@example.com', '0901234567', 'Female', '2000-10-15', '15 Nguyen Hue, District 1', 'Vietnam', 'Mechanical Engineering', 4, 3.5, (SELECT room_id FROM Rooms WHERE room_code = 'C305' AND building = 'C'), 'Active'),
('22010018', 'Hoang Van Z', 'l@example.com', '0902345678', 'Male', '1998-09-22', '25 Dong Khoi, District 3', 'Vietnam', 'Civil Engineering', 5, 3.0, (SELECT room_id FROM Rooms WHERE room_code = 'C305' AND building = 'C'), 'Active'),
('22010019', 'Hoang Van X', 'l@example.com', '0902345678', 'Male', '1998-09-22', '25 Dong Khoi, District 3', 'Vietnam', 'Civil Engineering', 5, 3.0, (SELECT room_id FROM Rooms WHERE room_code = 'C305' AND building = 'C'), 'Active');
-- Thêm cơ sở vật chất cho các phòng, sử dụng room_id từ bảng Rooms
INSERT INTO Facilities (facility_code, room_id, facility_name, quantity, status) VALUES
('TB001', (SELECT room_id FROM Rooms WHERE room_code = 'A101'), 'Bàn học', 4, 'good'),
('TB002', (SELECT room_id FROM Rooms WHERE room_code = 'A102'), 'Ghế', 4, 'good'),
('TB003', (SELECT room_id FROM Rooms WHERE room_code = 'A103'), 'Tủ quần áo', 2, 'good'),
('TB004', (SELECT room_id FROM Rooms WHERE room_code = 'A104'), 'Giường', 4, 'broken'),
('TB005', (SELECT room_id FROM Rooms WHERE room_code = 'A201'), 'Bàn học', 4, 'good'),
('TB006', (SELECT room_id FROM Rooms WHERE room_code = 'A202'), 'Ghế', 4, 'good'),
('TB007', (SELECT room_id FROM Rooms WHERE room_code = 'A203'), 'Tủ quần áo', 2, 'broken'),
('TB008', (SELECT room_id FROM Rooms WHERE room_code = 'B101'), 'Giường', 4, 'good'),
('TB009', (SELECT room_id FROM Rooms WHERE room_code = 'B102'), 'Bàn học', 4, 'good'),
('TB010', (SELECT room_id FROM Rooms WHERE room_code = 'B103'), 'Ghế', 4, 'good'),
('TB011', (SELECT room_id FROM Rooms WHERE room_code = 'B202'), 'Tủ quần áo', 2, 'broken'),
('TB012', (SELECT room_id FROM Rooms WHERE room_code = 'C101'), 'Giường', 4, 'good'),
('TB013', (SELECT room_id FROM Rooms WHERE room_code = 'C102'), 'Bàn học', 4, 'good'),
('TB014', (SELECT room_id FROM Rooms WHERE room_code = 'C103'), 'Ghế', 4, 'broken'),
('TB015', (SELECT room_id FROM Rooms WHERE room_code = 'C302'), 'Giường', 4, 'good'),

('TB016', (SELECT room_id FROM Rooms WHERE room_code = 'A105'), 'Máy lạnh', 1, 'good'),
('TB017', (SELECT room_id FROM Rooms WHERE room_code = 'A106'), 'Bàn học', 4, 'good'),
('TB018', (SELECT room_id FROM Rooms WHERE room_code = 'A107'), 'Ghế', 4, 'broken'),
('TB019', (SELECT room_id FROM Rooms WHERE room_code = 'A108'), 'Giường', 4, 'good'),
('TB020', (SELECT room_id FROM Rooms WHERE room_code = 'A109'), 'Tủ quần áo', 2, 'good'),
('TB021', (SELECT room_id FROM Rooms WHERE room_code = 'A110'), 'Máy giặt', 1, 'broken'),

('TB022', (SELECT room_id FROM Rooms WHERE room_code = 'B104'), 'Bàn học', 4, 'good'),
('TB023', (SELECT room_id FROM Rooms WHERE room_code = 'B105'), 'Ghế', 4, 'good'),
('TB024', (SELECT room_id FROM Rooms WHERE room_code = 'B201'), 'Tủ quần áo', 2, 'broken'),
('TB025', (SELECT room_id FROM Rooms WHERE room_code = 'B203'), 'Giường', 4, 'good'),
('TB026', (SELECT room_id FROM Rooms WHERE room_code = 'B204'), 'Máy lạnh', 1, 'good'),
('TB027', (SELECT room_id FROM Rooms WHERE room_code = 'B205'), 'Bàn học', 4, 'good'),

('TB028', (SELECT room_id FROM Rooms WHERE room_code = 'C104'), 'Ghế', 4, 'good'),
('TB029', (SELECT room_id FROM Rooms WHERE room_code = 'C105'), 'Giường', 4, 'broken'),
('TB030', (SELECT room_id FROM Rooms WHERE room_code = 'C201'), 'Tủ quần áo', 2, 'good'),
('TB031', (SELECT room_id FROM Rooms WHERE room_code = 'C202'), 'Máy lạnh', 1, 'good'),
('TB032', (SELECT room_id FROM Rooms WHERE room_code = 'C204'), 'Bàn học', 4, 'good'),
('TB033', (SELECT room_id FROM Rooms WHERE room_code = 'C205'), 'Giường', 4, 'good'),
('TB034', (SELECT room_id FROM Rooms WHERE room_code = 'C304'), 'Tủ quần áo', 2, 'broken'),
('TB035', (SELECT room_id FROM Rooms WHERE room_code = 'C305'), 'Máy giặt', 1, 'good'),

-- Các thiết bị bổ sung
('TB036', (SELECT room_id FROM Rooms WHERE room_code = 'A301'), 'Tủ lạnh', 1, 'good'),
('TB037', (SELECT room_id FROM Rooms WHERE room_code = 'A302'), 'Quạt', 3, 'good'),
('TB038', (SELECT room_id FROM Rooms WHERE room_code = 'A303'), 'Máy chiếu', 1, 'broken'),
('TB039', (SELECT room_id FROM Rooms WHERE room_code = 'A304'), 'Bàn là', 2, 'good'),
('TB040', (SELECT room_id FROM Rooms WHERE room_code = 'A305'), 'Máy nước nóng', 1, 'good'),
('TB041', (SELECT room_id FROM Rooms WHERE room_code = 'A306'), 'Kệ sách', 2, 'good'),
('TB042', (SELECT room_id FROM Rooms WHERE room_code = 'A307'), 'Giá treo quần áo', 3, 'broken'),
('TB043', (SELECT room_id FROM Rooms WHERE room_code = 'A308'), 'Bàn làm việc', 1, 'good'),
('TB044', (SELECT room_id FROM Rooms WHERE room_code = 'A309'), 'Tivi', 1, 'good'),
('TB045', (SELECT room_id FROM Rooms WHERE room_code = 'A310'), 'Máy lọc nước', 1, 'broken'),

('TB046', (SELECT room_id FROM Rooms WHERE room_code = 'B301'), 'Máy sấy', 1, 'good'),
('TB047', (SELECT room_id FROM Rooms WHERE room_code = 'B302'), 'Đèn bàn', 4, 'good'),
('TB048', (SELECT room_id FROM Rooms WHERE room_code = 'B303'), 'Máy lạnh', 2, 'broken'),
('TB049', (SELECT room_id FROM Rooms WHERE room_code = 'B304'), 'Chăn gối', 4, 'good'),
('TB050', (SELECT room_id FROM Rooms WHERE room_code = 'B305'), 'Bàn ghế', 2, 'good'),
('TB051', (SELECT room_id FROM Rooms WHERE room_code = 'B306'), 'Máy lọc không khí', 1, 'good'),
('TB052', (SELECT room_id FROM Rooms WHERE room_code = 'B307'), 'Rèm cửa', 2, 'good'),
('TB053', (SELECT room_id FROM Rooms WHERE room_code = 'B308'), 'Két sắt', 1, 'broken'),
('TB054', (SELECT room_id FROM Rooms WHERE room_code = 'B309'), 'Đèn ngủ', 4, 'good'),
('TB055', (SELECT room_id FROM Rooms WHERE room_code = 'B310'), 'Máy hút bụi', 1, 'good'),

('TB056', (SELECT room_id FROM Rooms WHERE room_code = 'C306'), 'Máy nước nóng lạnh', 1, 'good'),
('TB057', (SELECT room_id FROM Rooms WHERE room_code = 'C307'), 'Tủ lạnh mini', 1, 'broken'),
('TB058', (SELECT room_id FROM Rooms WHERE room_code = 'C308'), 'Lò vi sóng', 1, 'good'),
('TB059', (SELECT room_id FROM Rooms WHERE room_code = 'C309'), 'Bàn ghế', 2, 'good'),
('TB060', (SELECT room_id FROM Rooms WHERE room_code = 'C310'), 'Giường tầng', 4, 'broken'),
('TB061', (SELECT room_id FROM Rooms WHERE room_code = 'C101'), 'Tủ giày', 1, 'good'),
('TB062', (SELECT room_id FROM Rooms WHERE room_code = 'C102'), 'Bàn trà', 1, 'good'),
('TB063', (SELECT room_id FROM Rooms WHERE room_code = 'C103'), 'Lò sưởi', 1, 'broken'),
('TB064', (SELECT room_id FROM Rooms WHERE room_code = 'C104'), 'Kệ để đồ', 2, 'good'),
('TB065', (SELECT room_id FROM Rooms WHERE room_code = 'C105'), 'Đèn treo tường', 4, 'good'),
('TB066', (SELECT room_id FROM Rooms WHERE room_code = 'C106'), 'Máy chiếu', 1, 'good'),
-- Tòa nhà A
('TB067', (SELECT room_id FROM Rooms WHERE room_code = 'A102'), 'Quạt trần', 2, 'good'),
('TB068', (SELECT room_id FROM Rooms WHERE room_code = 'A103'), 'Bếp điện', 1, 'broken'),
('TB069', (SELECT room_id FROM Rooms WHERE room_code = 'A104'), 'Tủ quần áo', 2, 'good'),
('TB070', (SELECT room_id FROM Rooms WHERE room_code = 'A105'), 'Ghế sofa', 1, 'good'),
('TB071', (SELECT room_id FROM Rooms WHERE room_code = 'A106'), 'Máy giặt', 1, 'broken'),
('TB072', (SELECT room_id FROM Rooms WHERE room_code = 'A107'), 'Tủ lạnh', 1, 'good'),
('TB073', (SELECT room_id FROM Rooms WHERE room_code = 'A108'), 'Lò vi sóng', 1, 'good'),
('TB074', (SELECT room_id FROM Rooms WHERE room_code = 'A109'), 'Bàn ăn', 1, 'good'),
('TB075', (SELECT room_id FROM Rooms WHERE room_code = 'A110'), 'Kệ sách', 2, 'broken'),

('TB076', (SELECT room_id FROM Rooms WHERE room_code = 'A201'), 'Rèm cửa', 3, 'good'),
('TB077', (SELECT room_id FROM Rooms WHERE room_code = 'A202'), 'Giường tầng', 2, 'broken'),
('TB078', (SELECT room_id FROM Rooms WHERE room_code = 'A203'), 'Ghế tựa', 4, 'good'),
('TB079', (SELECT room_id FROM Rooms WHERE room_code = 'A204'), 'Bàn làm việc', 2, 'good'),
('TB080', (SELECT room_id FROM Rooms WHERE room_code = 'A205'), 'Tivi', 1, 'good'),
('TB081', (SELECT room_id FROM Rooms WHERE room_code = 'A206'), 'Máy lọc không khí', 1, 'broken'),
('TB082', (SELECT room_id FROM Rooms WHERE room_code = 'A207'), 'Tủ lạnh mini', 1, 'good'),
('TB083', (SELECT room_id FROM Rooms WHERE room_code = 'A208'), 'Máy sấy tóc', 2, 'good'),
('TB084', (SELECT room_id FROM Rooms WHERE room_code = 'A209'), 'Đèn ngủ', 4, 'broken'),
('TB085', (SELECT room_id FROM Rooms WHERE room_code = 'A210'), 'Két sắt', 1, 'good'),

('TB086', (SELECT room_id FROM Rooms WHERE room_code = 'A301'), 'Quạt điện', 3, 'good'),
('TB087', (SELECT room_id FROM Rooms WHERE room_code = 'A302'), 'Bàn ủi', 1, 'broken'),
('TB088', (SELECT room_id FROM Rooms WHERE room_code = 'A303'), 'Máy lọc nước', 1, 'good'),
('TB089', (SELECT room_id FROM Rooms WHERE room_code = 'A304'), 'Máy hút bụi', 1, 'good'),
('TB090', (SELECT room_id FROM Rooms WHERE room_code = 'A305'), 'Đèn trang trí', 2, 'good'),
('TB091', (SELECT room_id FROM Rooms WHERE room_code = 'A306'), 'Ghế dài', 1, 'broken'),
('TB092', (SELECT room_id FROM Rooms WHERE room_code = 'A307'), 'Bàn trà', 1, 'good'),
('TB093', (SELECT room_id FROM Rooms WHERE room_code = 'A308'), 'Bếp gas', 1, 'good'),
('TB094', (SELECT room_id FROM Rooms WHERE room_code = 'A309'), 'Bàn học', 4, 'good'),
('TB095', (SELECT room_id FROM Rooms WHERE room_code = 'A310'), 'Tủ giày', 2, 'broken'),

-- Tòa nhà B
('TB096', (SELECT room_id FROM Rooms WHERE room_code = 'B101'), 'Lò nướng', 1, 'good'),
('TB097', (SELECT room_id FROM Rooms WHERE room_code = 'B102'), 'Máy xay sinh tố', 1, 'broken'),
('TB098', (SELECT room_id FROM Rooms WHERE room_code = 'B103'), 'Máy nước nóng', 1, 'good'),
('TB099', (SELECT room_id FROM Rooms WHERE room_code = 'B104'), 'Ghế bành', 2, 'good'),
('TB100', (SELECT room_id FROM Rooms WHERE room_code = 'B105'), 'Đèn led', 4, 'good'),
('TB101', (SELECT room_id FROM Rooms WHERE room_code = 'B106'), 'Bàn vi tính', 1, 'good'),
('TB102', (SELECT room_id FROM Rooms WHERE room_code = 'B107'), 'Giá sách', 2, 'broken'),
('TB103', (SELECT room_id FROM Rooms WHERE room_code = 'B108'), 'Máy chiếu', 1, 'good'),
('TB104', (SELECT room_id FROM Rooms WHERE room_code = 'B109'), 'Ghế gỗ', 4, 'good'),
('TB105', (SELECT room_id FROM Rooms WHERE room_code = 'B110'), 'Đèn treo tường', 2, 'good'),

('TB106', (SELECT room_id FROM Rooms WHERE room_code = 'B201'), 'Bàn cafe', 1, 'good'),
('TB107', (SELECT room_id FROM Rooms WHERE room_code = 'B202'), 'Giá treo quần áo', 3, 'broken'),
('TB108', (SELECT room_id FROM Rooms WHERE room_code = 'B203'), 'Đèn ngủ', 4, 'good'),
('TB109', (SELECT room_id FROM Rooms WHERE room_code = 'B204'), 'Tủ đựng đồ', 2, 'good'),
('TB110', (SELECT room_id FROM Rooms WHERE room_code = 'B205'), 'Chăn gối', 4, 'good'),
('TB111', (SELECT room_id FROM Rooms WHERE room_code = 'B206'), 'Ghế nhựa', 4, 'good'),
('TB112', (SELECT room_id FROM Rooms WHERE room_code = 'B207'), 'Tủ quần áo', 2, 'broken'),
('TB113', (SELECT room_id FROM Rooms WHERE room_code = 'B208'), 'Máy lạnh', 1, 'good'),
('TB114', (SELECT room_id FROM Rooms WHERE room_code = 'B209'), 'Đèn bàn', 2, 'good'),
('TB115', (SELECT room_id FROM Rooms WHERE room_code = 'B210'), 'Kệ tivi', 1, 'broken'),

('TB116', (SELECT room_id FROM Rooms WHERE room_code = 'B301'), 'Máy xông hơi', 1, 'good'),
('TB117', (SELECT room_id FROM Rooms WHERE room_code = 'B302'), 'Tủ lạnh mini', 1, 'good'),
('TB118', (SELECT room_id FROM Rooms WHERE room_code = 'B303'), 'Bếp từ', 1, 'broken'),
('TB119', (SELECT room_id FROM Rooms WHERE room_code = 'B304'), 'Bàn là', 1, 'good'),
('TB120', (SELECT room_id FROM Rooms WHERE room_code = 'B305'), 'Bàn ủi', 1, 'good'),
('TB121', (SELECT room_id FROM Rooms WHERE room_code = 'B306'), 'Tủ đựng giày', 1, 'good'),
('TB122', (SELECT room_id FROM Rooms WHERE room_code = 'B307'), 'Đèn trang trí', 2, 'good'),
('TB123', (SELECT room_id FROM Rooms WHERE room_code = 'B308'), 'Máy nước nóng lạnh', 1, 'good'),
('TB124', (SELECT room_id FROM Rooms WHERE room_code = 'B309'), 'Lò sưởi', 1, 'good'),
('TB125', (SELECT room_id FROM Rooms WHERE room_code = 'B310'), 'Bàn cafe', 1, 'good'),

-- Tòa nhà C
('TB126', (SELECT room_id FROM Rooms WHERE room_code = 'C101'), 'Bếp nướng', 1, 'good'),
('TB127', (SELECT room_id FROM Rooms WHERE room_code = 'C102'), 'Lò vi sóng', 1, 'broken'),
('TB128', (SELECT room_id FROM Rooms WHERE room_code = 'C103'), 'Máy hút mùi', 1, 'good'),
('TB129', (SELECT room_id FROM Rooms WHERE room_code = 'C104'), 'Máy lọc không khí', 1, 'good'),
('TB130', (SELECT room_id FROM Rooms WHERE room_code = 'C105'), 'Tủ lạnh', 1, 'good'),
('TB131', (SELECT room_id FROM Rooms WHERE room_code = 'C106'), 'Ghế đôn', 3, 'broken'),
('TB132', (SELECT room_id FROM Rooms WHERE room_code = 'C107'), 'Máy nước nóng', 1, 'good'),
('TB133', (SELECT room_id FROM Rooms WHERE room_code = 'C108'), 'Giá để đồ', 1, 'good'),
('TB134', (SELECT room_id FROM Rooms WHERE room_code = 'C109'), 'Bàn học', 4, 'good'),
('TB135', (SELECT room_id FROM Rooms WHERE room_code = 'C110'), 'Ghế ngồi', 4, 'good'),

('TB136', (SELECT room_id FROM Rooms WHERE room_code = 'C201'), 'Đèn tường', 4, 'good'),
('TB137', (SELECT room_id FROM Rooms WHERE room_code = 'C202'), 'Tủ đựng đồ', 2, 'broken'),
('TB138', (SELECT room_id FROM Rooms WHERE room_code = 'C203'), 'Lò nướng điện', 1, 'good'),
('TB139', (SELECT room_id FROM Rooms WHERE room_code = 'C204'), 'Máy xay sinh tố', 1, 'good'),
('TB140', (SELECT room_id FROM Rooms WHERE room_code = 'C205'), 'Đèn chùm', 1, 'good'),
('TB141', (SELECT room_id FROM Rooms WHERE room_code = 'C206'), 'Máy pha cà phê', 1, 'good'),
('TB142', (SELECT room_id FROM Rooms WHERE room_code = 'C207'), 'Ghế sofa', 1, 'broken'),
('TB143', (SELECT room_id FROM Rooms WHERE room_code = 'C208'), 'Bếp điện', 1, 'good'),
('TB144', (SELECT room_id FROM Rooms WHERE room_code = 'C209'), 'Quạt trần', 2, 'good'),
('TB145', (SELECT room_id FROM Rooms WHERE room_code = 'C210'), 'Lò sưởi', 1, 'broken'),

('TB146', (SELECT room_id FROM Rooms WHERE room_code = 'C301'), 'Két sắt', 1, 'good'),
('TB147', (SELECT room_id FROM Rooms WHERE room_code = 'C302'), 'Máy lọc nước', 1, 'good'),
('TB148', (SELECT room_id FROM Rooms WHERE room_code = 'C303'), 'Đèn đọc sách', 1, 'broken'),
('TB149', (SELECT room_id FROM Rooms WHERE room_code = 'C304'), 'Máy lọc không khí', 1, 'good'),
('TB150', (SELECT room_id FROM Rooms WHERE room_code = 'C305'), 'Ghế tựa', 4, 'good');



-- Thêm thanh toán cho các phòng, sử dụng room_id từ bảng Rooms
INSERT INTO payments (payment_id, payment_code, room_id, electricity_usage, water_usage, total_amount, payment_status, payment_date) VALUES
(1, 'HD2_1_2025', 2, 100.50, 30.20, 1500000.00, 'paid', '2025-01-30 15:51:16'),
(2, 'HD3_1_2025', 3, 134.00, 12.00, 2782000.00, 'unpaid', '2025-01-30 17:00:00'),
(3, 'HD4_1_2025', 4, 234.00, 5.00, 2977000.00, 'unpaid', '2025-01-30 17:00:00'),
(4, 'HD5_1_2025', 5, 123.00, 4.00, 2629000.00, 'unpaid', '2025-01-30 17:00:00'),
(5, 'HD7_1_2025', 7, 342.00, 12.00, 3406000.00, 'unpaid', '2025-01-30 17:00:00'),
(6, 'HD42_1_2025', 42, 123.00, 43.00, 2614000.00, 'unpaid', '2025-01-30 17:00:00'),
(7, 'HD45_1_2025', 45, 123.00, 12.00, 2149000.00, 'unpaid', '2025-01-30 17:00:00'),
(8, 'HD82_1_2025', 82, 214.00, 11.00, 2507000.00, 'unpaid', '2025-01-30 17:00:00'),
(9, 'HD85_1_2025', 85, 435.00, 12.00, 3185000.00, 'unpaid', '2025-01-30 17:00:00');

-- Insert thông tin vào bảng Contracts
-- Hợp đồng cho SV20240001 - Nguyen Van A
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010001', 
 (SELECT student_id FROM Students WHERE student_code = '22010001'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'A102' AND building = 'A'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00, 
 'Hợp đồng thuê phòng A102 cho Nguyen Van A. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240002 - Tran Thi B
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010002', 
 (SELECT student_id FROM Students WHERE student_code = '22010002'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'A102' AND building = 'A'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00, 
 'Hợp đồng thuê phòng A102 cho Tran Thi B. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240003 - Le Van C
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010003', 
 (SELECT student_id FROM Students WHERE student_code = '22010003'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'A103' AND building = 'A'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00, 
 'Hợp đồng thuê phòng A103 cho Le Van C. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240004 - Pham Thi D
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010004', 
 (SELECT student_id FROM Students WHERE student_code = '22010004'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'A103' AND building = 'A'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00, 
 'Hợp đồng thuê phòng A103 cho Pham Thi D. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240005 - Hoang Van E
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010005', 
 (SELECT student_id FROM Students WHERE student_code = '22010005'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'A104' AND building = 'A'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00, 
 'Hợp đồng thuê phòng A104 cho Hoang Van E. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240006 - Do Thi F
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010006', 
 (SELECT student_id FROM Students WHERE student_code = '22010006'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'A105' AND building = 'A'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00, 
 'Hợp đồng thuê phòng A105 cho Do Thi F. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240007 - Vo Van G
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010007', 
 (SELECT student_id FROM Students WHERE student_code = '22010007'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'A105' AND building = 'A'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00, 
 'Hợp đồng thuê phòng A105 cho Vo Van G. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240008 - Nguyen Thi H
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010008', 
 (SELECT student_id FROM Students WHERE student_code = '22010008'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'B202' AND building = 'B'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00, 
 'Hợp đồng thuê phòng B202 cho Nguyen Thi H. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240009 - Tran Van I
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010009', 
 (SELECT student_id FROM Students WHERE student_code = '22010009'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'B202' AND building = 'B'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00, 
 'Hợp đồng thuê phòng B202 cho Tran Van I. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240010 - Nguyen Van J
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010010', 
 (SELECT student_id FROM Students WHERE student_code = '22010010'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'B202' AND building = 'B'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00, 
 'Hợp đồng thuê phòng B202 cho Nguyen Van J. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240011 - Le Thi K
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010011', 
 (SELECT student_id FROM Students WHERE student_code = '22010011'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'B205' AND building = 'B'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00, 
 'Hợp đồng thuê phòng B205 cho Le Thi K. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240012 - Hoang Van L
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010012', 
 (SELECT student_id FROM Students WHERE student_code = '22010012'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'B205' AND building = 'B'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00, 
 'Hợp đồng thuê phòng B205 cho Hoang Van L. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240013 - Le Thi M
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010013', 
 (SELECT student_id FROM Students WHERE student_code = '22010013'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'C302' AND building = 'C'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00,
 'Hợp đồng thuê phòng C302 cho Le Thi M. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240014 - Hoang Van N
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010014', 
 (SELECT student_id FROM Students WHERE student_code = '22010014'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'C302' AND building = 'C'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00,
 'Hợp đồng thuê phòng C302 cho Hoang Van N. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240015 - Le Thi O
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010015', 
 (SELECT student_id FROM Students WHERE student_code = '22010015'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'C302' AND building = 'C'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00,
 'Hợp đồng thuê phòng C302 cho Le Thi O. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240016 - Hoang Van P
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010016', 
 (SELECT student_id FROM Students WHERE student_code = '22010016'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'C305' AND building = 'C'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00,
 'Hợp đồng thuê phòng C305 cho Hoang Van P. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240017 - Le Thi Q
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010017', 
 (SELECT student_id FROM Students WHERE student_code = '22010017'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'C305' AND building = 'C'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00,
 'Hợp đồng thuê phòng C305 cho Le Thi Q. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240018 - Hoang Van Z
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010018', 
 (SELECT student_id FROM Students WHERE student_code = '22010018'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'C305' AND building = 'C'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00,
 'Hợp đồng thuê phòng C305 cho Hoang Van Z. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Hợp đồng cho SV20240019 - Hoang Van X
INSERT INTO Contracts 
(contract_code, student_id, room_id, signed_date, start_date, end_date, deposit, terms, status)
VALUES 
('CT22010019', 
 (SELECT student_id FROM Students WHERE student_code = '22010019'), 
 (SELECT room_id FROM Rooms WHERE room_code = 'C305' AND building = 'C'), 
 '2025-01-10', '2025-01-15', '2025-07-15', 
 2000000.00,
 'Hợp đồng thuê phòng C305 cho Hoang Van X. Sinh viên cam kết tuân thủ quy định của ký túc xá.', 
 'active');

-- Thêm tình trạng phòng cho các sinh viên, sử dụng room_id và student_id từ bảng Rooms và Students
INSERT INTO Room_Status (room_id, student_id, start_date, end_date) VALUES
((SELECT room_id FROM Rooms WHERE room_code = 'A102'), (SELECT student_id FROM Students WHERE student_code = 'SV20240001'), '2024-01-15', NULL),
((SELECT room_id FROM Rooms WHERE room_code = 'A201'), (SELECT student_id FROM Students WHERE student_code = 'SV20240002'), '2024-01-15', NULL),
((SELECT room_id FROM Rooms WHERE room_code = 'A301'), (SELECT student_id FROM Students WHERE student_code = 'SV20240003'), '2024-01-15', NULL),
((SELECT room_id FROM Rooms WHERE room_code = 'B102'), (SELECT student_id FROM Students WHERE student_code = 'SV20240004'), '2024-01-15', NULL),
((SELECT room_id FROM Rooms WHERE room_code = 'B201'), (SELECT student_id FROM Students WHERE student_code = 'SV20240005'), '2024-01-15', NULL),
((SELECT room_id FROM Rooms WHERE room_code = 'B301'), (SELECT student_id FROM Students WHERE student_code = 'SV20240006'), '2024-01-15', NULL),
((SELECT room_id FROM Rooms WHERE room_code = 'C102'), (SELECT student_id FROM Students WHERE student_code = 'SV20240007'), '2024-01-15', NULL),
((SELECT room_id FROM Rooms WHERE room_code = 'C202'), (SELECT student_id FROM Students WHERE student_code = 'SV20240008'), '2024-01-15', NULL),
((SELECT room_id FROM Rooms WHERE room_code = 'C303'), (SELECT student_id FROM Students WHERE student_code = 'SV20240009'), '2024-01-15', NULL);


-- 2. Ảnh hưởng của hợp đồng đến các chức năng hiện có
-- Quản lý sinh viên (R4):

-- Khi thêm sinh viên, có thể yêu cầu tạo hợp đồng mới đi kèm.
-- Khi sinh viên rời ký túc xá, cập nhật trạng thái hợp đồng là đã kết thúc và cập nhật lại tình trạng phòng.
-- Quản lý phòng (R2):

-- Khi thiết lập tình trạng phòng, cần kiểm tra hợp đồng liên quan để không cho thuê phòng đã có hợp đồng hiện hành.
-- Tình trạng phòng có thể tự động cập nhật khi hợp đồng kết thúc.
-- Quản lý thanh toán (R6):

-- Các khoản thanh toán có thể liên kết với hợp đồng, ví dụ: tính tiền thuê theo kỳ, điện nước theo thời gian hợp đồng.
-- Tạo hóa đơn dựa trên thông tin trong hợp đồng (giá thuê, tiền đặt cọc, các chi phí phát sinh theo điều khoản).