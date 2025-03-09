// equipment_reports_admin.js

$(document).ready(function() {
    $('.verify-btn').on('click', function() {
        var reportId = $(this).data('report-id');
        if (confirm("Bạn có chắc muốn xác nhận báo cáo này và cập nhật trạng thái thiết bị thành 'Hỏng'?")) {
            $.ajax({
                url: '../../php/admin/ajax/equipment_report_action.php',
                method: 'POST',
                data: { report_id: reportId, action: 'verify' },
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

    $('.processed-btn').on('click', function() {
        var reportId = $(this).data('report-id');
        if (confirm("Bạn có chắc rằng thiết bị đã được xử lý và trạng thái cập nhật thành 'Tốt'?")) {
            $.ajax({
                url: '../../php/admin/ajax/equipment_report_action.php',
                method: 'POST',
                data: { report_id: reportId, action: 'processed' },
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
