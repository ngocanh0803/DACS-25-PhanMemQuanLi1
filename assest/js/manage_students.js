// manage_students.js

$(document).ready(function() {
    var studentIdToDelete = null;
    var modal = $('#confirmModal');
    var notification = $('#notification');

    // Mở modal khi nhấn nút xóa
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();
        studentIdToDelete = $(this).data('student-id');
        modal.show();
    });

    // Đóng modal khi nhấn nút đóng (x) hoặc hủy
    $('.close, #cancelDelete').on('click', function() {
        modal.hide();
        studentIdToDelete = null;
    });

    // Xác nhận xóa
    $('#confirmDelete').on('click', function() {
        if (studentIdToDelete) {
            // Chuyển hướng đến trang xóa với student_id
            window.location.href = 'delete_student.php?student_id=' + studentIdToDelete;
        }
    });

    // Hàm hiển thị thông báo (đã được xử lý trong PHP)
    function showNotification(message, isError = false) {
        notification.text(message);
        if (isError) {
            notification.addClass('error');
        } else {
            notification.removeClass('error');
        }
        notification.fadeIn();

        setTimeout(function() {
            notification.fadeOut();
        }, 3000);
    }

    // Tìm kiếm sinh viên trong bảng
    $('#search-input').on('keyup', function() {
        var filter = $(this).val().toUpperCase();
        $('#students-table tbody tr').each(function() {
            var text = $(this).text().toUpperCase();
            if (text.indexOf(filter) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Phân trang cho bảng danh sách sinh viên
    const rowsPerPage = 10;
    const rows = $('#students-table tbody tr');
    const totalRows = rows.length;
    const totalPages = Math.ceil(totalRows / rowsPerPage);
    let currentPage = 1;

    function displayPage(page) {
        var start = (page - 1) * rowsPerPage;
        var end = start + rowsPerPage;

        rows.hide().slice(start, end).show();
    }

    function createPagination() {
        var paginationContainer = $('#pagination');
        if (!paginationContainer.length) return;

        for (var i = 1; i <= totalPages; i++) {
            var pageButton = $('<button>')
                .text(i)
                .addClass('page-btn')
                .toggleClass('active', i === currentPage)
                .on('click', function() {
                    var selectedPage = parseInt($(this).text());
                    currentPage = selectedPage;
                    displayPage(currentPage);
                    $('.page-btn').removeClass('active');
                    $(this).addClass('active');
                });
            paginationContainer.append(pageButton);
        }
    }

    // Chỉ thực hiện phân trang nếu có bảng và đủ số hàng
    if (totalRows > rowsPerPage) {
        displayPage(currentPage);
        createPagination();
    } else {
        rows.show();
    }

    // Hàm hiển thị thông báo từ URL (đã được xử lý trong các tệp PHP)
    // <?php
    // // Đã được xử lý trong các tệp PHP thông qua đoạn script
    // ?>
});

