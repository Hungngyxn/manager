@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'log'])

@section('_content')
    <div class="container-fluid mt-2 px-4">
        <div class="row">
            <div class="col-12">
                <h4 class="font-weight-bold">Order Error Logs</h4>
                <hr>
            </div>
        </div>

        <div class="d-flex justify-content-end align-items-center gap-2">
            <form method="GET" action="{{ route('log.index') }}" id="filterForm" class="d-flex align-items-center gap-2">
                <select name="user_id" class="form-select px-4 py-2 select2">
                    <option value="">-- All Sellers --</option>
                    <option value="null" {{ request('user_id') === 'null' ? 'selected' : '' }}>Unassigned Shops</option>
                    @foreach ($sellers as $seller)
                        <option value="{{ $seller->id }}" {{ request('user_id') == $seller->id ? 'selected' : '' }}>
                            {{ $seller->name }}
                        </option>
                    @endforeach
                </select>

                <div class="input-group flatpickr position-relative">
                    <input type="text" class="form-control ps-5 datepicker" name="date_start"
                        value="{{ request('date_start') }}" placeholder="Start Date">
                    <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-secondary">
                        <i class="fas fa-calendar-alt"></i>
                    </span>
                </div>
                <div class="input-group flatpickr position-relative">
                    <input type="text" class="form-control ps-5 datepicker" name="date_end"
                        value="{{ request('date_end') }}" placeholder="End Date">
                    <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-secondary">
                        <i class="fas fa-calendar-alt"></i>
                    </span>
                </div>

                <input type="text" name="search" value="{{ request('search') }}" class="form-control px-4 py-2"
                    placeholder="Search ..." style="min-width: 250px;">

                <button type="submit" class="btn btn-outline-secondary px-2" id="btnsearch">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            <form method="GET" action="{{ route('log.index') }}">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-times"></i>
                </button>
            </form>
        </div>


        <div class="table-responsive">
            <table class="table table-bordered text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Order ID</th>
                        <th>Error Message</th>
                        <th>Seller</th>
                        <th>Ngày</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <th scope="row">{{ $loop->iteration + $logs->firstItem() - 1 }}</th>
                            <td>{{ $log->order_id }}</td>
                            <td>{{ $log->error_message }}</td>
                            <td>{{ $log->user->name ?? '-' }}</td>
                            <td>{{ $log->created_at->format('d-m-Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">Không có log nào</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    </div>

    @push('scripts')
        <script src="/js/log.js"></script>
    @endpush
@endsection
