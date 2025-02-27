<!-- header.php -->
<div class="header">
    <div class="sidebar-logo">
        <img style="width: 54%;" src="https://qldtbeta.phenikaa-uni.edu.vn/congsinhvien/logo.png" alt="Logo">
    </div>
    <div style="display: flex; text-align: center;">
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Tìm kiếm thông tin">
        </div>
        <div class="user-profile">
            <img src="https://qldtbeta.phenikaa-uni.edu.vn/upload/Core/images/no-avatar.png" alt="User">
            <span><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></span>
            <div class="dropdown-menu">
                <a href="#"><i class="fas fa-key"></i> Thay đổi mật khẩu</a>
                <a href="#"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>
    </div>
</div>
