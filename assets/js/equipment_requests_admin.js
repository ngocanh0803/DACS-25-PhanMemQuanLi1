// equipment_requests_admin.js
$(document).ready(function() {
    $('.approve-btn').on('click', function() {
        var requestId = $(this).data('request-id');
        if (confirm("Bạn có chắc muốn duyệt yêu cầu này?")) {
            $.ajax({
                url: '../../php/admin/ajax/handle_request.php',
                method: 'POST',
                data: { request_id: requestId, action: 'approve' },
                dataType: 'json',
                success: function(response) {
                    alert(response.message);
                    if(response.success) {
                        location.reload();
                    }
                },
                error: function() {
                    alert("Lỗi khi xử lý yêu cầu. Vui lòng thử lại sau.");
                }
            });
        }
    });
    
    $('.reject-btn').on('click', function() {
        var requestId = $(this).data('request-id');
        if (confirm("Bạn có chắc muốn từ chối yêu cầu này?")) {
            $.ajax({
                url: '../../php/admin/ajax/handle_request.php',
                method: 'POST',
                data: { request_id: requestId, action: 'reject' },
                dataType: 'json',
                success: function(response) {
                    alert(response.message);
                    if(response.success) {
                        location.reload();
                    }
                },
                error: function() {
                    alert("Lỗi khi xử lý yêu cầu. Vui lòng thử lại sau.");
                }
            });
        }
    });
});
