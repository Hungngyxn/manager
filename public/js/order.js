document.addEventListener("DOMContentLoaded", function () {
    // Update total cost based on SKU and quantity
    function updateCost() {
        const selectedSku = $("#sku").find(":selected");
        const unitCost = parseFloat(selectedSku.data("cost")) || 0;
        const quantity = parseInt($("#quantity").val()) || 0;
        const totalCost = unitCost * quantity;

        $("#calculatedCost").val(totalCost.toFixed(2));
        $("#cost").val(totalCost);
    }

    // Initialize Select2 and bind change event for SKU and quantity
    $(".select2").select2({
        width: "100%",
        theme: "bootstrap-5",
    });

    $(".select2-edit").select2({
        dropdownAutoWidth: true,
        width: "100%",
        theme: "bootstrap-5",
        closeOnSelect: true,
        dropdownParent: $("#editModal"),
    });

    $("#sku, #quantity").on("change keyup", updateCost);
    updateCost(); // Initial calculation on page load

    // Bind event when shop is changed
    $("#shop_name").on("change", function () {
        const selected = $(this).find(":selected");

        $("#shop_code").val(selected.data("code"));
        $("#seller").val(selected.data("user"));
    });

    // When SKU is selected, update cost input
    $("#sku").on("change", function () {
        const selected = $(this).find(":selected");
        $("#cost").val(selected.data("cost") || "0.00");
    });

    // Select all checkboxes
    const selectAllCheckbox = document.getElementById("selectAllTable");
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener("change", function () {
            const isChecked = this.checked;
            document
                .querySelectorAll(".table-checkbox")
                .forEach((cb) => (cb.checked = isChecked));
        });
    }

    // Sync "Select All" checkbox when individual checkboxes change
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

    // Flatpickr for date picker inputs
    flatpickr(".datepicker", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d-m-Y",
        clickOpens: true,
    });

    // Handle delete form
    const deleteForm = document.getElementById("deleteForm");
    if (deleteForm) {
        deleteForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const selected = Array.from(
                document.querySelectorAll(".table-checkbox:checked")
            ).map((cb) => cb.value);

            if (!selected.length) {
                alert("Please select at least one order to delete.");
                return;
            }

            if (
                !confirm("Are you sure you want to delete the selected orders?")
            ) {
                return;
            }

            // Remove old hidden inputs if any
            this.querySelectorAll('input[name="order_ids[]"]').forEach((el) =>
                el.remove()
            );

            // Create new hidden inputs for selected orders
            selected.forEach((id) => {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "order_ids[]";
                input.value = id;
                this.appendChild(input);
            });

            this.submit();
        });
    }
});

// Reset search filters
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

// Handle export all
function submitExport(mode) {
    document.getElementById("exportMode").value = mode;
    new bootstrap.Modal(document.getElementById("exportConfirmModal")).show();
}

// Handle export for selected checkboxes
function submitExportSelected() {
    const selected = document.querySelectorAll(".table-checkbox:checked");
    if (!selected.length) {
        Swal.fire({
            icon: "warning",
            title: "No items selected",
            text: "Please select at least one order to export.",
            confirmButtonText: "Got it",
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

// Handle file import
function handleImport(input) {
    if (input.files.length > 0) {
        document.getElementById("importSpinner").style.display = "block";
        input.form.submit();
    }
}

// Open edit order modal
function openEditModal(id, order_id, sku, quantity, total, fulfill_fee) {
    $("#editOrderForm").attr("action", "/orders/" + id);
    $("#edit_order_id").val(order_id);
    $("#edit_sku").val(sku).trigger("change");
    $("#edit_quantity").val(quantity);
    $("#edit_total").val(total);
    $("#edit_fulfill_fee").val(fulfill_fee);
}
