#!/bin/bash -xe
# -x: Hiển thị lệnh đang chạy
# -e: Thoát ngay nếu có lỗi

# --- Biến Cấu hình ---
DB_NAME="dormitory_management"
DB_ROOT_PASSWORD="phong12" # <<< Mật khẩu MySQL root
GIT_REPO_URL="https://github.com/ngocanh0803/DACS-25-PhanMemQuanLi1.git"
APP_DIR_NAME="DACS-25-PhanMemQuanLi1"
WEB_ROOT="/var/www/html"
APP_PATH="${WEB_ROOT}/${APP_DIR_NAME}"
WEBSOCKET_SCRIPT_PATH="${APP_PATH}/php/websocket/server.php"
WEBSOCKET_LOG_PATH="/var/log/websocket_server.log"
DB_SQL_URL="https://raw.githubusercontent.com/ngocanh0803/DACS-25-PhanMemQuanLi1/main/assets/database/ktx.sql"
DB_SQL_LOCAL_PATH="/tmp/ktx.sql"

# --- Cập nhật hệ thống và cài đặt các gói cần thiết ---
echo "Đang cập nhật hệ thống và cài đặt gói..."
apt update -y
apt upgrade -y
apt install -y apache2 mysql-server php libapache2-mod-php php-mysql php-sockets git wget unzip
echo "Cài đặt gói hoàn tất."

# --- Cấu hình MySQL ---
echo "Đang cấu hình MySQL..."
systemctl start mysql
systemctl enable mysql
echo "Đang đặt mật khẩu root MySQL..."
mysql -u root -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_ROOT_PASSWORD}';"
mysql -u root -p"${DB_ROOT_PASSWORD}" -e "FLUSH PRIVILEGES;"
echo "Đặt mật khẩu root MySQL hoàn tất."

# --- Tạo Database trước khi import ---
echo "Đang tạo database '${DB_NAME}' (nếu chưa tồn tại)..."
mysql -u root -p"${DB_ROOT_PASSWORD}" -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME};"
echo "Tạo database hoàn tất."

# --- Tải và Import Database Schema & Data ---
echo "Đang tải file SQL từ GitHub..."
wget -O "${DB_SQL_LOCAL_PATH}" "${DB_SQL_URL}"

echo "Đang import cơ sở dữ liệu vào '${DB_NAME}' (sử dụng force)..."
# === !!! THỬ NGHIỆM: Thêm cờ -f (force) để bỏ qua lỗi và tiếp tục !!! ===
mysql -f -u root -p"${DB_ROOT_PASSWORD}" "${DB_NAME}" < "${DB_SQL_LOCAL_PATH}"
# =====================================================================

# Lấy mã lỗi sau khi import (ngay cả khi dùng -f)
MYSQL_IMPORT_EXIT_CODE=$?
echo "Mã lỗi sau khi import MySQL (với -f): ${MYSQL_IMPORT_EXIT_CODE}"

# Xóa file SQL tạm sau khi import xong
rm "${DB_SQL_LOCAL_PATH}"
echo "Import cơ sở dữ liệu đã cố gắng hoàn tất (với force)."

# *** LƯU Ý QUAN TRỌNG VỀ VIỆC BỎ QUA KIỂM TRA LỖI ***
# Tạm thời chúng ta sẽ KHÔNG thoát script ngay cả khi MYSQL_IMPORT_EXIT_CODE != 0
# để cho phép các bước sau chạy nhằm mục đích gỡ lỗi.
# Trong môi trường thực tế, bạn NÊN kiểm tra mã lỗi này.
# if [ $MYSQL_IMPORT_EXIT_CODE -ne 0 ]; then
#     echo "CẢNH BÁO: Import MySQL có thể đã gặp lỗi (mã lỗi ${MYSQL_IMPORT_EXIT_CODE}), nhưng script sẽ tiếp tục." >&2
#     # Cân nhắc việc thoát ở đây trong production: exit $MYSQL_IMPORT_EXIT_CODE
# fi

# --- Tải Mã nguồn ứng dụng ---
echo "Đang tải mã nguồn ứng dụng từ GitHub..."
rm -f ${WEB_ROOT}/index.html
git clone "${GIT_REPO_URL}" "${APP_PATH}"
echo "Tải mã nguồn hoàn tất."

