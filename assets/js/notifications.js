document.addEventListener('DOMContentLoaded', function() {
    const notificationBell = document.getElementById('notification-bell');
    const notificationCount = document.getElementById('notification-count');
    const notificationDropdown = document.getElementById('notification-dropdown');

    /**
     * Cập nhật giao diện (badge + danh sách dropdown) từ một mảng thông báo
     * @param {Array} notifications - Mảng các object { notification_id, title, message, created_at, is_read }
     */
    function updateNotifications(notifications) {
        // Bảo đảm 'notifications' là mảng
        let notifArray = Array.isArray(notifications) ? notifications : [notifications];

        // Cập nhật badge (số lượng thông báo)
        if (notifArray.length > 0) {
            notificationCount.textContent = notifArray.length; 
            notificationCount.style.display = 'inline-block';
        } else {
            notificationCount.textContent = '0';
            notificationCount.style.display = 'none';
        }

        // Tạo HTML cho dropdown
        notificationDropdown.innerHTML = notifArray.map(notif => `
            <div 
              class="notification-item ${notif.is_read == 0 ? 'unread' : ''}" 
              data-id="${notif.notification_id}"
            >
                <strong>${notif.title}</strong>
                <p>${notif.message}</p>
                <small>${notif.created_at}</small>
                <!-- Nếu muốn hiển thị ID ngay trên giao diện: -->
                <!-- <div>ID: ${notif.notification_id}</div> -->
            </div>
        `).join('');
    }

    // (1) GỌI AJAX LẤY TẤT CẢ THÔNG BÁO CŨ
    fetch('../../php/student/ajax/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            console.log('Initial notifications:', data);
            if (data.success && Array.isArray(data.notifications)) {
                updateNotifications(data.notifications);
            } else {
                console.error('Error from server:', data.message);
            }
        })
        .catch(err => console.error('Error fetching old notifications:', err));

    // (2) KẾT NỐI WEBSOCKET ĐỂ NHẬN THÔNG BÁO MỚI
    const ws = new WebSocket('ws://localhost:8080');

    ws.onopen = () => {
        console.log('WebSocket connection opened.');
        // Nếu cần, bạn có thể gửi dữ liệu xác thực ở đây
        // ws.send(JSON.stringify({type: 'auth', username: '...'}));
    };

    ws.onmessage = event => {
        try {
            // Giả sử server gửi JSON (một thông báo hoặc mảng thông báo)
            const data = JSON.parse(event.data);
            console.log('Notification received via WS:', data);

            // Lấy danh sách thông báo cũ trong dropdown
            let currentItems = notificationDropdown.querySelectorAll('.notification-item');
            // Biến NodeList thành mảng JS
            let currentArray = [...currentItems].map(item => ({
                notification_id: item.getAttribute('data-id'),
                title: item.querySelector('strong').innerText,
                message: item.querySelector('p').innerText,
                created_at: item.querySelector('small').innerText,
                is_read: item.classList.contains('unread') ? 0 : 1
            }));

            // 'data' có thể là 1 object thông báo hoặc 1 mảng
            let incoming = Array.isArray(data) ? data : [data];
            // Thêm vào đầu danh sách
            incoming.forEach(notif => currentArray.unshift(notif));

            // Cập nhật UI
            updateNotifications(currentArray);

        } catch (e) {
            console.error('Error parsing WebSocket message:', e);
        }
    };

    ws.onclose = () => {
        console.log('WebSocket connection closed.');
    };

    // (3) TOGGLE DROPDOWN KHI CLICK VÀO CHUÔNG
    notificationBell.addEventListener('click', function() {
        notificationDropdown.classList.toggle('active');
    });

    // (4) ĐÁNH DẤU ĐÃ ĐỌC KHI CLICK VÀO TỪNG THÔNG BÁO
    notificationDropdown.addEventListener('click', function(e) {
        const notifItem = e.target.closest('.notification-item');
        if (notifItem) {
            const notifId = notifItem.getAttribute('data-id');
            // Gửi AJAX đánh dấu thông báo đã đọc
            fetch('../../php/student/ajax/mark_notification_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `notification_id=${encodeURIComponent(notifId)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Xóa class unread
                    notifItem.classList.remove('unread');

                    // Giảm badge đếm
                    let count = parseInt(notificationCount.textContent, 10) - 1;
                    if (count <= 0) {
                        notificationCount.textContent = '0';
                        notificationCount.style.display = 'none';
                    } else {
                        notificationCount.textContent = count;
                    }
                }
            })
            .catch(error => console.error('Error marking notification as read:', error));
        }
    });
    
    // Đóng dropdown khi click ra bên ngoài
    document.addEventListener('click', function(event) {
    if (!notificationBell.contains(event.target) && !notificationDropdown.contains(event.target)) {
        notificationDropdown.classList.remove('active');
    }
    });
});
