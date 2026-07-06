@extends('layouts.admin', ['accesses' => $accesses ?? [], 'active' => 'finance'])

@section('_content')
    <div class="container-fluid mt-3">

        {{-- Form Tìm kiếm / Bộ lọc đầu trang --}}
        <form method="GET" action="{{ url()->current() }}" class="card shadow-sm border-0 p-3 mb-3 bg-white">
            <div class="row g-2 align-items-center">

                {{-- Shop Code --}}
                <div class="col">
                    <select name="shop_code" class="form-select form-select-sm select2 text-muted">
                        <option value="">-Shop code-</option>
                        @foreach ($shopCodes as $code)
                            <option value="{{ $code }}" {{ request('shop_code') == $code ? 'selected' : '' }}>
                                {{ $code }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Payout ID --}}
                <div class="col">
                    <input type="text" name="payout_id" class="form-control form-control-sm" placeholder="Payout Id"
                        value="{{ request('payout_id') }}">
                </div>

                {{-- Statement ID --}}
                <div class="col">
                    <input type="text" name="statement_id" class="form-control form-control-sm"
                        placeholder="Statement Id" value="{{ request('statement_id') }}">
                </div>

                {{-- Status --}}
                <div class="col">
                    <select name="status" class="form-select form-select-sm text-muted">
                        <option value="">-Status-</option>
                        <option value="PAID" {{ request('status') == 'PAID' ? 'selected' : '' }}>PAID</option>
                        <option value="PROCESSING" {{ request('status') == 'PROCESSING' ? 'selected' : '' }}>PROCESSING
                        </option>
                    </select>
                </div>

                {{-- From Date --}}
                <div class="col">
                    <input type="date" name="from_date" class="form-control form-control-sm text-muted"
                        value="{{ request('from_date') }}">
                </div>

                {{-- To Date --}}
                <div class="col">
                    <input type="date" name="to_date" class="form-control form-control-sm text-muted"
                        value="{{ request('to_date') }}">
                </div>

                {{-- Nhóm Nút hành động --}}
                <div class="col-auto d-flex gap-1">
                    <button type="submit"
                        class="btn btn-info btn-sm text-white px-3 fw-semibold d-flex align-items-center justify-content-center gap-1"
                        style="height: 31px;">
                        <i class="fas fa-search" style="font-size: 0.75rem;"></i> Search
                    </button>
                    @if (request()->anyFilled(['shop_code', 'payout_id', 'statement_id', 'status', 'from_date', 'to_date']))
                        <a href="{{ url()->current() }}"
                            class="btn btn-danger btn-sm text-white px-2 d-flex align-items-center justify-content-center"
                            style="height: 31px; width: 31px;">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>

            </div>
        </form>

        {{-- Thanh xuất dữ liệu & Hiển thị chỉ số dòng phân trang --}}
        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
            <a href="{{ route('finance.syncStatements') }}"
                class="btn btn-info btn-sm text-white fw-semibold px-3 shadow-sm d-flex align-items-center gap-1"
                style="height: 31px;">
                <i class="fas fa-download" style="font-size: 0.75rem;"></i>
                Export pages
            </a>
            <small class="text-muted fw-bold">
                {{ $statements->firstItem() ?? 0 }} - {{ $statements->lastItem() ?? 0 }} /
                {{ number_format($statements->total()) }}
            </small>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Bảng dữ liệu chính --}}
        <div class="card shadow-sm border-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size: 0.82rem;">
                    <thead class="table-light text-secondary text-uppercase fw-bold border-bottom"
                        style="font-size: 0.75rem;">
                        {{-- Hàng tiêu đề thứ nhất --}}
                        <tr>
                            <th rowspan="2" class="ps-3 py-3 vertical-middle">Shop Code</th>
                            <th rowspan="2" class="py-3 vertical-middle">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'statement_date', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}"
                                    class="text-secondary text-decoration-none d-inline-flex align-items-center">
                                    Statement Date (UTC)
                                    <i
                                        class="fas {{ request('sort') === 'statement_date' ? (request('direction') === 'asc' ? 'fa-sort-up text-info' : 'fa-sort-down text-info') : 'fa-sort text-muted' }} ms-1"></i>
                                </a>
                            </th>
                            <th rowspan="2" class="py-3 vertical-middle">Statement ID</th>
                            <th rowspan="2" class="py-3 vertical-middle">Status</th>
                            <th rowspan="2" class="py-3 text-end vertical-middle">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'settlement_amount', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}"
                                    class="text-secondary text-decoration-none d-inline-flex align-items-center float-end">
                                    Settlement Amount
                                    <i
                                        class="fas {{ request('sort') === 'settlement_amount' ? (request('direction') === 'asc' ? 'fa-sort-up text-info' : 'fa-sort-down text-info') : 'fa-sort text-muted' }} ms-1"></i>
                                </a>
                            </th>

                            <th colspan="4"
                                class="text-center py-2 border-start border-end text-dark bg-light bg-opacity-50">Adjustment
                                Breakdown</th>

                            <th rowspan="2" class="pe-3 py-3 ps-3 vertical-middle">Payout ID</th>
                        </tr>
                        {{-- Hàng tiêu đề thứ hai --}}
                        <tr class="border-bottom">
                            <th class="py-2 text-end text-muted border-start">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'net_sales', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}"
                                    class="text-muted text-decoration-none d-inline-flex align-items-center float-end">
                                    Net Sales
                                    <i
                                        class="fas {{ request('sort') === 'net_sales' ? (request('direction') === 'asc' ? 'fa-sort-up text-info' : 'fa-sort-down text-info') : 'fa-sort text-muted' }} ms-1"></i>
                                </a>
                            </th>
                            <th class="py-2 text-end text-muted">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'shipping', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}"
                                    class="text-muted text-decoration-none d-inline-flex align-items-center float-end">
                                    Shipping
                                    <i
                                        class="fas {{ request('sort') === 'shipping' ? (request('direction') === 'asc' ? 'fa-sort-up text-info' : 'fa-sort-down text-info') : 'fa-sort text-muted' }} ms-1"></i>
                                </a>
                            </th>
                            <th class="py-2 text-end text-muted">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'fee', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}"
                                    class="text-muted text-decoration-none d-inline-flex align-items-center float-end">
                                    Fee
                                    <i
                                        class="fas {{ request('sort') === 'fee' ? (request('direction') === 'asc' ? 'fa-sort-up text-info' : 'fa-sort-down text-info') : 'fa-sort text-muted' }} ms-1"></i>
                                </a>
                            </th>
                            <th class="py-2 text-end text-muted border-end">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'adjustment', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}"
                                    class="text-muted text-decoration-none d-inline-flex align-items-center float-end">
                                    Adjustment
                                    <i
                                        class="fas {{ request('sort') === 'adjustment' ? (request('direction') === 'asc' ? 'fa-sort-up text-info' : 'fa-sort-down text-info') : 'fa-sort text-muted' }} ms-1"></i>
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($statements as $item)
                            <tr class="border-bottom">
                                <td class="ps-3 fw-semibold text-dark">{{ $item->shop_code }}</td>
                                <td class="text-secondary">
                                    {{ $item->statement_date ? $item->statement_date->format('d/m/Y H:i') : '' }}
                                </td>
                                <td class="text-dark">{{ $item->statement_id }}</td>    
                                <td class="text-dark">{{ $item->status }}</td>
                                <td
                                    class="text-end fw-bold {{ $item->settlement_amount < 0 ? 'text-danger' : 'text-dark' }}">
                                    {{ $item->settlement_amount < 0 ? '-$' . number_format(abs($item->settlement_amount), 2) : '$' . number_format($item->settlement_amount, 2) }}
                                </td>
                                <td class="text-end text-secondary border-start">
                                    ${{ number_format($item->net_sales, 2) }}
                                </td>
                                <td class="text-end text-secondary">
                                    {{ $item->shipping < 0 ? '-$' . number_format(abs($item->shipping), 2) : '$' . number_format($item->shipping, 2) }}
                                </td>
                                <td class="text-end text-secondary">
                                    {{ $item->fee < 0 ? '-$' . number_format(abs($item->fee), 2) : '$' . number_format($item->fee, 2) }}
                                </td>
                                <td class="text-end text-secondary border-end">
                                    {{ $item->adjustment < 0 ? '-$' . number_format(abs($item->adjustment), 2) : '$' . number_format($item->adjustment, 2) }}
                                </td>
                                <td class="pe-3 ps-3 text-secondary font-monospace">{{ $item->payout_id ?? '0' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">No data available</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Điều hướng phân trang --}}
        <div class="d-flex justify-content-center mt-3">
            {{ $statements->links() }}
        </div>

    </div>

    {{-- Bổ sung class css nhỏ để đảm bảo chữ căn giữa ô theo chiều dọc khi dùng rowspan --}}
    <style>
        .vertical-middle {
            vertical-align: middle !important;
        }
    </style>

    @push('scripts')
        <script src="/js/report.js"></script>
        <script>
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        </script>
    @endpush
@endsection