# --- Cấu hình ứng dụng ---
echo "Đang cấu hình kết nối database trong các file PHP..."
find "${APP_PATH}/php/" -type f -name "*.php" -print0 | while IFS= read -r -d $'\0' file; do
    # Kiểm tra xem file có tồn tại và có thể đọc không
    if [ -r "$file" ] && grep -q -E '\$servername\s*=' "$file"; then
        echo "Đang cập nhật file: $file"
        sed -i "s/\$servername\s*=\s*\".*\"/\$servername = \"localhost\"/g" "$file"
        sed -i "s/\$username\s*=\s*\".*\"/\$username = \"root\"/g" "$file"
        sed -i "s/\$password\s*=\s*\".*\"/\$password = \"${DB_ROOT_PASSWORD}\"/g" "$file"
        sed -i "s/\$dbname\s*=\s*\".*\"/\$dbname = \"${DB_NAME}\"/g" "$file"
    elif [ ! -r "$file" ]; then
        echo "CẢNH BÁO: Không thể đọc file $file để cấu hình." >&2
    fi
done
echo "Cấu hình kết nối database hoàn tất."

# --- Cài đặt quyền ---
echo "Đang cài đặt quyền thư mục..."
# Kiểm tra xem APP_PATH có tồn tại không trước khi chown
if [ -d "${APP_PATH}" ]; then
    chown -R www-data:www-data "${APP_PATH}"
    echo "Cài đặt quyền thư mục hoàn tất."
else
    echo "LỖI: Thư mục ứng dụng ${APP_PATH} không tồn tại. Không thể cài đặt quyền." >&2
    # Cân nhắc việc thoát script ở đây nếu cần: exit 1
fi


# --- Chạy WebSocket Server ---
echo "Đang khởi chạy WebSocket server..."
# Kiểm tra xem file websocket có tồn tại không trước khi chạy
if [ -f "${WEBSOCKET_SCRIPT_PATH}" ]; then
    nohup php "${WEBSOCKET_SCRIPT_PATH}" > "${WEBSOCKET_LOG_PATH}" 2>&1 &
    sleep 5 # Chờ một chút
    if pgrep -f "${WEBSOCKET_SCRIPT_PATH}" > /dev/null; then
        echo "WebSocket server đã chạy."
    else
        echo "CẢNH BÁO: Không thể khởi chạy WebSocket server (tiến trình không tìm thấy). Kiểm tra log: ${WEBSOCKET_LOG_PATH} và quyền thực thi file." >&2
    fi
else
    echo "CẢNH BÁO: Không tìm thấy file WebSocket tại ${WEBSOCKET_SCRIPT_PATH}. Bỏ qua khởi chạy." >&2
fi


# --- Khởi động lại Apache ---
echo "Đang khởi động lại Apache..."
systemctl restart apache2

# --- Hoàn tất ---
echo "======================================================================"
echo "=== Cài đặt và triển khai hoàn tất! (Đã sử dụng mysql --force) ==="
echo "Truy cập ứng dụng tại: http://<Public_IP_Address>/${APP_DIR_NAME}/php/admin/login.php"
echo "Thay <Public_IP_Address> bằng địa chỉ IP công khai của EC2 instance."
echo "Lưu ý: Do sử dụng '--force', CSDL có thể không đầy đủ nếu import gặp lỗi."
echo "      Ứng dụng có thể chạy nhưng gặp lỗi liên quan đến dữ liệu."
echo "Kiểm tra log /var/log/cloud-init-output.log và /var/log/mysql/error.log nếu ứng dụng không hoạt động."
echo "Mã lỗi trả về từ lệnh import MySQL (với -f) là: ${MYSQL_IMPORT_EXIT_CODE}"
echo "Mật khẩu root MySQL đã được đặt thành: ${DB_ROOT_PASSWORD}"
echo "======================================================================"


http://34.226.195.11/DACS-25-PhanMemQuanLi1/php/admin/login.php
sudo cat /var/log/cloud-init-output.log
ls -l /var/www/html/
ls -l /var/www/html/DACS-25-PhanMemQuanLi1/php/admin/
ls -ld /var/www/html/DACS-25-PhanMemQuanLi1