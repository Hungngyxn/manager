function openEditModal(id, shopName, shopCode, sellerId, onHold, payout) {
 
    $('#editShopForm').attr('action', '/shop/' + id);
    $('#editShopId').val(id);
    $('#editShopName').val(shopName);
    $('#editShopCode').val(shopCode);
    $('#editSellerId').val(sellerId).trigger('change');
    $('#editOnHold').val(onHold)
    $("#editPayout").val(payout);
    
    $('#editShopModal').modal('show');
}

$(document).ready(function () {
    $('.select2').select2({
        dropdownAutoWidth: true,
        width: '100%',
        theme: 'bootstrap-5',
        closeOnSelect: true
    });

    // Tự động filter khi chọn seller
    $('.select2[name="user_id_filter"]').on('change', function () {
        $('#filterForm').submit();
    });
});

function resetFilters() {
    const form = document.getElementById('filterForm');
    form.querySelector('input[name="search"]').value = '';
    if (form.querySelector('select[name="user_id"]')) {
        $(form.querySelector('select[name="user_id"]')).val('').trigger('change');
    }
    document.getElementById('btnsearch').click();
}

function handleImport(input) {
    if (input.files.length > 0) {
        document.getElementById("importSpinner").style.display = "block";
        input.form.submit();
    }
}