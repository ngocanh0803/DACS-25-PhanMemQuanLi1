
// JavaScript xử lý modal duyệt/từ chối
document.addEventListener('DOMContentLoaded', function() {
    let selectedApplicationId = null;

    // Approve Modal
    const approveModal = document.getElementById('approveModal');
    const closeApproveModal = document.getElementById('closeApproveModal');
    const confirmApprove = document.getElementById('confirmApprove');
    const assignRoomSelect = document.getElementById('assign_room');

    // Reject Modal
    const rejectModal = document.getElementById('rejectModal');
    const closeRejectModal = document.getElementById('closeRejectModal');
    const confirmReject = document.getElementById('confirmReject');
    const rejectReason = document.getElementById('reject_reason');

    // Khi nhấn nút duyệt đơn
    document.querySelectorAll('.approve-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            selectedApplicationId = this.getAttribute('data-id');
            // Mở modal duyệt
            approveModal.style.display = 'block';
        });
    });

    // Khi nhấn nút từ chối đơn
    document.querySelectorAll('.reject-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            selectedApplicationId = this.getAttribute('data-id');
            // Mở modal từ chối
            rejectModal.style.display = 'block';
        });
    });

    closeApproveModal.addEventListener('click', function() {
        approveModal.style.display = 'none';
        selectedApplicationId = null;
    });
    closeRejectModal.addEventListener('click', function() {
        rejectModal.style.display = 'none';
        selectedApplicationId = null;
        rejectReason.value = "";
    });

    // Xử lý duyệt đơn
    confirmApprove.addEventListener('click', function() {
        const room_id = assignRoomSelect.value;
        if (!room_id) {
            alert("Vui lòng chọn phòng.");
            return;
        }
        // Gửi yêu cầu duyệt qua AJAX
        fetch('ajax/handle_registration_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                application_id: selectedApplicationId,
                action: 'approve',
                room_id: room_id
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if(data.success) {
                window.location.reload();
            }
        })
        .catch(error => {
            console.error("Lỗi duyệt đơn:", error);
            alert("Lỗi khi xử lý đơn.");
        });
    });

    // Xử lý từ chối đơn
    confirmReject.addEventListener('click', function() {
        const reason = rejectReason.value.trim();
        if (!reason) {
            alert("Vui lòng nhập lý do từ chối.");
            return;
        }
        fetch('ajax/handle_registration_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                application_id: selectedApplicationId,
                action: 'reject',
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if(data.success) {
                window.location.reload();
            }
        })
        .catch(error => {
            console.error("Lỗi từ chối đơn:", error);
            alert("Lỗi khi xử lý đơn.");
        });
    });

    // Đóng modal khi click ngoài nội dung modal
    window.addEventListener('click', function(e) {
        if(e.target == approveModal) {
            approveModal.style.display = 'none';
            selectedApplicationId = null;
        }
        if(e.target == rejectModal) {
            rejectModal.style.display = 'none';
            selectedApplicationId = null;
            rejectReason.value = "";
        }
    });
});
