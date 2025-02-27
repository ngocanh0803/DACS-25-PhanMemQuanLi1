// profile_student.js
document.addEventListener("DOMContentLoaded", function() {
    const editBtn = document.getElementById("edit-btn");
    const inputs = document.querySelectorAll("#profile-form input");

    let isEditing = false;

    editBtn.addEventListener("click", function() {
        if (!isEditing) {
            // Cho phép chỉnh sửa: bỏ thuộc tính readonly cho các input (ngoại trừ student_code)
            inputs.forEach(function(input) {
                if(input.id !== "student_code") {
                    input.removeAttribute("readonly");
                    input.style.backgroundColor = "#fff";
                }
            });
            editBtn.textContent = "Lưu thay đổi";
            isEditing = true;
        } else {
            // Khi đã chỉnh sửa xong, bạn có thể thêm logic AJAX để lưu thay đổi
            // Ví dụ:
            // let formData = new FormData(document.getElementById("profile-form"));
            // Gửi formData tới server để cập nhật thông tin

            // Sau khi lưu xong, đặt lại trạng thái readonly
            inputs.forEach(function(input) {
                input.setAttribute("readonly", "readonly");
                input.style.backgroundColor = "#f5f5f5";
            });
            editBtn.textContent = "Chỉnh sửa thông tin";
            isEditing = false;

            // Hiện thông báo cập nhật thành công (có thể dùng alert hoặc hiển thị trên giao diện)
            alert("Thông tin đã được cập nhật!");
        }
    });
});
