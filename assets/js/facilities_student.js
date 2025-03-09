// facilities_student.js
document.addEventListener("DOMContentLoaded", function() {
    console.log("Trang cơ sở vật chất đã được tải.");

    const modal = document.getElementById("reportModal");
    const closeModal = document.querySelector(".modal .close");
    const reportForm = document.getElementById("reportForm");

    // Lắng nghe click vào nút báo cáo của tất cả các thiết bị
    const reportButtons = document.querySelectorAll('.report-btn');
    reportButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const facilityId = this.getAttribute('data-facility-id');
            const facilityCode = this.getAttribute('data-facility-code');

            // Đưa giá trị vào modal
            document.getElementById("facility_id").value = facilityId;
            document.getElementById("facility_code").value = facilityCode;
            document.getElementById("reported_quantity").value = '';
            document.getElementById("reported_condition").value = '';

            // Hiển thị modal
            modal.style.display = "block";
        });
    });

    // Đóng modal khi click vào dấu "x"
    closeModal.addEventListener('click', function() {
        modal.style.display = "none";
    });

    // Đóng modal khi click bên ngoài vùng modal-content
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    });

    // Xử lý gửi form báo cáo sự cố qua AJAX
    reportForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(reportForm);
        
        fetch('../../php/student/ajax/report_equipment.php', {  // Giả sử file xử lý nằm trong thư mục ajax ở cấp config hoặc tương tự
            method: 'POST',
            body: formData
        })
        
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if(data.success) {
                modal.style.display = "none";
            }
        })
        .catch(error => {
            console.error("Lỗi:", error);
            alert("Đã có lỗi xảy ra, vui lòng thử lại sau.");
        });
    });
});
