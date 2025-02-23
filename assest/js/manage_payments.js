// manage_payments.js

$(document).ready(function() {
    // Xác nhận trước khi xóa hóa đơn
    $('.delete-payment-btn').on('click', function(e) {
        e.preventDefault();
        var link = $(this).attr('href');

        if (confirm('Bạn có chắc chắn muốn xóa hóa đơn này?')) {
            window.location.href = link;
        }
    });
});
