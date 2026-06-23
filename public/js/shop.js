function openEditModal(
    id,
    shopName,
    shopCode,
    email,
    sellerId,
    teamId,
    onHold,
    payout
) {
    $("#editShopForm").attr("action", "/shop/" + id);
    $("#editShopId").val(id);
    $("#editShopName").val(shopName);
    $("#editShopCode").val(shopCode);
    $("#editEmail").val(email);
    $("#editSellerId").val(sellerId).trigger("change");
    $("#editTeamId").val(teamId).trigger("change");
    $("#editOnHold").val(onHold);
    $("#editPayout").val(payout);

    $("#editShopModal").modal("show");
}

$(document).ready(function () {
    $(".select2").select2({
        dropdownAutoWidth: true,
        width: "100%",
        theme: "bootstrap-5",
        closeOnSelect: true,
    });

    $(".select2-edit").select2({
        dropdownAutoWidth: true,
        width: "100%",
        theme: "bootstrap-5",
        closeOnSelect: true,
        dropdownParent: $("#editShopModal"),
    });

    // Tự động filter khi chọn seller
    $('.select2[name="user_id_filter"]').on("change", function () {
        $("#filterForm").submit();
    });
});

function resetFilters() {
    // const form = document.getElementById("filterForm");
    // form.querySelector('input[name="search"]').value = "";

    // $(form.querySelector('select[name="user_id"]')).val("").trigger("change");
    // $(form.querySelector('select[name="team_id"]')).val("").trigger("change");
    // document.getElementById("btnsearch").click();
    window.location.href = "{{ route('shops.index') }}";
}

function handleImport(input) {
    if (input.files.length > 0) {
        document.getElementById("importSpinner").style.display = "block";
        input.form.submit();
    }
}
