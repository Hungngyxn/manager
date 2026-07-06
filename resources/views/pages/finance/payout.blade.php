@extends('layouts.admin', ['accesses' => $accesses ?? [], 'active' => 'finance'])

@section('_content')
    <div class="container-fluid mt-3">

        {{-- Form Tìm kiếm / Bộ lọc đầu trang phẳng --}}
        <form method="GET" action="{{ url()->current() }}" class="card shadow-sm border-0 p-3 mb-3 bg-white">
            <div class="row g-2 align-items-center">

                {{-- Shop Code --}}
                <div class="col">
                    <select name="shop_code" class="form-select form-select-sm text-muted">
                        <option value="">-Shop code-</option>
                        @foreach ($shopCodes as $code)
                            <option value="{{ $code }}" {{ request('shop_code') == $code ? 'selected' : '' }}>
                                {{ $code }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Bank Account --}}
                <div class="col">
                    <input type="text" name="bank_account" class="form-control form-control-sm"
                        placeholder="Bank account" value="{{ request('bank_account') }}">
                </div>

                {{-- Payout ID --}}
                <div class="col">
                    <input type="text" name="payout_id" class="form-control form-control-sm" placeholder="Payout Id"
                        value="{{ request('payout_id') }}">
                </div>

                {{-- Status --}}
                <div class="col">
                    <select name="status" class="form-select form-select-sm text-muted">
                        <option value="">-Status-</option>
                        <option value="Paid" {{ request('status') == 'Paid' ? 'selected' : '' }}>Paid</option>
                        <option value="Processing" {{ request('status') == 'Processing' ? 'selected' : '' }}>Processing
                        </option>
                    </select>
                </div>

                {{-- Paid From Date --}}
                <div class="col">
                    <input type="date" name="from_date" class="form-control form-control-sm text-muted"
                        value="{{ request('from_date') }}">
                </div>

                {{-- To Date --}}
                <div class="col">
                    <input type="date" name="to_date" class="form-control form-control-sm text-muted"
                        value="{{ request('to_date') }}">
                </div>

                {{-- Nhóm Nút hành động Search / Clear --}}
                <div class="col-auto d-flex gap-1">
                    <button type="submit"
                        class="btn btn-info btn-sm text-white px-3 fw-semibold d-flex align-items-center justify-content-center gap-1"
                        style="height: 31px;">
                        <i class="fas fa-search" style="font-size: 0.75rem;"></i> Search
                    </button>
                    @if (request()->anyFilled(['shop_code', 'bank_account', 'payout_id', 'status', 'from_date', 'to_date']))
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
            <a href="{{ route('finance.syncPayouts') }}"
                class="btn btn-info btn-sm text-white fw-semibold px-3 shadow-sm d-flex align-items-center gap-1"
                style="height: 31px;">
                <i class="fas fa-download" style="font-size: 0.75rem;"></i>
                Export pages
            </a>
            <small class="text-muted fw-bold">
                {{ $payouts->firstItem() ?? 0 }} - {{ $payouts->lastItem() ?? 0 }} /
                {{ number_format($payouts->total()) }}
            </small>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Bảng dữ liệu chính của Payout --}}
        <div class="card shadow-sm border-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size: 0.82rem;">
                    <thead class="table-light text-secondary text-uppercase fw-bold border-bottom"
                        style="font-size: 0.75rem;">
                        <tr>
                            <th class="ps-3 py-3">Shop Code</th>

                            {{-- Payout Initiation Date --}}
                            <th class="py-3">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'payout_initiation_date', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}"
                                    class="text-secondary text-decoration-none d-inline-flex align-items-center">
                                    Payout Initiation Date (UTC)
                                    <i
                                        class="fas {{ request('sort') === 'payout_initiation_date' ? (request('direction') === 'asc' ? 'fa-sort-up text-info' : 'fa-sort-down text-info') : 'fa-sort text-muted' }} ms-1"></i>
                                </a>
                            </th>

                            {{-- Payout ID --}}
                            <th class="py-3">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'payout_id', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}"
                                    class="text-secondary text-decoration-none d-inline-flex align-items-center">
                                    Payout ID
                                    <i
                                        class="fas {{ request('sort') === 'payout_id' ? (request('direction') === 'asc' ? 'fa-sort-up text-info' : 'fa-sort-down text-info') : 'fa-sort text-muted' }} ms-1"></i>
                                </a>
                            </th>

                            {{-- Payout Amount --}}
                            <th class="py-3 text-end">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'payout_amount', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}"
                                    class="text-secondary text-decoration-none d-inline-flex align-items-center float-end">
                                    Payout Amount
                                    <i
                                        class="fas {{ request('sort') === 'payout_amount' ? (request('direction') === 'asc' ? 'fa-sort-up text-info' : 'fa-sort-down text-info') : 'fa-sort text-muted' }} ms-1"></i>
                                </a>
                            </th>

                            {{-- Settlement Amount --}}
                            <th class="py-3 text-end">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'settlement_amount', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}"
                                    class="text-secondary text-decoration-none d-inline-flex align-items-center float-end">
                                    Settlement Amount
                                    <i
                                        class="fas {{ request('sort') === 'settlement_amount' ? (request('direction') === 'asc' ? 'fa-sort-up text-info' : 'fa-sort-down text-info') : 'fa-sort text-muted' }} ms-1"></i>
                                </a>
                            </th>

                            {{-- Amount Before Exchange --}}
                            <th class="py-3 text-end">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'amount_before_exchange', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}"
                                    class="text-secondary text-decoration-none d-inline-flex align-items-center float-end">
                                    Amount Before Exchange
                                    <i
                                        class="fas {{ request('sort') === 'amount_before_exchange' ? (request('direction') === 'asc' ? 'fa-sort-up text-info' : 'fa-sort-down text-info') : 'fa-sort text-muted' }} ms-1"></i>
                                </a>
                            </th>

                            {{-- Reserve Amount --}}
                            <th class="py-3 text-end">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'reserve_amount', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}"
                                    class="text-secondary text-decoration-none d-inline-flex align-items-center float-end">
                                    Reserve Amount
                                    <i
                                        class="fas {{ request('sort') === 'reserve_amount' ? (request('direction') === 'asc' ? 'fa-sort-up text-info' : 'fa-sort-down text-info') : 'fa-sort text-muted' }} ms-1"></i>
                                </a>
                            </th>

                            {{-- Payout Completion Date --}}
                            <th class="py-3">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'payout_completion_date', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc']) }}"
                                    class="text-secondary text-decoration-none d-inline-flex align-items-center">
                                    Payout Completion Date
                                    <i
                                        class="fas {{ request('sort') === 'payout_completion_date' ? (request('direction') === 'asc' ? 'fa-sort-up text-info' : 'fa-sort-down text-info') : 'fa-sort text-muted' }} ms-1"></i>
                                </a>
                            </th>

                            <th class="py-3">Status</th>
                            <th class="pe-3 py-3">Bank Account</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payouts as $item)
                            <tr class="border-bottom">
                                <td class="ps-3 fw-semibold text-dark">{{ $item->shop_code }}</td>
                                <td class="text-secondary">
                                    {{ $item->payout_initiation_date ? $item->payout_initiation_date->format('d/m/Y H:i') : '' }}
                                </td>
                                <td class="text-dark font-monospace">{{ $item->payout_id }}</td>
                                <td class="text-end fw-bold text-dark">${{ number_format($item->payout_amount, 2) }}</td>
                                <td class="text-end fw-bold text-dark">${{ number_format($item->settlement_amount, 2) }}
                                </td>
                                <td class="text-end text-secondary">${{ number_format($item->amount_before_exchange, 2) }}
                                </td>
                                <td class="text-end text-secondary">${{ number_format($item->reserve_amount, 2) }}</td>
                                <td class="text-secondary">
                                    {{ $item->payout_completion_date ?$item->payout_completion_date->format('d/m/Y H:i') : '' }}
                                </td>
                                <td>
                                    {{ $item->status }}
                                </td>
                                <td class="pe-3 text-secondary font-monospace">{{ $item->bank_account }}</td>
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
        <div class="d-flex mt-3">
            {{ $payouts->links() }}
        </div>

    </div>
@endsection
