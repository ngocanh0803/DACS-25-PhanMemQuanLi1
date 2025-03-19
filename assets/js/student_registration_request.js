document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    startDateInput.addEventListener('change', function() {
        const startDate = new Date(this.value);
        if (startDate.toString() !== "Invalid Date") { // Kiểm tra ngày hợp lệ
          const endDate = new Date(startDate);
          endDate.setMonth(startDate.getMonth() + 7);

          // Format ngày tháng năm (YYYY-MM-DD)
          const year = endDate.getFullYear();
          const month = String(endDate.getMonth() + 1).padStart(2, '0'); // Thêm số 0 nếu cần
          const day = String(endDate.getDate()).padStart(2, '0');

          endDateInput.value = `${year}-${month}-${day}`;
          endDateInput.setAttribute('readonly', 'readonly'); // Đặt thuộc tính readonly
        } else {
          endDateInput.value = ''; // Xoá giá trị nếu ngày bắt đầu không hợp lệ
          endDateInput.removeAttribute('readonly'); // Loại bỏ thuộc tính readonly

        }

    });
      // Xuất PDF (giữ nguyên code của bạn)
    document.getElementById('exportPDF').addEventListener('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'pt', 'a4');
        const content = document.getElementById('contract-content') || document.getElementById('contract-content');
        // Sử dụng html2canvas để đảm bảo chuyển đổi đúng HTML sang canvas
        html2canvas(content, { scale: 0.7 }).then(function(canvas) {
            const imgData = canvas.toDataURL('image/png');
            const imgProps = doc.getImageProperties(imgData);
            const pdfWidth = doc.internal.pageSize.getWidth() - 40;
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
            doc.addImage(imgData, 'PNG', 20, 20, pdfWidth, pdfHeight);
            doc.save('don_dang_ky.pdf');
        }).catch(function(error) {
            console.error("Lỗi xuất PDF:", error);
        });
    });

    // Gửi đơn đăng ký: Sử dụng AJAX gửi dữ liệu đến process_registration_request.php
    document.getElementById('submitRequest').addEventListener('click', function() {
        // Thu thập dữ liệu từ form, bao gồm cả file nếu có
        const formData = new FormData();
        formData.append('student_code', document.getElementById('student_code').value);
        formData.append('full_name', document.getElementById('full_name').value);
        formData.append('email', document.getElementById('email').value);
        formData.append('phone', document.getElementById('phone').value);
        formData.append('address', document.getElementById('address').value);
        formData.append('start_date', document.getElementById('start_date').value);
        formData.append('end_date', document.getElementById('end_date').value);
        formData.append('deposit', document.getElementById('deposit').value);
        // Lấy các file upload
        const files = document.getElementById('documents').files;
        for (let i = 0; i < files.length; i++) {
            formData.append('documents[]', files[i]);
        }

        fetch('ajax/process_registration_request.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                window.location.href = 'status_dashboard2.php';
            }
        })
        .catch(error => {
            console.error('Error submitting request:', error);
            alert('Lỗi khi gửi đơn đăng ký.');
        });
    });
});