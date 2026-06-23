@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'track'])

@section('_content')
    <div class="container-fluid mt-2 px-4">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="font-weight-bold">Tracking Management</h4>
                <hr>
            </div>
        </div>

        <div class="card bg-light text-dark p-4 shadow-sm rounded">

            {{-- Toolbar --}}
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">

                @canEdit
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-dark px-3 py-2" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus me-2"></i> Add Tracking
                    </button>

                    <form action="{{ route('track.syncAll') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-dark px-3 py-2">
                            <i class="fas fa-sync-alt me-2"></i> Sync Tracking
                        </button>
                    </form>
                </div>
                @endcanEdit

                {{-- Filter --}}
                <div class="col-md-7">
                    <form method="GET" action="{{ route('track.index') }}" id="filterForm"
                        class="d-flex align-items-center gap-2 ms-auto">
                        @if (count($sellers))
                            <select name="user_id" class="form-select form-select-md select2 filter-seller">
                                <option value="">-- All Sellers --</option>
                                @foreach ($sellers as $seller)
                                    <option value="{{ $seller->id }}"
                                        {{ request('user_id') == $seller->id ? 'selected' : '' }}>
                                        {{ $seller->name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        <div class="col-md-4">
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
                            class="form-control px-4 py-2 col-3" placeholder="Search ..." style="min-width: 250px;">
                        <button type="submit" class="btn btn-outline-secondary px-2" id="btnsearch">
                            <i class="fas fa-search"></i></button>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i></button>
                    </form>

                </div>
            </div>

            {{-- Alerts --}}
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center">
                    <thead class="table-light text-uppercase">
                        <tr>
                            <th>#</th>
                            <th>SKU</th>
                            <th>Date</th>
                            <th>Seller</th>
                            <th>Status</th>
                            <th>Email</th>
                            <th>Tracking Number</th>
                            <th>Drive</th>
                            @canEdit
                            <th>Actions</th>
                            @endcanEdit
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tracks as $track)
                            <tr>
                                <td>{{ $loop->iteration + $tracks->firstItem() - 1 }}</td>
                                <td>{{ $track->sku }}</td>
                                <td>{{ \Carbon\Carbon::parse($track->date)->format('Y-m-d') }}</td>
                                <td>{{ optional($track->seller)->name }}</td>
                                <td>
                                    <span
                                        class="badge bg-{{ str_contains(strtolower($track->status), 'delivered') ? 'success' : 'secondary' }}">
                                        {{ ucfirst($track->status) }}
                                    </span>
                                </td>
                                <td>{{ $track->email }}</td>
                                <td>{{ $track->tracking_number }}</td>
                                <td>{{ $track->driver_link }}</td>

                                @canEdit
                                <td>
                                    </form>
                                    {{-- Delete --}}
                                    <form action="{{ route('track.destroy', $track->id) }}" method="POST"
                                        onsubmit="return confirm('Delete this record?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                                @endcanEdit

                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">No data found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-end mt-3">
                {{ $tracks->appends(request()->all())->links() }}
            </div>
        </div>
    </div>

    {{-- Modal Add --}}
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('track.store') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Tracking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Seller</label>
                        <select name="seller_id" class="form-select select2" required>
                            @foreach ($allSellers as $seller)
                                <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Tracking Number</label>
                        <input type="text" name="tracking_number" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Carrier</label>
                        <input type="text" name="carrier" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Link Drive</label>
                        <input type="text" name="driver_link" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>SKU</label>
                        <input type="text" name="sku" class="form-control">
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

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
