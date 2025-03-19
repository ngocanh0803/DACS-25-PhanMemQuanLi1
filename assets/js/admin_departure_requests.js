// Xử lý modal duyệt và từ chối
document.addEventListener('DOMContentLoaded', function() {
    let selectedDepartureId = null;

    const approveModal = document.getElementById('approveModal');
    const rejectModal = document.getElementById('rejectModal');

    const closeApproveModal = document.getElementById('closeApproveModal');
    const closeRejectModal = document.getElementById('closeRejectModal');

    const confirmApprove = document.getElementById('confirmApprove');
    const confirmReject = document.getElementById('confirmReject');

    const assignRoomSelect = document.getElementById('assign_room');
    const rejectReason = document.getElementById('reject_reason');

    // Khi nhấn nút duyệt đơn
    document.querySelectorAll('.approve-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            selectedDepartureId = this.getAttribute('data-id');
            approveModal.style.display = 'block';
        });
    });

    // Khi nhấn nút từ chối đơn
    document.querySelectorAll('.reject-btn').forEach(function(btn) {
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

    // Xử lý duyệt đơn
    confirmApprove.addEventListener('click', function() {
        const room_id = assignRoomSelect.value;
        if (!room_id) {
            alert("Vui lòng chọn phòng bàn giao.");
            return;
        }
        fetch('ajax/handle_departure_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                departure_id: selectedDepartureId,
                action: 'approve',
                room_id: room_id
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
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
        fetch('ajax/handle_departure_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                departure_id: selectedDepartureId,
                action: 'reject',
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                window.location.reload();
            }
        })
        .catch(error => {
            console.error("Lỗi từ chối đơn:", error);
            alert("Lỗi khi xử lý đơn.");
        });
    });

    window.addEventListener('click', function(e) {
        if (e.target == approveModal) {
            approveModal.style.display = 'none';
            selectedDepartureId = null;
        }
        if (e.target == rejectModal) {
            rejectModal.style.display = 'none';
            selectedDepartureId = null;
            rejectReason.value = "";
        }
    });
});