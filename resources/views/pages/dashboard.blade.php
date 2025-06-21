@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'dashboard'])

@section('_content')
    <div class="container-fluid mt-2">
        {{-- Title --}}
        <div class="row">
            <div class="col-12">
                <h4 class="font-weight-bold">Dashboard</h4>
                <hr>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card shadow-sm border-start-warning p-3">
                    <h6 class="text-info fw-bold">Yesterday</h6>
                    <h5>{{ $yesterdayOrders }} orders</h5>
                    <p class="mb-0 fw-semibold" style="font-size:0.95rem">Revenue: {{ number_format($yesterdayRevenue, 2) }}
                        | Cost:
                        {{ number_format($yesterdayCost, 2) }}
                    </p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card shadow-sm border-start-primary p-3">
                    <h6 class="text-primary fw-bold">This Week</h6>
                    <h5>{{ $thisWeekOrders }} orders</h5>
                    <p class="mb-0 fw-semibold" style="font-size:0.95rem">Revenue: {{ number_format($thisWeekRevenue, 2) }}
                        | Cost:
                        {{ number_format($thisWeekCost, 2) }}
                    </p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card shadow-sm border-start-success p-3">
                    <h6 class="text-success fw-bold">This Month</h6>
                    <h5>{{ $thisMonthOrders }} orders</h5>
                    <p class="mb-0 fw-semibold" style="font-size:0.95rem">Revenue: {{ number_format($thisMonthRevenue, 2) }}
                        | Cost:
                        {{ number_format($thisMonthCost, 2) }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Chart Section --}}
        <div class="row">
            <div class="col-12 mb-3">
                <div class="card shadow-sm p-4">
                    <h5 class="mb-4 fw-bold">Orders Over Time</h5>

                    {{-- Filter Form --}}
                    <form method="GET" action="{{ route('dashboard') }}" class="row g-2 align-items-center mb-3">
                        <div class="col-md-4">
                            <input type="text" id="date_range" name="date_range" class="form-control"
                                placeholder="Select Date Range"
                                value="{{ request('date_range', $startDate . ' to ' . $endDate) }}">
                        </div>
                        <div class="col-md-auto">
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-danger">
                                <i class="fas fa-times me-1"></i> Clear
                            </a>
                        </div>
                    </form>

                    {{-- Chart --}}
                    <canvas id="orderChart" height="500"></canvas>

                    {{-- Summary Cards --}}
                    <div class="row mb-4 justify-content-center">
                        @if (Auth::user()->role->name !== 'Seller')
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="card shadow-sm border-start-primary p-3">
                                    <h6 class="text-primary fw-bold">Total Sellers</h6>
                                    <h3 class="fw-bold">{{ $totalSellers }}</h3>
                                </div>
                            </div>
                        @endif
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card shadow-sm border-start-success p-3">
                                <h6 class="text-success fw-bold">Total Orders</h6>
                                <h3 class="fw-bold">{{ $totalOrders }}</h3>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card shadow-sm border-start-danger p-3">
                                <h6 class="text-danger fw-bold">Total Cost</h6>
                                <h3 class="fw-bold">{{ number_format($totalCost, 2) }}</h3>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card shadow-sm border-start-info p-3">
                                <h6 class="text-info fw-bold">Total Revenue</h6>
                                <h3 class="fw-bold">{{ number_format($totalRevenue, 2) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Sellers --}}
        <form id="top-seller-filter" class="row g-3 mb-3 px-3 pt-3">
            <div class="col-md-6">
                <label for="top_seller_range" class="form-label">Select Date Range</label>
                <input type="text" id="top_seller_range" class="form-control" placeholder="Select Date Range">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>

        <table id="top-seller-table" class="table table-bordered m-0 text-center">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Seller Name</th>
                    <th>Total Cost</th>
                    <th>Total Revenue</th>
                    <th>Profit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($topSellers as $index => $seller)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $seller->seller_name }}</td>
                        <td>{{ number_format($seller->total_cost, 2) }}</td>
                        <td>{{ number_format($seller->total_revenue, 2) }}</td>
                        <td>{{ number_format($seller->profit, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Top Shops --}}
        <div class="row mt-5">
            <div class="col-12">
                <div class="card shadow-sm p-4">
                    <h5 class="fw-bold mb-3">Top 5 Shops by Number of Orders</h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Shop Name</th>
                                    <th>Total Orders</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topShops as $index => $shop)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $shop->shop_name }}</td>
                                        <td>{{ $shop->total_orders }}</td>
                                    </tr>
                                @endforeach
                                @if ($topShops->isEmpty())
                                    <tr>
                                        <td colspan="3" class="text-center">No data available</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- JS Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('orderChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartLabels) !!},
                    datasets: [{
                        label: 'Number of Orders',
                        data: {!! json_encode($chartData) !!},
                        fill: true,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        tension: 0.4,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            flatpickr("#date_range", {
                mode: "range",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d-m-Y",
                allowInput: true,
                defaultDate: [
                    "{{ $startDate }}",
                    "{{ $endDate }}"
                ],
                onClose: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        instance.input.form.submit();
                    }
                }
            });

        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#top_seller_range", {
                mode: "range",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d-m-Y",
                allowInput: true,
                defaultDate: ["{{ $from }}", "{{ $to }}"],
            });

            document.getElementById('top-seller-filter').addEventListener('submit', function(e) {
                e.preventDefault();
                const range = document.getElementById('top_seller_range').value;
                let [from, to] = range.split(' to ');
                if (!to) to = from; // fallback nếu người dùng chỉ chọn 1 ngày

                fetch(`/dashboard/top-sellers?from=${from}&to=${to}`)
                    .then(response => response.json())
                    .then(data => {
                        const tbody = document.querySelector('#top-seller-table tbody');
                        tbody.innerHTML = '';

                        if (data.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="5">No data available</td></tr>';
                        } else {
                            data.forEach((seller, index) => {
                                const row = `<tr>
                                    <td>${index + 1}</td>
                                    <td>${seller.seller_name}</td>
                                    <td>${parseFloat(seller.total_cost).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                    <td>${parseFloat(seller.total_revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                    <td>${parseFloat(seller.profit).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                </tr>`;
                                tbody.insertAdjacentHTML('beforeend', row);
                            });
                        }
                    })
                    .catch(error => console.error('Error fetching top sellers:', error));
            });

        });
    </script>
@endsection
