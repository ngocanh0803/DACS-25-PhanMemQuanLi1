function viewStudentDetails(studentId) {
    $.ajax({
        url: 'student_details.php',
        type: 'GET',
        data: { student_id: studentId },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                document.getElementById('student-popup').innerHTML = `
                    <div class="popup-overlay">
                        <div class="popup-content">
                            <h3>Chi tiết sinh viên</h3>
                            <p><strong>Mã sinh viên:</strong> ${data.student.student_code}</p>
                            <p><strong>Họ và tên:</strong> ${data.student.full_name}</p>
                            <p><strong>Email:</strong> ${data.student.email}</p>
                            <p><strong>Số điện thoại:</strong> ${data.student.phone}</p>
                            <p><strong>Giới tính:</strong> ${data.student.gender}</p>
                            <p><strong>Ngày sinh:</strong> ${data.student.date_of_birth}</p>
                            <p><strong>Địa chỉ:</strong> ${data.student.address}</p>
                            <p><strong>Quốc tịch:</strong> ${data.student.nationality}</p>
                            <p><strong>Chuyên ngành:</strong> ${data.student.major}</p>
                            <p><strong>Năm học:</strong> ${data.student.year_of_study}</p>
                            <p><strong>GPA:</strong> ${data.student.gpa}</p>
                            <p><strong>Trạng thái:</strong> ${data.student.status}</p>
                            <button onclick="closePopup()">Đóng</button>
                        </div>
                    </div>
                `;
                document.getElementById('student-popup').style.display = 'block';
            } else {
                showNotification('Không tìm thấy thông tin sinh viên', 'error');
            }
        },
        error: function() {
            showNotification('Lỗi khi tải thông tin sinh viên', 'error');
        }
    });
}

function showEditPopup(studentId) {
    $.ajax({
        url: 'student_details.php',
        type: 'GET',
        data: { student_id: studentId },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                document.getElementById('student-popup').innerHTML = `
                    <div class="popup-overlay">
                        <div class="popup-content">
                            <h3>Chỉnh sửa thông tin sinh viên</h3>
                            <form id="edit-student-form">
                                <input type="hidden" name="student_id" value="${studentId}">
                                <label>Họ và Tên</label>
                                <input type="text" name="full_name" value="${data.student.full_name}">
                                <label>Email</label>
                                <input type="email" name="email" value="${data.student.email}">
                                <label>Số điện thoại</label>
                                <input type="text" name="phone" value="${data.student.phone}">
                                <label>Địa chỉ</label>
                                <input type="text" name="address" value="${data.student.address}">
                                <button type="button" onclick="updateStudent(${studentId})">Lưu</button>
                                <button type="button" onclick="closePopup()">Hủy</button>
                            </form>
                        </div>
                    </div>
                `;
                document.getElementById('student-popup').style.display = 'block';
            } else {
                showNotification('Không tìm thấy thông tin sinh viên', 'error');
            }
        },
        error: function() {
            showNotification('Lỗi khi tải thông tin sinh viên', 'error');
        }
    });
}



function confirmDeleteStudent(studentId) {
    // Hiển thị popup xác nhận
    document.getElementById('student-popup').innerHTML = `
        <div class="popup-overlay2">
            <div class="popup-content2">
                <h3>Xóa Sinh Viên</h3>
                <p>Bạn có chắc chắn muốn xóa sinh viên này không?</p>
                <button onclick="deleteStudent(${studentId})">Xác nhận</button>
                <button onclick="closePopup()">Hủy</button>
            </div>
        </div>
    `;
    document.getElementById('student-popup').style.display = 'block';
}

function updateStudent(studentId) {
    const formData = $('#edit-student-form').serialize();
    $.ajax({
        url: 'update_student.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                closePopup();
                // Lưu thông báo vào sessionStorage
                sessionStorage.setItem('notification', JSON.stringify({
                    message: 'Cập nhật thành công',
                    type: 'success'
                }));
                location.reload(); // Tải lại trang để cập nhật dữ liệu
            } else {
                showNotification('Cập nhật thất bại', 'error');
            }
        },
        error: function() {
            showNotification('Lỗi khi cập nhật sinh viên', 'error');
        }
    });
}

function deleteStudent(studentId) {
    $.ajax({
        url: 'delete_student.php',
        type: 'GET',
        data: { student_id: studentId },
        success: function(response) {
            // Lưu thông báo vào sessionStorage
            sessionStorage.setItem('notification', JSON.stringify({
                message: 'Xóa sinh viên thành công',
                type: 'success'
            }));
            location.reload(); // Tải lại trang để cập nhật danh sách
        },
        error: function() {
            showNotification('Lỗi khi xóa sinh viên', 'error');
        }
    });
}



function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerText = message;

    document.body.appendChild(notification);

    // Hiển thị thông báo
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);

    // Tự động ẩn thông báo sau 3 giây
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}


function closePopup() {
    document.getElementById('student-popup').style.display = 'none';
}

// Kiểm tra thông báo trong sessionStorage sau khi tải lại trang
window.addEventListener('load', function() {
    const notificationData = sessionStorage.getItem('notification');
    if (notificationData) {
        const { message, type } = JSON.parse(notificationData);
        showNotification(message, type);
        // Xóa thông báo khỏi sessionStorage sau khi hiển thị
        sessionStorage.removeItem('notification');
    }
});
