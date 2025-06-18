document.addEventListener("DOMContentLoaded", function () {
    // Cập nhật tổng chi phí dựa trên SKU và số lượng
    function updateCost() {
        const selectedSku = $("#sku").find(":selected");
        const unitCost = parseFloat(selectedSku.data("cost")) || 0;
        const quantity = parseInt($("#quantity").val()) || 0;
        const totalCost = unitCost * quantity;

        $("#calculatedCost").val(totalCost.toFixed(2));
        $("#cost").val(totalCost);
    }

    // Khởi tạo Select2 và gắn sự kiện thay đổi SKU, số lượng
    $(".select2").select2({
        width: "100%",
        theme: "bootstrap-5",
    });

    $("#sku, #quantity").on("change keyup", updateCost);
    updateCost(); // Gọi lần đầu khi trang tải xong

    // Gắn sự kiện khi thay đổi shop
    $("#shop_name").on("change", function () {
        const selected = $(this).find(":selected");
        
        $("#shop_code").val(selected.data("code"));
        $("#seller").val(selected.data("user"));
    });

    // Khi chọn SKU thì cập nhật ô cost
    $("#sku").on("change", function () {
        const selected = $(this).find(":selected");
        $("#cost").val(selected.data("cost") || "0.00");
    });

    // Chọn tất cả checkbox
    const selectAllCheckbox = document.getElementById("selectAllTable");
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener("change", function () {
            const isChecked = this.checked;
            document
                .querySelectorAll(".table-checkbox")
                .forEach((cb) => (cb.checked = isChecked));
        });
    }

    // Đồng bộ trạng thái "Chọn tất cả" nếu checkbox con thay đổi
    document.querySelectorAll(".table-checkbox").forEach((cb) => {
        cb.addEventListener("change", function () {
            const checkboxes = document.querySelectorAll(".table-checkbox");
            const checkedCount = document.querySelectorAll(
                ".table-checkbox:checked"
            ).length;
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = checkedCount === checkboxes.length;
            }
        });
    });

    // Flatpickr cho các ô chọn ngày
    flatpickr(".datepicker", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d-m-Y",
        clickOpens: true,
    });

    // Gắn xử lý form xóa
    const deleteForm = document.getElementById("deleteForm");
    if (deleteForm) {
        deleteForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const selected = Array.from(
                document.querySelectorAll(".table-checkbox:checked")
            ).map((cb) => cb.value);

            if (!selected.length) {
                alert("Vui lòng chọn ít nhất một đơn hàng để xoá.");
                return;
            }

            // Hiển thị hộp thoại xác nhận
            if (
                !confirm(
                    "Bạn có chắc chắn muốn xoá những đơn hàng đã chọn không?"
                )
            ) {
                return;
            }

            // Xóa input cũ nếu có
            this.querySelectorAll('input[name="order_ids[]"]').forEach((el) =>
                el.remove()
            );

            // Tạo input mới cho các order đã chọn
            selected.forEach((id) => {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "order_ids[]";
                input.value = id;
                this.appendChild(input);
            });

            // Gửi form
            this.submit();
        });
    }
});

// Reset bộ lọc tìm kiếm
function resetFilters() {
    const form = document.getElementById("filterForm");
    if (!form) return;

    const searchInput = form.querySelector('input[name="search"]');
    const sellerSelect = form.querySelector('select[name="user_id"]');
    const shopSelect = form.querySelector('select[name="shop_name"]');
    const dateStart = form.querySelector('input[name="date_start"]');
    const dateEnd = form.querySelector('input[name="date_end"]');
    const searchBtn = document.getElementById("btnsearch");
    const searchSkuMissing = form.querySelector('input[name="missing_sku"]');

    if (searchInput) searchInput.value = "";
    if (sellerSelect) $(sellerSelect).val("").trigger("change");
    if (shopSelect) $(shopSelect).val("").trigger("change");
    if (dateStart) dateStart.value = "";
    if (dateEnd) dateEnd.value = "";
    if (searchSkuMissing) searchSkuMissing.checked = false;
    if (searchBtn) searchBtn.click();
}

// Xử lý export toàn bộ
function submitExport(mode) {
    document.getElementById("exportMode").value = mode;
    new bootstrap.Modal(document.getElementById("exportConfirmModal")).show();
}

// Xử lý export theo checkbox đã chọn
function submitExportSelected() {
    const selected = document.querySelectorAll(".table-checkbox:checked");
    if (!selected.length) {
        Swal.fire({
            icon: "warning",
            title: "Không có mục nào được chọn",
            text: "Vui lòng chọn ít nhất một đơn hàng để export.",
            confirmButtonText: "Đã hiểu",
        });
        return;
    }

    const form = document.getElementById("exportForm");
    document.getElementById("exportMode").value = "selected";
    form.querySelectorAll('input[name="order_ids[]"]').forEach((el) =>
        el.remove()
    );

    selected.forEach((cb) => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "order_ids[]";
        input.value = cb.value;
        form.appendChild(input);
    });

    new bootstrap.Modal(document.getElementById("exportConfirmModal")).show();
}

// Xử lý import file
function handleImport(input) {
    if (input.files.length > 0) {
        document.getElementById("importSpinner").style.display = "block";
        input.form.submit();
    }
}

// Mở modal sửa đơn hàng
function openEditModal(id, extra_id, sku, quantity, total, fulfill_fee) {
    $("#editOrderForm").attr("action", "/orders/" + id);
    document.getElementById("edit_extra_id").value = extra_id;
    document.getElementById("edit_sku").value = sku;
    document.getElementById("edit_quantity").value = quantity;
    document.getElementById("edit_total").value = total;
    document.getElementById("edit_fulfill_fee").value = fulfill_fee;
}
