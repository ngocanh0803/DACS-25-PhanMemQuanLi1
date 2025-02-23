document.addEventListener("DOMContentLoaded", function () {
    const notification = document.getElementById("notification");
    const modal = document.getElementById('modal');
    const modalTitle = document.getElementById('modal-title');
    const closeModal = document.querySelector('.close');
    const accountForm = document.getElementById('account-form');
    const addAccountBtn = document.getElementById('add-account-btn');
    const accountList = document.querySelector('.account-list-container table tbody');
    const userIdInput = document.getElementById('user-id');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const roleInput = document.getElementById('role');
    const isApprovedInput = document.getElementById('is_approved');


    // Hàm hiển thị thông báo
    function showNotification(message, type) {
        notification.textContent = message;
        notification.className = `notification show ${type}`;
        setTimeout(() => {
            notification.classList.remove("show", type);
        }, 3000);
    }

    // Mở modal
    function openModal(title = "Thêm tài khoản", data = {}) {
        console.log("Mở modal với dữ liệu:", data); // Log dữ liệu nhận được
        modal.style.display = "block";
        modalTitle.textContent = title;
        userIdInput.value = data.user_id || '';
        usernameInput.value = data.username || '';
        console.log("usernameInput sau khi gán:", usernameInput.value);
        passwordInput.value = data.password || ''; // Gán mật khẩu nếu muốn
        roleInput.value = data.role || 'manager';
        isApprovedInput.value = data.is_approved !== undefined ? data.is_approved : '0';
    
        console.log("Giá trị usernameInput:", usernameInput.value); // Kiểm tra giá trị đã gán
        console.log("test mo modal");
    }
    

    // Đóng modal
    closeModal.addEventListener('click', () => {
        modal.style.display = "none";
    });

    window.addEventListener('click', (event) => {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    });

    addAccountBtn.addEventListener('click', () => {
        openModal();
    });

    accountList.addEventListener('click', function(event) {
        const clickedButton = event.target.closest('button');
        if(clickedButton) {
            const userId = clickedButton.getAttribute('data-id');
            if(clickedButton.classList.contains('edit-btn')) {
                handleEdit(userId);
            } else if (clickedButton.classList.contains('approve-btn')) {
                handleApprove(userId, clickedButton);
            }
            else if (clickedButton.classList.contains('delete-btn')) {
                handleDelete(userId, clickedButton);
            }
        }
    });

    // Xử lý edit
    function handleEdit(userId) {
        const row = document.querySelector(`tr[data-id="${userId}"]`);
        if(row) {
            const userData = {
                user_id: userId,
                username: row.querySelector('td:nth-child(2)').textContent.trim(),
                password: row.querySelector('td:nth-child(3)').textContent.trim(),
                role: row.querySelector('td:nth-child(4)').textContent.trim(),
                is_approved: row.querySelector('td:nth-child(5)').textContent.trim() === 'Đã phê duyệt' ? 1 : 0,
            };
            console.log("Dữ liệu chỉnh sửa:", userData); // Kiểm tra dữ liệu
            openModal("Sửa tài khoản", userData);
        }
    }

    // Xử lý submit form
    accountForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(accountForm);
        const data = Object.fromEntries(formData);
        if(data.user_id) {
            handleUpdate(data);
        } else {
            handleAdd(data);
        }
        modal.style.display = "none";
    });

    function handleAdd(data) {
        fetch('add_account.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                // Reload lại trang để cập nhật
                location.reload();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error("Lỗi khi thêm tài khoản:", error);
            showNotification("Đã xảy ra lỗi khi thêm tài khoản.", 'error');
        })
    }

    function handleUpdate(data) {
        console.log("Cập nhật dữ liệu:", data); // Kiểm tra dữ liệu gửi
        fetch('update_account.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                // Reload lại trang để cập nhật
                location.reload();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error("Lỗi khi cập nhật tài khoản:", error);
            showNotification("Đã xảy ra lỗi khi cập nhật tài khoản.", 'error');
        });
    }

    // Xử lý approve
    function handleApprove(userId, button) {
        button.disabled = true;
        fetch('approve_account.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                button.closest('tr').querySelector('td:nth-child(5)').textContent = 'Đã phê duyệt';
                button.remove();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error("Lỗi khi phê duyệt tài khoản:", error);
            showNotification("Đã xảy ra lỗi khi phê duyệt tài khoản.", 'error');
        })
        .finally(() => {
            button.disabled = false;
        });
    }

    // Xử lý delete
    function handleDelete(userId, button) {
        if (confirm("Bạn có chắc chắn muốn xóa tài khoản này?")) {
            button.disabled = true;
            fetch('delete_account.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    button.closest('tr').remove();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error("Lỗi khi xóa tài khoản:", error);
                showNotification("Đã xảy ra lỗi khi xóa tài khoản.", 'error');
            })
            .finally(() => {
                button.disabled = false;
            });
        }
    }
});
