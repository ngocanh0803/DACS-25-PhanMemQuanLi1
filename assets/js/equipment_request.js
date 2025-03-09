// equipment_request.js
document.addEventListener("DOMContentLoaded", function() {
    const requestForm = document.getElementById("requestForm");

    requestForm.addEventListener("submit", function(e) {
        e.preventDefault();
        const formData = new FormData(requestForm);

        fetch('../../php/student/ajax/submit_request.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                requestForm.reset();
            }
        })
        .catch(error => {
            console.error("Lỗi:", error);
            alert("Đã có lỗi xảy ra, vui lòng thử lại sau.");
        });
    });
});
