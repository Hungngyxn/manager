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
                        @canEdit
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="card shadow-sm border-start-primary p-3">
                                <h6 class="text-primary fw-bold">Total Sellers</h6>
                                <h3 class="fw-bold">{{ $totalSellers }}</h3>
                            </div>
                        </div>
                        @endcanEdit
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

        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm p-4">
                    <h5 class="fw-bold mb-4">🏆 Top Seller Of The Month</h5>

                    <div class="row justify-content-center text-center align-items-end mb-5" style="gap: 10px">
                        @php
                            $first = $topSellers[0] ?? null;
                            $second = $topSellers[1] ?? null;
                            $third = $topSellers[2] ?? null;
                        @endphp

                        <div class="row justify-content-center text-center align-items-end mb-5" style="gap: 10px;">
                            @if ($second)
                                <div class="col-md-3 col-4 order-0" style="margin-top: 40px;">
                                    <div class="position-relative top-seller">
                                        <img src="{{ asset('images/profile.png') }}" alt="avatar"
                                            class="rounded-circle mb-3" style="width: 110px; height: 110px;">
                                        <div class="text-primary fs-3">🥈</div>
                                        <div class="fw-bold fs-5">{{ $second['name'] }}</div>
                                        <div class="text-primary fs-4 fw-semibold">{{ $second['score'] }}</div>
                                    </div>
                                </div>
                            @endif

                            @if ($first)
                                <div class="col-md-3 col-4 order-1" style="margin-top: 0px;">
                                    <div class="position-relative top-seller">
                                        <img src="{{ asset('images/profile.png') }}" alt="avatar"
                                            class="rounded-circle mb-3" style="width: 130px; height: 130px;">
                                        <div class="text-warning fs-2">👑</div>
                                        <div class="fw-bold fs-4">{{ $first['name'] }}</div>
                                        <div class="text-d fs-3 fw-bold">{{ $first['score'] }}</div>
                                    </div>
                                </div>
                            @endif

                            @if ($third)
                                <div class="col-md-3 col-4 order-2" style="margin-top: 60px;">
                                    <div class="position-relative top-seller">
                                        <img src="{{ asset('images/profile.png') }}" alt="avatar"
                                            class="rounded-circle mb-3" style="width: 100px; height: 100px;">
                                        <div class="text-success fs-4">🥉</div>
                                        <div class="fw-bold fs-5">{{ $third['name'] }}</div>
                                        <div class="text-success fs-4 fw-semibold">{{ $third['score'] }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover text-center align-middle">
                            <tbody>
                                @foreach ($topSellers->slice(3)->values() as $index => $seller)
                                    <tr>
                                        <td style="width: 50px;" class="text-muted fw-bold">
                                            {{ $index + 4 }}
                                        </td>
                                        <td class="text-start d-flex align-items-center gap-3">
                                            <img src="{{ asset('images/profile.png') }}" alt="Avatar"
                                                class="rounded-circle" style="width: 36px; height: 36px;">
                                            <div>
                                                <div class="fw-bold">{{ $seller['name'] }}</div>
                                            </div>
                                        </td>
                                        <td class="fw-bold">{{ $seller['score'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        {{-- </div>
        <div style="height: 500px"></div>
        <button class="sitrit" > Sít rịt nè</button> --}}

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
@endsection
