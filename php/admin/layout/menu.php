<nav class="sidebar">
    <div class="menu-items">

        <!-- 1. Quản lý Sinh viên (Ưu tiên lên đầu) -->
        <?php if (in_array($role, ['student_manager', 'admin', 'manager', 'accountant'])): ?>
            <div class="menu-item">
                <div class="menu-title">
                    <i class="fas fa-user-graduate"></i>
                    <span>Quản lý Sinh viên</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <ul class="submenu">
                    <li><a href="./import_students.php"><i class="fas fa-user-plus"></i>Thêm SV vào danh sách</a></li>
                    <li><a href="./students_list.php"><i class="fas fa-users"></i>Danh sách Sinh viên</a></li>
                    <li><a href="./admin_late_requests.php"><i class="fas fa-user-clock"></i>Xử lý SV về muộn</a></li>
                    <!-- Chuyển "Chat với sinh viên" lên trên cùng, trong nhóm Quản lý sinh viên -->
                    <li><a href="./admin_chat_combined.php"><i class="fas fa-comments"></i>Chat với Sinh viên</a></li>
                </ul>
            </div>
        <?php endif; ?>
        <!-- 2. Quản lý phòng (Giữ nguyên vị trí) -->
       <?php if (in_array($role, ['manager', 'admin', 'student_manager', 'accountant'])): ?>
            <div class="menu-item">
                <div class="menu-title">
                    <i class="fas fa-door-open"></i>
                    <span>Quản lý Phòng</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <ul class="submenu">
                    <li><a href="./view_floor_plan.php"><i class="fas fa-map-marked-alt"></i>Xem sơ đồ</a></li>
                    <li><a href="./rooms_list.php"><i class="fas fa-list-ol"></i>Danh sách Phòng</a></li>
                    <li><a href="./manage_rooms.php"><i class="fas fa-cogs"></i>Thiết lập phòng</a></li>
                    <li><a href="./room_prices.php"><i class="fas fa-dollar-sign"></i>Thiết lập giá</a></li>
                    <li><a href="./statistics.php"><i class="fas fa-chart-bar"></i>Thống kê</a></li>
                </ul>
            </div>
        <?php endif; ?>

        <!-- 3. Quản lý Hợp đồng (Giữ nguyên vị trí) -->
        <?php if (in_array($role, ['manager', 'admin', 'student_manager', 'accountant'])): ?>
            <div class="menu-item">
                <div class="menu-title">
                    <i class="fas fa-file-contract"></i>
                    <span>Quản lý Hợp đồng</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <ul class="submenu">
                    <li><a href="./contracts_list.php"><i class="fas fa-file-alt"></i>Danh sách Hợp đồng</a></li>
                    <li><a href="./create_contract.php"><i class="fas fa-file-signature"></i>Thêm Hợp đồng</a></li>
                    <li><a href="./registration_requests.php"><i class="fas fa-file-import"></i>Duyệt Y/C vào KTX</a></li>
                    <li><a href="./departure_requests.php"><i class="fas fa-file-export"></i>Y/C rời KTX</a></li>
                      <li><a href="./departure_requests_expired.php"><i class="fas fa-file-export"></i>Y/C rời KTX hết hạn</a></li>
                </ul>
            </div>
        <?php endif; ?>

        <!-- 4. Quản lý Cơ sở vật chất (Giữ nguyên vị trí) -->
        <?php if (in_array($role, ['manager', 'admin', 'student_manager', 'accountant'])): ?>
            <div class="menu-item">
                <div class="menu-title">
                    <i class="fas fa-tools"></i>
                    <span>Cơ sở Vật chất</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <ul class="submenu">
                    <li><a href="./view_facilities.php"><i class="fas fa-eye"></i>Xem CSVC</a></li>
                    <li><a href="./manage_facilities.php"><i class="fas fa-wrench"></i>Thiết lập CSVC</a></li>
                    <li><a href="./equipment_reports.php"><i class="fas fa-clipboard-list"></i>Báo cáo CSVC</a></li>
                    <li><a href="./equipment_requests.php"><i class="fas fa-clipboard-check"></i>Yêu cầu CSVC</a></li>
                </ul>
            </div>
        <?php endif; ?>

        <!-- 5. Quản lý Thanh toán (Giữ nguyên vị trí) -->
        <?php if (in_array($role, ['accountant', 'admin', 'manager', 'student_manager'])): ?>
            <div class="menu-item">
                <div class="menu-title">
                    <i class="fas fa-money-check-alt"></i>
                    <span>Quản lý Thanh toán</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <ul class="submenu">
                    <li><a href="./payments_list.php"><i class="fas fa-faucet"></i>Nhập số điện nước</a></li>
                    <li><a href="./all_payments.php"><i class="fas fa-file-invoice-dollar"></i>Tổng hóa đơn</a></li>
                </ul>
            </div>
        <?php endif; ?>

        <!-- 6. Quản lý User (Chỉ dành cho admin) (Giữ nguyên vị trí) -->
        <?php if ($role === 'admin'): ?>
            <div class="menu-item">
                <div class="menu-title">
                    <i class="fas fa-users-cog"></i>
                    <span>Quản lý User</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <ul class="submenu">
                    <li><a href="./manage_account.php"><i class="fas fa-user-edit"></i>Quản lý User</a></li>
                </ul>
            </div>
        <?php endif; ?>

    </div>
</nav>