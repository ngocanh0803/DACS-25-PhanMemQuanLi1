// manage_contracts.js

$(document).ready(function() {
    var modal = $('#confirmModal');
    var span = $('.close');
    var confirmBtn = $('#confirmTerminate');
    var cancelBtn = $('#cancelTerminate');
    var currentContractId;

    // Khi click vào nút chấm dứt
    $('.terminate-btn').on('click', function(e) {
        e.preventDefault();
        currentContractId = $(this).data('contract-id');
        modal.show();
    });

    // Khi click vào nút đóng (x)
    span.on('click', function() {
        modal.hide();
    });

    // Khi click vào nút hủy
    cancelBtn.on('click', function() {
        modal.hide();
    });

    // Khi click vào nút xác nhận chấm dứt
    confirmBtn.on('click', function() {
        if (currentContractId) {
            window.location.href = 'process_terminate_contract.php?contract_id=' + currentContractId;
        }
    });

    // Khi click ngoài modal, đóng modal
    $(window).on('click', function(event) {
        if ($(event.target).is(modal)) {
            modal.hide();
        }
    });
});
