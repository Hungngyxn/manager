document.addEventListener("DOMContentLoaded", function () {
    $(".select2").select2({
        width: "100%",
        theme: "bootstrap-5",
    });
    
    const dateStart = document.getElementById("date_start").value;
    const dateEnd = document.getElementById("date_end").value;

    flatpickr("#date_range", {
        mode: "range",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d-m-Y",
        allowInput: true,
        defaultDate: [dateStart, dateEnd],
    });
});

function fetchAdsFee(userId, date) {
    fetch(`/ads-fee/fetch?user_id=${userId}&date=${date}`)
        .then((response) => response.json())
        .then((data) => {
            document.getElementById("edit_ads").value = data.ads ?? 0;
        })
        .catch((error) => {
            alert("Không thể tải dữ liệu chi phí Ads từ server.");
            console.error(error);
        });
}

function openEditReportModal( _adsIgnore, userId) {
    const today = new Date().toISOString().slice(0, 10); // YYYY-MM-DD

    document.getElementById("edit_date").value = today;

    const userSelect = document.getElementById("edit_user_id");
    for (let i = 0; i < userSelect.options.length; i++) {
        userSelect.options[i].selected = userSelect.options[i].value == userId;
    }

    fetchAdsFee(userId, today);

    const dateInput = document.getElementById("edit_date");
    dateInput.onchange = function () {
        const selectedDate = this.value;
        const selectedUser = document.getElementById("edit_user_id").value;
        if (selectedDate && selectedUser) {
            fetchAdsFee(selectedUser, selectedDate);
        }
    };

    new bootstrap.Modal(document.getElementById("editAdsModal")).show();
}

function resetFilters() {
    const form = document.getElementById("filterForm");
    if (!form) return;

    const searchInput = form.querySelector('input[name="search"]');
    const sellerSelect = form.querySelector('select[name="user_id"]');
    const dateRange = document.querySelector("#date_range");
    const searchBtn = document.getElementById("btnsearch");

    if (searchInput) searchInput.value = "";
    if (sellerSelect) $(sellerSelect).val("").trigger("change");

    if (dateRange && dateRange._flatpickr) {
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        dateRange._flatpickr.setDate([firstDay, lastDay]);
    }

    if (searchBtn) searchBtn.click();
}
