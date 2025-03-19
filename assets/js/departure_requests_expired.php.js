
document.addEventListener('DOMContentLoaded', function() {
    let selectedDepartureId = null;

    const approveModal = document.getElementById('approveModal');
    const closeApproveModal = document.getElementById('closeApproveModal');
    const confirmApprove = document.getElementById('confirmApprove');

    const rejectModal = document.getElementById('rejectModal');
    const closeRejectModal = document.getElementById('closeRejectModal');
    const confirmReject = document.getElementById('confirmReject');
    const rejectReason = document.getElementById('reject_reason');

    // Bắt sự kiện nút duyệt
    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            selectedDepartureId = this.getAttribute('data-id');
            approveModal.style.display = 'block';
        });
    });

    // Bắt sự kiện nút từ chối
    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            selectedDepartureId = this.getAttribute('data-id');
            rejectModal.style.display = 'block';
        });
    });

    closeApproveModal.addEventListener('click', function() {
        approveModal.style.display = 'none';
        selectedDepartureId = null;
    });
    closeRejectModal.addEventListener('click', function() {
        rejectModal.style.display = 'none';
        selectedDepartureId = null;
        rejectReason.value = "";
    });

    // Xác nhận duyệt
    confirmApprove.addEventListener('click', function() {
        fetch('ajax/handle_departure_request_expired.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                departure_id: selectedDepartureId,
                action: 'approve'
            })
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if(data.success) {
                window.location.reload();
            }
        })
        .catch(err => {
            console.error("Error:", err);
            alert("Lỗi khi duyệt đơn.");
        });
    });

    // Xác nhận từ chối
    confirmReject.addEventListener('click', function() {
        const reason = rejectReason.value.trim();
        if(!reason) {
            alert("Vui lòng nhập lý do từ chối");
            return;
        }
        fetch('ajax/handle_departure_request_expired.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                departure_id: selectedDepartureId,
                action: 'reject',
                reason: reason
            })
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if(data.success) {
                window.location.reload();
            }
        })
        .catch(err => {
            console.error("Error:", err);
            alert("Lỗi khi từ chối đơn.");
        });
    });

    // Đóng modal khi click outside
    window.addEventListener('click', function(e) {
        if(e.target == approveModal) {
            approveModal.style.display = 'none';
        }
        if(e.target == rejectModal) {
            rejectModal.style.display = 'none';
            rejectReason.value = "";
        }
    });
});
