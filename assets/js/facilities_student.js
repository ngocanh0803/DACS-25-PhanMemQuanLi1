document.addEventListener('DOMContentLoaded', function() {
// Mở modal khi click vào nút "Báo cáo sự cố"
const reportButtons = document.querySelectorAll('.report-btn');
const reportModal = document.getElementById('reportModal');
const facilityIdInput = document.getElementById('facility_id');
const facilityCodeInput = document.getElementById('facility_code');
const closeBtn = document.querySelector('.close'); // Nút đóng modal báo cáo

reportButtons.forEach(button => {
    button.addEventListener('click', function() {
        // Lấy facility_id và facility_code từ data attributes
        const facilityId = this.dataset.facilityId;
        const facilityCode = this.dataset.facilityCode;

        // Đặt giá trị vào các input ẩn
        facilityIdInput.value = facilityId;
        facilityCodeInput.value = facilityCode;

        // Mở modal
        reportModal.style.display = 'flex';
    });
});

// Đóng modal khi click vào nút đóng (dấu x)
if (closeBtn) { // Thêm kiểm tra closeBtn có tồn tại không
  closeBtn.addEventListener('click', function() {
      reportModal.style.display = 'none';
  });
}

window.addEventListener('click', function(event) {
    if (event.target == reportModal) {
        reportModal.style.display = 'none';
    }
});

const reportForm = document.getElementById('reportForm');
reportForm.addEventListener('submit', function(event) {
    event.preventDefault();

    const formData = new FormData(reportForm);
     // Lấy số lượng hiện có
    const facilityId = formData.get('facility_id');
    const currentRow = document.querySelector(`[data-facility-id="${facilityId}"]`).closest('tr'); // Dòng hiện tại
    const currentQuantity = parseInt(currentRow.querySelector('.quantity-value').textContent, 10);

    // Lấy số lượng báo hỏng
    const reportedQuantity = parseInt(formData.get('reported_quantity'), 10);

    // Kiểm tra
     if (reportedQuantity > currentQuantity) {
        alert('Số lượng báo hỏng không được lớn hơn số lượng hiện có!');
        return; // Dừng submit
    }

    fetch('../../php/student/ajax/report_equipment.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Báo cáo thành công!');
            reportModal.style.display = 'none';
            location.reload();
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Đã xảy ra lỗi khi gửi báo cáo.');
    });
});

// Phần Yêu cầu thiết bị (Thêm mới)
const requestEquipmentBtn = document.getElementById('request-equipment-btn'); // Nút "Yêu cầu thiết bị"
const requestModal = document.getElementById('requestModal'); // Modal yêu cầu
const closeRequestModalBtn = document.getElementById('closeRequestModal'); // Nút đóng modal yêu cầu

 // Mở modal yêu cầu
if(requestEquipmentBtn){ //kiểm tra tồn tại
    requestEquipmentBtn.addEventListener('click', function(event) {
        event.preventDefault(); // Ngăn chuyển trang
        requestModal.style.display = 'flex';
    });
}

// Đóng modal yêu cầu
if(closeRequestModalBtn){
    closeRequestModalBtn.addEventListener('click', function() {
        requestModal.style.display = 'none';
    });
}


// Đóng modal khi click ra ngoài (cho cả 2 modal)
window.addEventListener('click', function(event) {
    if (event.target == reportModal) {
        reportModal.style.display = 'none';
    }
    if (event.target == requestModal) {
        requestModal.style.display = 'none';
    }
});

// Xử lý submit form yêu cầu thiết bị (AJAX)
const requestForm = document.getElementById('requestForm');
requestForm.addEventListener('submit', function(event) {
    event.preventDefault();
    const formData = new FormData(requestForm);

    fetch('../../php/student/ajax/submit_request.php', { // Tạo file process_request.php
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message); // Thông báo kết quả
        if (data.success) {
            requestModal.style.display = 'none'; // Đóng modal
            // Cập nhật giao diện (nếu cần)
             location.reload(); // Reload trang
        }
    })
    .catch(error => {
        console.error("Lỗi:", error);
        alert("Đã có lỗi xảy ra, vui lòng thử lại sau.");
    });
});
});
