@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'report'])

@section('_content')
    <div class="container-fluid mt-2 px-4">
        <div class="row">
            <div class="col-12">
                <h4 class="font-weight-bold">Report</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-3">
                <div class="bg-light text-dark card p-4 shadow-sm rounded">
                    <div class="d-flex justify-content-end align-items-center mb-4 flex-wrap" style="float: right">
                        <form method="GET" action="{{ route('report.index') }}" id="filterForm"
                            class="d-flex align-items-center gap-2">
                            @if (auth()->user()->role->name !== 'Seller')
                                <select name="user_id" class="form-select px-3 py-2 select2">
                                    <option value="">-- All Sellers --</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}"
                                            {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif

                            <div class="col-md-5">
                                <div class="input-group flatpickr" id="dateRangePicker">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                    <input type="hidden" id="date_start" value="{{ $date_start }}">
                                    <input type="hidden" id="date_end" value="{{ $date_end }}">
                                    <input type="text" id="date_range" name="date_range" class="form-control"
                                        placeholder="Date Range" value="{{ request('date_range') }}">
                                </div>
                            </div>
                            <input type="text" name="search" value="{{ request('search') }}"
                                class="form-control px-3 py-2" placeholder="Search ....">

                            <button class="btn btn-outline-secondary px-4" type="submit" id="btnsearch">
                                <i class="fas fa-search"></i>
                            </button>
                            <button type="button" class="btn btn-danger" onclick="resetFilters()">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>

                    {{-- Success Message --}}
                    @if (session('status'))
                        <div class="alert alert-success mt-2">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Seller</th>
                                    <th>Unit Sale</th>
                                    <th>Revenue</th>
                                    <th>Basecost</th>
                                    <th>Chi phí Ads</th>
                                    <th>Profit</th>
                                    <th>Ads/Profit (%)</th>
                                    <th>Bonus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reports as $report)
                                    <tr>
                                        <td>{{ $report->userInfo?->name ?? 'N/A' }}</td>
                                        <td>{{ $report->unit_sale }}</td>
                                        <td>{{ $report->revenue }}</td>
                                        <td>{{ $report->base_cost }}</td>
                                        <td>
                                            {{ $report->ads }}
                                            @if (auth()->user()->role->name !== 'Seller' && $report->userInfo)
                                                <button type="button" class="btn btn-sm ms-2"
                                                    onclick="openEditReportModal({{ $report->id }}, '{{ $report->ads }}', {{ $report->userInfo->id }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            @endif
                                        </td>
                                        <td>{{ $report->profit }}</td>
                                        <td>
                                            @php
                                                $percent =
                                                    $report->profit != 0
                                                        ? round(($report->ads / $report->profit) * 100, 2)
                                                        : 0;
                                            @endphp

                                            @if ($percent > 25)
                                                <span style="color:red">{{ $percent }}%</span>
                                            @else
                                                <span>{{ $percent }}%</span>
                                            @endif
                                        </td>
                                        <td>{{ $report->bonus }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{ $reports->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal chỉnh sửa Ads --}}
    <div class="modal fade" id="editAdsModal" tabindex="-1" aria-labelledby="editAdsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-sm border-0">
                <form method="POST" action="{{ route('ads-fee.store') }}">
                    @csrf
                    <input type="hidden" name="report_id" id="edit_report_id">

                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Cập nhật chi phí Ads theo tháng</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4 py-3">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Người bán</label>
                            <select name="user_id" id="edit_user_id" class="form-select" required>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Ngày</label>
                            <input type="date" name="date" id="edit_date" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Chi phí Ads</label>
                            <input type="number" step="0.01" name="ads" id="edit_ads" class="form-control"
                                required>
                        </div>
                    </div>

                    <div class="modal-footer px-4">
                        <button type="submit" class="btn btn-success">Lưu thay đổi</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Huỷ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    @push('scripts')
        <script src="/js/report.js"></script>
    @endpush
@endsection
