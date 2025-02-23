document.addEventListener('DOMContentLoaded', function() {
    const priceForms = document.querySelectorAll('.price-form');

    priceForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Ngăn chặn hành vi mặc định của form
            const capacity = this.dataset.capacity; // Lấy loại phòng từ thuộc tính data-capacity
            const newPrice = this.querySelector('input[name="new_price"]').value; // Lấy giá mới từ input

            // Kiểm tra giá hợp lệ
            if (newPrice <= 0) {
                showNotification('Giá phòng phải lớn hơn 0', 'error');
                return;
            }

            // Gọi hàm cập nhật giá phòng
            updateRoomPrice(capacity, newPrice, this);
        });
    });

    function updateRoomPrice(capacity, newPrice, form) {
        fetch('update_room_price.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                capacity: capacity,
                new_price: newPrice
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Cập nhật giá phòng thành công', 'success');
                // Cập nhật giá hiện tại hiển thị
                const currentPriceSpan = form.querySelector('.current-price span');
                currentPriceSpan.textContent = formatCurrency(newPrice) + ' VNĐ/tháng';
            } else {
                showNotification(data.message || 'Có lỗi xảy ra', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Có lỗi xảy ra khi cập nhật giá phòng', 'error');
        });
    }

    function showNotification(message, type = 'success') {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        notification.className = `notification ${type}`;
        notification.style.display = 'block';

        // Tự động ẩn thông báo sau 3 giây
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount);
    }
});