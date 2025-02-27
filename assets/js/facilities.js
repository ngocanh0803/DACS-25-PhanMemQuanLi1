// facilities.js
document.addEventListener('DOMContentLoaded', function() {
    const roomSelect = document.getElementById('roomSelect');
    const facilityContainer = document.getElementById('facilityContainer');
    const notification = document.getElementById('notification');

    roomSelect.addEventListener('change', function() {
        const roomId = roomSelect.value;
        if (roomId) {
            facilityContainer.classList.add('loading'); // Bắt đầu loading
            fetch(`facilities_by_room.php?room_id=${encodeURIComponent(roomId)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.facilities) {
                        displayFacilities(data.facilities);
                        showNotification('Đã tải thành công cơ sở vật chất.', 'success');
                    } else {
                        displayFacilities([]);
                        showNotification(data.error || 'Không thể tải cơ sở vật chất.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Đã xảy ra lỗi khi tải dữ liệu.', 'error');
                })
                .finally(() => {
                    facilityContainer.classList.remove('loading'); // Kết thúc loading
                });
        } else {
            facilityContainer.innerHTML = '<p>Vui lòng chọn phòng để xem cơ sở vật chất.</p>';
        }
    });

    function displayFacilities(facilities) {
        if (Array.isArray(facilities) && facilities.length > 0) {
            facilityContainer.innerHTML = facilities.map(facility => `
                <div class="facility-item">
                    <h3>${sanitizeHTML(facility.facility_name)} (${sanitizeHTML(facility.facility_code)})</h3>
                    <p>Số lượng: ${sanitizeHTML(facility.quantity)}</p>
                    <p>Tình trạng: ${facility.status === 'good' ? 'Tốt' : 'Hỏng'}</p>
                </div>
            `).join('');
        } else {
            facilityContainer.innerHTML = '<p>Không có cơ sở vật chất nào trong phòng này.</p>';
        }
    }

    function showNotification(message, type) {
        notification.textContent = message;
        notification.className = `notification show ${type}`; // 'notification show success' or 'notification show error'

        // Ẩn thông báo sau 3 giây
        setTimeout(() => {
            notification.classList.remove('show', type);
        }, 3000);
    }

    // Hàm sanitize để ngăn chặn XSS
    function sanitizeHTML(str) {
        const temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    }
});
