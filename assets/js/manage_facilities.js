document.addEventListener('DOMContentLoaded', function() {
    const buildingSelect = document.getElementById('building-select');
    const floorSelect = document.getElementById('floor-select');
    const roomSelect = document.getElementById('room-select');
    const loadFacilitiesBtn = document.getElementById('load-facilities');
    const facilitiesTableBody = document.querySelector('#facilities-table tbody');
    const addFacilityBtn = document.getElementById('add-facility-btn');
    const facilityModal = document.getElementById('facility-modal');
    const closeBtn = document.querySelector('.close-btn');
    const facilityForm = document.getElementById('facility-form');
    const modalTitle = document.getElementById('modal-title');
    const confirmDeletePopup = document.getElementById('confirm-delete-popup');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');

    // Revoke modal elements
    const revokeModal = document.getElementById('revoke-modal');
    const closeRevokeBtn = document.querySelector('.close-revoke');
    const revokeForm = document.getElementById('revoke-form');

    let currentRoomId = null;
    let deleteFacilityId = null; // Giữ id của cơ sở vật chất cần xóa

    // Hàm hiển thị popup xác nhận xóa
    function showDeletePopup(facilityId) {
        deleteFacilityId = facilityId;
        confirmDeletePopup.style.display = 'block';
    }

    // Sự kiện nút xác nhận xóa
    confirmDeleteBtn.addEventListener('click', function() {
        if (deleteFacilityId) {
            fetch('delete_facility.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `facility_id=${encodeURIComponent(deleteFacilityId)}`
            })
            .then(response => response.json())
            .then(result => {
                showNotification(result.message, result.success ? 'success' : 'error');
                if (result.success) {
                    loadFacilitiesBtn.click();
                }
                confirmDeletePopup.style.display = 'none';
                deleteFacilityId = null;
            })
            .catch(error => {
                showNotification('Lỗi khi xóa cơ sở vật chất', 'error');
                confirmDeletePopup.style.display = 'none';
                deleteFacilityId = null;
                console.error('Error:', error);
            });
        }
    });

    // Sự kiện nút hủy xóa
    cancelDeleteBtn.addEventListener('click', function() {
        confirmDeletePopup.style.display = 'none';
        deleteFacilityId = null;
    });

    // Load danh sách phòng theo tòa nhà và tầng
    function loadRooms() {
        const building = buildingSelect.value;
        const floor = floorSelect.value;
        if (building && floor) {
            fetch(`get_rooms.php?building=${encodeURIComponent(building)}&floor=${encodeURIComponent(floor)}`)
            .then(response => response.json())
            .then(data => {
                roomSelect.innerHTML = '<option value="">Chọn phòng</option>';
                data.rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.room_id;
                    option.textContent = `Phòng ${room.room_number}`;
                    roomSelect.appendChild(option);
                });
            })
            .catch(error => {
                showNotification('Lỗi khi tải danh sách phòng', 'error');
                console.error('Error:', error);
            });
        }
    }
    buildingSelect.addEventListener('change', loadRooms);
    floorSelect.addEventListener('change', loadRooms);

    // Load cơ sở vật chất theo phòng
    loadFacilitiesBtn.addEventListener('click', function() {
        currentRoomId = roomSelect.value;
        const currentRoomNumber = roomSelect.options[roomSelect.selectedIndex].textContent;
        document.getElementById('current-room').textContent = currentRoomNumber;
        if (currentRoomId) {
            fetch(`get_facilities.php?room_id=${encodeURIComponent(currentRoomId)}`)
            .then(response => response.json())
            .then(data => {
                facilitiesTableBody.innerHTML = '';
                data.facilities.forEach(facility => {
                    const row = document.createElement('tr');
                    // Xác định loại thiết bị: 1 => "Sinh viên", 0 => "Chung"
                    const facilityType = facility.is_student_device == 1 ? 'Sinh viên' : 'Chung';
                    row.innerHTML = `
                        <td>${sanitizeHTML(facility.facility_code)}</td>
                        <td>${sanitizeHTML(facility.facility_name)}</td>
                        <td>${sanitizeHTML(facility.quantity)}</td>
                        <td>${facility.status === 'good' ? 'Tốt' : 'Hỏng'}</td>
                        <td>${facilityType}</td>
                        <td>
                            <button class="edit-btn" data-id="${facility.facility_id}"><i class="fas fa-edit"></i></button>
                            ${
                                facility.is_student_device == 1 
                                ? `<button class="revoke-btn" data-id="${facility.facility_id}"><i class="fas fa-ban"></i></button>`
                                : `<button class="delete-btn" data-id="${facility.facility_id}"><i class="fas fa-trash-alt"></i></button>`
                            }
                        </td>
                    `;
                    facilitiesTableBody.appendChild(row);
                });
            })
            .catch(error => {
                showNotification('Lỗi khi tải danh sách cơ sở vật chất', 'error');
                console.error('Error:', error);
            });
        } else {
            showNotification('Vui lòng chọn phòng.', 'error');
        }
    });

    // Thêm/Chỉnh sửa cơ sở vật chất
    addFacilityBtn.addEventListener('click', function() {
        if (!currentRoomId) {
            showNotification('Vui lòng chọn phòng trước khi thêm cơ sở vật chất.', 'error');
            return;
        }
        modalTitle.textContent = 'Thêm Cơ sở Vật chất';
        facilityForm.reset();
        document.getElementById('facility-id').value = '';
        facilityModal.style.display = 'block';
    });

    closeBtn.addEventListener('click', function() {
        facilityModal.style.display = 'none';
    });
    window.addEventListener('click', function(event) {
        if (event.target === facilityModal) {
            facilityModal.style.display = 'none';
        }
        if (event.target === revokeModal) {
            revokeModal.style.display = 'none';
        }
    });

    facilityForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const facilityId = document.getElementById('facility-id').value;
        const facilityCode = document.getElementById('facility-code').value;
        const facilityName = document.getElementById('facility-name').value;
        const quantity = document.getElementById('quantity').value;
        const status = document.getElementById('status').value;
        const url = facilityId ? 'edit_facility.php' : 'add_facility.php';
        const data = {
            room_id: currentRoomId,
            facility_id: facilityId,
            facility_code: facilityCode,
            facility_name: facilityName,
            quantity: quantity,
            status: status
        };
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            showNotification(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                facilityModal.style.display = 'none';
                loadFacilitiesBtn.click();
            }
        })
        .catch(error => {
            showNotification('Lỗi khi lưu cơ sở vật chất', 'error');
            console.error('Error:', error);
        });
    });

    // Xử lý nút Edit, Delete, Revoke
    facilitiesTableBody.addEventListener('click', function(event) {
        const button = event.target.closest('button');
        if (!button) return;
        const facilityId = button.dataset.id;
        if (button.classList.contains('edit-btn')) {
            fetch(`get_facilities.php?facility_id=${encodeURIComponent(facilityId)}`)
            .then(response => response.json())
            .then(data => {
                const facility = data.facilities[0];
                modalTitle.textContent = 'Chỉnh sửa Cơ sở Vật chất';
                document.getElementById('facility-id').value = facility.facility_id;
                document.getElementById('facility-code').value = facility.facility_code;
                document.getElementById('facility-name').value = facility.facility_name;
                document.getElementById('quantity').value = facility.quantity;
                document.getElementById('status').value = facility.status;
                facilityModal.style.display = 'block';
            })
            .catch(error => {
                showNotification('Lỗi khi tải thông tin cơ sở vật chất', 'error');
                console.error('Error:', error);
            });
        }
        if (button.classList.contains('delete-btn')) {
            showDeletePopup(facilityId);
        }
        if (button.classList.contains('revoke-btn')) {
            // Khi nhấn nút revoke, gọi API để lấy thông tin chủ thiết bị và loại thiết bị
            fetch(`get_facility_owner.php?facility_id=${encodeURIComponent(facilityId)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Giả sử API trả về data.owner_name, data.owner_code, data.device_type
                        document.getElementById('owner-name').textContent = data.owner_name;
                        document.getElementById('owner-code').textContent = data.owner_code;
                        document.getElementById('device-type').textContent = data.device_type;
                    } else {
                        document.getElementById('owner-name').textContent = 'Không xác định';
                        document.getElementById('owner-code').textContent = 'Không xác định';
                        document.getElementById('device-type').textContent = 'Không xác định';
                    }
                    document.getElementById('revoke-facility-id').value = facilityId;
                    revokeModal.style.display = 'block';
                })
                .catch(error => {
                    showNotification('Lỗi khi tải thông tin chủ thiết bị', 'error');
                    console.error('Error:', error);
                });
        }
    });

    revokeForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const facilityId = document.getElementById('revoke-facility-id').value;
        const revokeReason = document.getElementById('revoke-reason').value;
        const revokeEvidence = document.getElementById('revoke-evidence').value;
        const data = {
            facility_id: facilityId,
            revoke_reason: revokeReason,
            revoke_evidence: revokeEvidence
        };
        fetch('revoke_facility.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            showNotification(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                revokeModal.style.display = 'none';
                loadFacilitiesBtn.click();
            }
        })
        .catch(error => {
            showNotification('Lỗi khi thu hồi/xóa thiết bị', 'error');
            console.error('Error:', error);
        });
    });
    
    function sanitizeHTML(str) {
        const temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    }
    
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerText = message;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
});
