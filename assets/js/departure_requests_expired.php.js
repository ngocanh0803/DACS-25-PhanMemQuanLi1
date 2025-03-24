document.addEventListener('DOMContentLoaded', function() {
    let selectedDepartureId = null;
    let selectedDepositAmountOriginal = null; // Biến lưu tiền cọc gốc

    const approveModal = document.getElementById('approveModal');
    const closeApproveModal = document.getElementById('closeApproveModal');
    const confirmApprove = document.getElementById('confirmApprove');
    // Lấy các phần tử input và textarea trong modal
    const initiateRefundCheckbox = document.getElementById('initiate_refund');
    const refundReductionReasonTextarea = document.getElementById('refund_reduction_reason');
    const refundAmountInput = document.getElementById('refund_amount');
    const refundAmountSection = document.getElementById('refundAmountSection'); // Phần chứa input điều chỉnh cọc


    const rejectModal = document.getElementById('rejectModal');
    const closeRejectModal = document.getElementById('closeRejectModal');
    const confirmReject = document.getElementById('confirmReject');
    const rejectReason = document.getElementById('reject_reason');

    // const depositAmountInput = document.getElementById('deposit_amount');
    const depositAmountInput = document.getElementById('deposit_amount_original');

    // Bắt sự kiện nút "Duyệt"
    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            selectedDepartureId = this.getAttribute('data-id');
            selectedDepositAmountOriginal = parseFloat(this.getAttribute('data-deposit')); // Lấy tiền cọc gốc
            console.log("Giá trị selectedDepositAmountOriginal:", selectedDepositAmountOriginal); // **THÊM DÒNG NÀY**
            depositAmountInput.value = selectedDepositAmountOriginal.toFixed(2); // Hiển thị trong modal
            // console.log("Đối tượng depositAmountInput:", depositAmountInput); // **THÊM DÒNG NÀY**
            // console.log("Giá trị depositAmountInput.value sau gán:", depositAmountInput.value); 
            refundAmountSection.style.display = 'none'; // Ẩn phần điều chỉnh cọc ban đầu
            approveModal.style.display = 'block';
        });
    });

    // Bắt sự kiện nút "Từ chối"
    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            selectedDepartureId = this.getAttribute('data-id');
            rejectModal.style.display = 'block';
        });
    });

    closeApproveModal.addEventListener('click', function() {
        approveModal.style.display = 'none';
        selectedDepartureId = null;
        selectedDepositAmountOriginal = null; // Reset tiền cọc gốc
        refundAmountInput.value = ""; // Reset input tiền cọc
        refundReductionReasonTextarea.value = ""; // Reset textarea lý do cọc
        initiateRefundCheckbox.checked = false; // Reset checkbox trả cọc
        refundAmountSection.style.display = 'none'; // Ẩn phần điều chỉnh cọc khi đóng modal
    });
    closeRejectModal.addEventListener('click', function() {
        rejectModal.style.display = 'none';
        selectedDepartureId = null;
        rejectReason.value = "";
    });

    // Sự kiện thay đổi checkbox "Khởi tạo yêu cầu trả cọc?"
    initiateRefundCheckbox.addEventListener('change', function() {
        if (this.checked) {
            refundAmountSection.style.display = 'block'; // Hiển thị phần điều chỉnh cọc
        } else {
            refundAmountSection.style.display = 'none'; // Ẩn phần điều chỉnh cọc
            refundAmountInput.value = selectedDepositAmountOriginal.toFixed(2); // Reset về tiền cọc gốc khi bỏ chọn 
            refundReductionReasonTextarea.value = ""; // Xóa lý do điều chỉnh cọc khi bỏ chọn

        }
    });


    // Xác nhận duyệt
    confirmApprove.addEventListener('click', function() {
        // Lấy giá trị
        const initiateRefundValue = initiateRefundCheckbox.checked ? 1 : 0;
        const refundReductionReason = refundReductionReasonTextarea.value.trim();
        const refundAmount = parseFloat(refundAmountInput.value); // Lấy số tiền hoàn trả và parse sang float
        const refundAmount2 = parseFloat(refundAmountInput.value); // Lấy số tiền hoàn trả và parse sang float

        // Validation frontend (chỉ thực hiện nếu checkbox "Khởi tạo yêu cầu trả cọc?" được chọn)
        if (initiateRefundValue === 1) {
            if (refundAmount > selectedDepositAmountOriginal) {
                alert("Số tiền hoàn trả không được lớn hơn tiền cọc hợp đồng.");
                return;
            }
            if (refundAmount < selectedDepositAmountOriginal && !refundReductionReason) {
                alert("Vui lòng nhập lý do điều chỉnh tiền cọc nếu số tiền hoàn trả nhỏ hơn tiền cọc hợp đồng.");
                return;
            }
        }


        fetch('ajax/handle_departure_request_expired.php', { // Đảm bảo đường dẫn AJAX đúng
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                departure_id: selectedDepartureId,
                action: 'approve',
                initiate_refund: initiateRefundValue,
                refund_reduction_reason: refundReductionReason,
                refund_amount: refundAmount // Gửi số tiền hoàn trả
            })
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if(data.success) {
                window.location.reload();
            }
        })
        .catch(function(err) { // **Sửa arrow function catch ở đây**
            console.error("Lỗi:", err); // Ghi log lỗi ra console
            alert("Lỗi khi duyệt đơn."); // Hiển thị thông báo lỗi cho người dùng
        });
    });

    // Xác nhận từ chối (giữ nguyên)
    confirmReject.addEventListener('click', function() {
        const reason = rejectReason.value.trim();
        if(!reason) {
            alert("Vui lòng nhập lý do từ chối");
            return;
        }
        fetch('ajax/handle_departure_request_expired.php', { // Đảm bảo đường dẫn AJAX đúng
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
            alert(data.message); // Hiển thị thông báo từ server
            if(data.success) {
                window.location.reload(); // Tải lại trang nếu thành công
            }
        })
        .catch(function(err) { // **Sửa arrow function catch ở đây**
            console.error("Lỗi:", err); // Ghi log lỗi ra console
            alert("Lỗi khi từ chối đơn."); // Hiển thị thông báo lỗi cho người dùng
        });
    });

    // Đóng modal khi click outside (giữ nguyên)
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