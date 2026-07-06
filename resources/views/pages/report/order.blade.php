@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'report'])

@section('_content')
    <div class="container-fluid mt-3">
        <div class="row">
            <div class="col-12">
                <h4 class="font-weight-bold">Reports > sellers</h4>
                <hr>
            </div>
        </div>
        {{-- Breadcrumb --}}
        <div class="row mb-2">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1" style="background: none; padding: 0;">
                        <li class="breadcrumb-item text-muted">Reports</li>
                        <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">orders</li>
                    </ol>
                </nav>
            </div>
        </div>

        {{-- Section: Report in 7 days --}}
        <div class="row">
            <div class="col-12">
                <h5 class="fw-bold border-start border-3 border-dark ps-2 mb-3">Report in range</h5>
            </div>
        </div>

        <div class="row">
            {{-- Left Sidebar Metrics --}}
            <div class="col-xxl-3 col-xl-4 col-md-5">
                <div class="d-flex flex-column gap-2 mb-4">

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Awaiting
                                    Shipment</small>
                                <h5 class="fw-bold mb-0">{{ number_format($report7Days['awaiting_shipment']) }}</h5>
                            </div>
                            <div class="p-2 rounded"
                                style="background-color: #d63384; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-boxes"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Awaiting
                                    Collection</small>
                                <h5 class="fw-bold mb-0">{{ number_format($report7Days['awaiting_collection']) }}</h5>
                            </div>
                            <div class="p-2 rounded"
                                style="background-color: #ffc107; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-warehouse"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">In Transit</small>
                                <h5 class="fw-bold mb-0">{{ number_format($report7Days['in_transit']) }}</h5>
                            </div>
                            <div class="p-2 rounded"
                                style="background-color: #2b3e50; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-truck"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Delivered</small>
                                <h5 class="fw-bold mb-0">{{ number_format($report7Days['delivered']) }}</h5>
                            </div>
                            <div class="p-2 rounded"
                                style="background-color: #0dcaf0; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-home"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Completed</small>
                                <h5 class="fw-bold mb-0">{{ number_format($report7Days['completed']) }}</h5>
                            </div>
                            <div class="p-2 rounded"
                                style="background-color: #198754; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-check-square"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Canceled</small>
                                <h5 class="fw-bold mb-0">{{ number_format($report7Days['canceled']) }}</h5>
                            </div>
                            <div class="p-2 rounded"
                                style="background-color: #dc3545; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-times-circle"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Total
                                    Orders</small>
                                <h5 class="fw-bold mb-0">{{ number_format($report7Days['total_orders']) }}</h5>
                            </div>
                            <div class="p-2 rounded"
                                style="background-color: #0dcaf0; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Units
                                    Ordered</small>
                                <h5 class="fw-bold mb-0">{{ number_format($report7Days['units_ordered']) }}</h5>
                            </div>
                            <div class="p-2 rounded"
                                style="background-color: #0dcaf0; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-cubes"></i>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Right Side: Filters and Analytics Chart --}}
            <div class="col-xxl-9 col-xl-8 col-md-7 mb-4">
                <div class="card shadow-sm border-0 p-4 h-100">

                    {{-- Filter controls --}}
                    <form method="GET" action="{{ url()->current() }}" class="d-flex flex-wrap gap-2 mb-4">
                        <div style="width: 230px;">
                            <input type="text" id="date_range" name="date_range" class="form-control form-control-sm"
                                placeholder="Select Date Range"
                                value="{{ request('date_range', $startDate->format('d-m-Y') . ' to ' . $endDate->format('d-m-Y')) }}">
                        </div>
                        <div style="width: 170px;">
                            <select name="seller_id" class="form-select form-select-sm text-muted"
                                onchange="this.form.submit()">
                                <option value="">Chose seller</option>
                                @foreach ($sellers as $seller)
                                    <option value="{{ $seller->id }}"
                                        {{ request('seller_id') == $seller->id ? 'selected' : '' }}>
                                        {{ $seller->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit"
                            class="btn btn-info btn-sm text-white px-3 d-flex align-items-center gap-1">
                            <i class="fas fa-search" style="font-size: 0.75rem;"></i> Filter
                        </button>
                        @if (request()->has('date_range') || request()->has('seller_id'))
                            <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                        @endif
                    </form>

                    <h6 class="fw-bold mb-3">Orders analytics</h6>

                    {{-- Chart container --}}
                    <div class="position-relative w-100" style="height: 380px;">
                        <canvas id="ordersAnalyticsChart"></canvas>
                    </div>

                </div>
            </div>
        </div>

        {{-- Section: All Metrics --}}
        <div class="row mt-2">
            <div class="col-12">
                <h5 class="fw-bold border-start border-3 border-dark ps-2 mb-3">All</h5>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Awaiting
                                Shipment</small>
                            <h5 class="fw-bold mb-0">{{ number_format($reportAll['awaiting_shipment']) }}</h5>
                        </div>
                        <div class="p-2 rounded"
                            style="background-color: #d63384; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Awaiting
                                Collection</small>
                            <h5 class="fw-bold mb-0">{{ number_format($reportAll['awaiting_collection']) }}</h5>
                        </div>
                        <div class="p-2 rounded"
                            style="background-color: #ffc107; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-warehouse"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">In Transit</small>
                            <h5 class="fw-bold mb-0">{{ number_format($reportAll['in_transit']) }}</h5>
                        </div>
                        <div class="p-2 rounded"
                            style="background-color: #2b3e50; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-truck"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Delivered</small>
                            <h5 class="fw-bold mb-0">{{ number_format($reportAll['delivered']) }}</h5>
                        </div>
                        <div class="p-2 rounded"
                            style="background-color: #0dcaf0; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-home"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Completed</small>
                            <h5 class="fw-bold mb-0">{{ number_format($reportAll['completed']) }}</h5>
                        </div>
                        <div class="p-2 rounded"
                            style="background-color: #198754; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-check-square"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Canceled</small>
                            <h5 class="fw-bold mb-0">{{ number_format($reportAll['canceled']) }}</h5>
                        </div>
                        <div class="p-2 rounded"
                            style="background-color: #dc3545; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Total Orders</small>
                            <h5 class="fw-bold mb-0">{{ number_format($reportAll['total_orders']) }}</h5>
                        </div>
                        <div class="p-2 rounded"
                            style="background-color: #0dcaf0; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Units
                                Ordered</small>
                            <h5 class="fw-bold mb-0">{{ number_format($reportAll['units_ordered']) }}</h5>
                        </div>
                        <div class="p-2 rounded"
                            style="background-color: #0dcaf0; color: white; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-cubes"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts JS --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('ordersAnalyticsChart').getContext('2d');

            // Render mảng từ PHP sang JSON bằng Directive của Blade
            const labels = {!! json_encode($chartLabels) !!};
            const chartData = {!! json_encode($chartData) !!};

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Awaiting shipment',
                            data: chartData.awaiting_shipment,
                            borderColor: '#d63384',
                            backgroundColor: 'transparent',
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 2
                        },
                        {
                            label: 'Awaiting collection',
                            data: chartData.awaiting_collection,
                            borderColor: '#ffc107',
                            backgroundColor: 'transparent',
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 2
                        },
                        {
                            label: 'In transit',
                            data: chartData.in_transit,
                            borderColor: '#2b3e50',
                            backgroundColor: 'rgba(43, 62, 80, 0.08)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 2
                        },
                        {
                            label: 'Delivered',
                            data: chartData.delivered,
                            borderColor: '#0dcaf0',
                            backgroundColor: 'rgba(13, 202, 240, 0.08)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 2
                        },
                        {
                            label: 'Completed',
                            data: chartData.completed,
                            borderColor: '#198754',
                            backgroundColor: 'transparent',
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 2
                        },
                        {
                            label: 'Canceled',
                            data: chartData.canceled,
                            borderColor: '#dc3545',
                            backgroundColor: 'transparent',
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f5f5f5'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Hỗ trợ Flatpickr tự động nộp form khi chọn đủ khoảng ngày
            if (typeof flatpickr !== "undefined") {
                flatpickr("#date_range", {
                    mode: "range",
                    dateFormat: "d-m-Y",
                    altInput: true,
                    altFormat: "d-m-Y",
                    allowInput: true,
                    onClose: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length === 2) {
                            instance.input.form.submit();
                        }
                    }
                });
            }
        });
    </script>
@endsection
