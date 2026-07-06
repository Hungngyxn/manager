@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'report'])

@section('_content')
    <div class="container-fluid mt-3">
        <div class="row">
            <div class="col-12">
                <h4 class="font-weight-bold">Reports > sellers</h4>
                <hr>
            </div>
        </div>
        {{-- Section: Report in 7 days --}}
        <div class="row mb-2">
            <div class="col-12">
                <h5 class="fw-bold border-start border-3 border-dark ps-2 mb-0">Report in Date Range</h5>
            </div>
        </div>

        <div class="row">
            {{-- Left Financial Metrics (Sidebar) --}}
            <div class="col-xxl-3 col-xl-4 col-md-5">
                <div class="d-flex flex-column gap-2 mb-4">

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Total</small>
                                <h5 class="fw-bold mb-0">{{ number_format($reportRange['total'], 2) }}</h5>
                            </div>
                            <div class="p-2 rounded text-white"
                                style="background-color: #d63384; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Fulfill
                                    Fee</small>
                                <h5 class="fw-bold mb-0">{{ number_format($reportRange['fulfill_fee'], 2) }}</h5>
                            </div>
                            <div class="p-2 rounded text-white"
                                style="background-color: #2b3e50; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Basecost</small>
                                <h5 class="fw-bold mb-0">{{ number_format($reportRange['basecost'], 2) }}</h5>
                            </div>
                            <div class="p-2 rounded text-white"
                                style="background-color: #0dcaf0; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Seller
                                    Shipping</small>
                                <h5 class="fw-bold mb-0">{{ number_format($reportRange['seller_ship'], 2) }}</h5>
                            </div>
                            <div class="p-2 rounded text-white"
                                style="background-color: #0dcaf0; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Label Fee</small>
                                <h5 class="fw-bold mb-0">{{ number_format($reportRange['label_fee'], 2) }}</h5>
                            </div>
                            <div class="p-2 rounded text-white"
                                style="background-color: #ffc107; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Design Fee</small>
                                <h5 class="fw-bold mb-0">{{ number_format($reportRange['design_fee'], 2) }}</h5>
                            </div>
                            <div class="p-2 rounded text-white"
                                style="background-color: #0dcaf0; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Ads</small>
                                <h5 class="fw-bold mb-0">{{ number_format($reportRange['ads'], 2) }}</h5>
                            </div>
                            <div class="p-2 rounded text-white"
                                style="background-color: #0dcaf0; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Refunds</small>
                                <h5 class="fw-bold mb-0" style="color: #dc3545;">
                                    {{ number_format($reportRange['refunds'], 2) }}</h5>
                            </div>
                            <div class="p-2 rounded text-white"
                                style="background-color: #dc3545; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Profits</small>
                                <h5 class="fw-bold mb-0" style="color: #198754;">
                                    {{ number_format($reportRange['profits'], 2) }}</h5>
                            </div>
                            <div class="p-2 rounded text-white"
                                style="background-color: #198754; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Payouts</small>
                                <h5 class="fw-bold mb-0" style="color: #198754;">
                                    {{ number_format($reportRange['payouts'], 2) }}</h5>
                            </div>
                            <div class="p-2 rounded text-white"
                                style="background-color: #198754; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Right Side: Filter Forms and Chart Area --}}
            <div class="col-xxl-9 col-xl-8 col-md-7 mb-4">
                <div class="card shadow-sm border-0 p-4 h-100">

                    {{-- Filters --}}
                    <form method="GET" action="{{ url()->current() }}" class="d-flex flex-wrap gap-2 mb-4">
                        <div style="width: 200px;">
                            <input type="text" id="date_range" name="date_range" class="form-control form-control-sm"
                                value="{{ request('date_range', $startDate->format('d-m-Y') . ' to ' . $endDate->format('d-m-Y')) }}">
                        </div>
                        <div style="width: 150px;">
                            <select name="seller_id" class="form-select form-select-sm text-muted"
                                onchange="this.form.submit()">
                                <option value="">Chose seller</option>
                                @foreach ($sellers as $seller)
                                    <option value="{{ $seller->id }}"
                                        {{ request('seller_id') == $seller->id ? 'selected' : '' }}>{{ $seller->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="width: 150px;">
                            <select name="shop_code" class="form-select form-select-sm text-muted"
                                onchange="this.form.submit()">
                                <option value="">Shop</option>
                                @foreach ($shops as $code)
                                    <option value="{{ $code }}"
                                        {{ request('shop_code') == $code ? 'selected' : '' }}>{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-info btn-sm text-white px-3"><i class="fas fa-search"></i>
                            Filter</button>
                        @if (request()->has('date_range') || request()->has('seller_id') || request()->has('shop_code'))
                            <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                        @endif
                    </form>

                    <h6 class="fw-bold mb-3">Finance</h6>

                    {{-- Chart Display Canvas --}}
                    <div class="position-relative w-100" style="height: 420px;">
                        <canvas id="financeAnalyticsChart"></canvas>
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
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Total</small>
                            <h5 class="fw-bold mb-0">{{ number_format($reportAll['total'], 2) }}</h5>
                        </div>
                        <div class="p-2 rounded text-white"
                            style="background-color: #d63384; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Fulfill</small>
                            <h5 class="fw-bold mb-0">{{ number_format($reportAll['fulfill_fee'], 2) }}</h5>
                        </div>
                        <div class="p-2 rounded text-white"
                            style="background-color: #2b3e50; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Basecost</small>
                            <h5 class="fw-bold mb-0">{{ number_format($reportAll['basecost'], 2) }}</h5>
                        </div>
                        <div class="p-2 rounded text-white"
                            style="background-color: #0dcaf0; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Design Fee</small>
                            <h5 class="fw-bold mb-0">{{ number_format($reportAll['design_fee'], 2) }}</h5>
                        </div>
                        <div class="p-2 rounded text-white"
                            style="background-color: #0dcaf0; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Total Ads</small>
                            <h5 class="fw-bold mb-0">{{ number_format($reportAll['ads'], 2) }}</h5>
                        </div>
                        <div class="p-2 rounded text-white"
                            style="background-color: #dc3545; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Total
                                Refunds</small>
                            <h5 class="fw-bold mb-0" style="color:#dc3545;">{{ number_format($reportAll['refunds'], 2) }}
                            </h5>
                        </div>
                        <div class="p-2 rounded text-white"
                            style="background-color: #dc3545; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.8rem;">Profits</small>
                            <h5 class="fw-bold mb-0" style="color:#198754;">{{ number_format($reportAll['profits'], 2) }}
                            </h5>
                        </div>
                        <div class="p-2 rounded text-white"
                            style="background-color: #198754; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Processing Script --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('financeAnalyticsChart').getContext('2d');

            const labels = {!! json_encode($chartLabels) !!};
            const chartData = {!! json_encode($chartData) !!};

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Total',
                            data: chartData.total,
                            borderColor: '#d63384',
                            backgroundColor: 'rgba(214, 51, 132, 0.05)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 1
                        },
                        {
                            label: 'Fulfill',
                            data: chartData.fulfill,
                            borderColor: '#ffc107',
                            backgroundColor: 'transparent',
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 1
                        },
                        {
                            label: 'basecost',
                            data: chartData.basecost,
                            borderColor: '#2b3e50',
                            backgroundColor: 'rgba(43, 62, 80, 0.05)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 1
                        },
                        {
                            label: 'Design fee',
                            data: chartData.design_fee,
                            borderColor: '#0dcaf0',
                            backgroundColor: 'transparent',
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 1
                        },
                        {
                            label: 'Refunds',
                            data: chartData.refunds,
                            borderColor: '#dc3545',
                            backgroundColor: 'transparent',
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 1
                        },
                        {
                            label: 'Profits',
                            data: chartData.profits,
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.05)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2,
                            pointRadius: 1
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
                                boxWidth: 8,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: {
                                    size: 11
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f3f3f3'
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

            if (typeof flatpickr !== "undefined") {
                flatpickr("#date_range", {
                    mode: "range",
                    dateFormat: "d-m-Y",
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
