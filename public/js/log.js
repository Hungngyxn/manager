document.addEventListener("DOMContentLoaded", function () {
    $(".select2").select2({
        width: "100%",
        theme: "bootstrap-5",
    });

        flatpickr(".datepicker", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d-m-Y",
        clickOpens: true,
    });
});