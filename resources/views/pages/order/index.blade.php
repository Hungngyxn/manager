@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'order'])

@section('_content')
    @php
        $isAdmin = auth()->user()->role->name === 'Administrator';
    @endphp
    <div class="container-fluid mt-2">
        <div class="row">
            <div class="col-12">
                <h4 class="font-weight-bold">Orders</h4>
                <hr>
            </div>
        </div>
        <div class="row">
            <div class="col-12 mb-3">
                <div class="bg-light text-dark card p-4 shadow-sm rounded">
                    <form method="GET" action="{{ route('orders.index') }}" id="filterForm"
                        class="d-flex align-items-center gap-2">
                        @if (count($sellers) > 0)
                            <select name="user_id" class="form-select px-3 py-2 select2">
                                <option value="">-- All Sellers --</option>
                                <option value="Unassigned" {{ request('user_id') === 'Unassigned' ? 'selected' : '' }}>
                                    Unassigned</option>
                                @foreach ($sellers as $seller)
                                    <option value="{{ $seller->id }}"
                                        {{ request('user_id') == $seller->id ? 'selected' : '' }}>
                                        {{ $seller->name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        <select name="shop_name" class="form-select px-3 py-2 select2">
                            <option value="">-- All Shops --</option>
                            @foreach ($shopNames as $shopName)
                                <option value="{{ $shopName }}"
                                    {{ request('shop_name') == $shopName ? 'selected' : '' }}>
                                    {{ $shopName }}
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
                        <div class="input-group form-check align-items-center" style="font-size: 1rem;">
                            <input class=" me-1" type="checkbox" name="missing_sku" value="1"
                                style="width: 1.2em; height: 1.2em;" {{ request('missing_sku') ? 'checked' : '' }}>
                            <label class="form-check-label"> Show orders missing SKU </label>
                        </div>
                        <div class="input-group">
                            <input type="text" name="search" value="{{ request('search') }}"
                                class="form-control px-3 py-2" placeholder="Search ....">
                        </div>
                        <button class="btn btn-outline-secondary px-4" type="submit" id="btnsearch">
                            <i class="fas fa-search"></i>
                        </button>
                        <button type="button" class="btn btn-danger" onclick="resetFilters()">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>

                    {{-- Import/Export Buttons --}}
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap pt-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            {{-- Import Dropdown --}}
                            <div class="btn-group">
                                <button class="btn btn-outline-dark btn-md dropdown-toggle px-4 py-2" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-file-import me-2"></i> Import
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('orders.create') }}"><i
                                                class="fas fa-keyboard me-1"></i> Import Manual</a></li>
                                    <li>
                                        <form action="{{ route('orders.import') }}" method="POST"
                                            enctype="multipart/form-data" class="dropdown-item p-0 m-0 border-0"
                                            id="importForm">
                                            @csrf
                                            <label class="dropdown-item d-block" style="cursor: pointer">
                                                <i class="fas fa-file-excel me-1"></i> Import Excel (Max 20 files)
                                                <input type="file" name="file[]" accept=".xlsx,.xls"
                                                    onchange="handleImport(this)" style="display: none;" multiple>
                                            </label>
                                        </form>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                            data-bs-target="#addFulfillModal">
                                            <i class="fas fa-truck me-1"></i> Add Fulfill Fee
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            {{-- Export Dropdown --}}
                            <div class="btn-group">
                                <button class="btn btn-outline-dark btn-md dropdown-toggle px-4 py-2" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-file-export me-1"></i> Export
                                </button>
                                <ul class="dropdown-menu">
                                    <li><button class="dropdown-item" type="button" onclick="submitExportSelected()"><i
                                                class="fas fa-check-square me-1"></i> Export Selected</button></li>
                                    <li><button class="dropdown-item" type="button" onclick="submitExport('current')"><i
                                                class="fas fa-clone me-1"></i> Export Current Page</button></li>
                                    <li><button class="dropdown-item" type="button" onclick="submitExport('all')"><i
                                                class="fas fa-globe me-1"></i> Export All</button></li>
                                </ul>
                            </div>

                            {{-- Delete --}}
                            @if ($isAdmin)
                                <form method="POST" action="{{ route('orders.delete') }}" id="deleteForm">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="order_ids[]" id="deleteOrderIds">
                                    <button type="submit" class="btn btn-outline-danger btn-md px-4 py-2">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    {{-- Spinner --}}
                    <div id="importSpinner" class="text-center my-3" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading file Excel, pleasewait...</p>
                    </div>

                    {{-- Alerts --}}
                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center align-middle">
                            <thead class="table text-uppercase">
                                <tr>
                                    <th><input type="checkbox" id="selectAllTable"></th>
                                    <th>Seller</th>
                                    <th>ORDER</th>
                                    <th>Product</th>
                                    <th>Shop</th>
                                    <th>Quantity</th>
                                    <th>Cost</th>
                                    <th>Total</th>
                                    <th>Fulfill Fee</th>
                                    <th>Profit</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $order)
                                    <tr @if (is_null($order->skuInfo)) class="table-danger" @endif>
                                        <td><input type="checkbox" class="table-checkbox" value="{{ $order->id }}">
                                        </td>
                                        <td>{{ $order->seller->name ?? ' Unassigned' }}</td>
                                        <td>
                                            <div style="display: flex; flex-direction: column;">
                                                <strong>{{ $order->order_id }}</strong>
                                                <span class="text-gray-500 text-sm">{{ $order->created_at }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $order->skuInfo?->name ?? ($order->skuInfo?->sku ?? $order->sku . ' wrong sku') }}
                                        </td>
                                        <td>
                                            @if (Str::contains($order->shop_name, 'New Shop'))
                                                <span class="badge bg-danger">{{ $order->shop_name }}</span><br>
                                                <a href="{{ route('shop.create') }}"
                                                    class="btn btn-sm btn-primary mt-1">+
                                                    Add Shop</a>
                                            @else
                                                {{ $order->shop_name }}
                                            @endif
                                        </td>
                                        <td>{{ $order->quantity }}</td>
                                        <td>{{ $order->cost }}</td>
                                        <td>{{ $order->total }}</td>
                                        <td>{{ $order->fulfill_fee }}</td>
                                        <td>{{ $order->profit }}</td>
                                        <td>
                                            {{-- Edit --}}
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                data-bs-target="#editModal" type="button"
                                                onclick="openEditModal({{ $order->id }},'{{ addslashes($order->order_id) }}', '{{ addslashes($order->sku) }}', {{ $order->quantity }}, {{ $order->total }}, {{ $order->fulfill_fee ?? 0 }})">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            {{-- Delete --}}
                                            <form action="{{ route('orders.destroy', $order->id) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this order?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center">No orders found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <strong>Total order: {{ $orderCount }}</strong>
                                </tr>
                            </tfoot>

                        </table>
                    </div>

                    {{-- Pagination + PerPage --}}
                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                        <form method="GET" action="{{ route('orders.index') }}" class="d-flex align-items-center">
                            @foreach (request()->except('perPage') as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <select name="perPage" class="form-select" onchange="this.form.submit()">
                                @foreach ([10, 20, 50, 100] as $size)
                                    <option value="{{ $size }}"
                                        {{ request('perPage', 10) == $size ? 'selected' : '' }}>{{ $size }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                        <div>
                            {{ $orders->appends(request()->only(['search', 'perPage', 'user_id', 'shop_name', 'date_start', 'date_end', 'missing_sku']))->links() }}
                        </div>
                    </div>

                    {{-- Export Hidden Form --}}
                    <form id="exportForm" method="POST" action="{{ route('orders.export') }}">
                        @csrf
                        <input type="hidden" name="mode" id="exportMode">

                        {{-- truyền lại toàn bộ filter --}}
                        @foreach (request()->only(['user_id', 'shop_name', 'date_start', 'date_end', 'missing_sku', 'search']) as $name => $value)
                            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                        @endforeach

                        {{-- export current page --}}
                        <div id="currentPageIds">
                            @foreach ($orders as $order)
                                <input type="hidden" name="order_ids[]" value="{{ $order->id }}">
                            @endforeach
                        </div>
                    </form>


                    {{-- Modal Edit --}}
                    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <form method="POST" id="editOrderForm" class="modal-content">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="id" id="edit_id">

                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title" id="editModalLabel">Edit SKU order</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body px-4">
                                        <div class="mb-3">
                                            <label for="edit_order_id" class="form-label">Order ID</label>
                                            <input type="text" name="order_id" id="edit_order_id"
                                                class="form-control" required readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit_sku" class="form-label">SKU</label>
                                            <select name="sku" id="edit_sku" class="form-select select2-edit"
                                                required>
                                                <option value="">-- Select SKU --</option>
                                                @foreach ($skus as $sku)
                                                    <option value="{{ $sku->sku }}">{{ $sku->sku }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit_quantity" class="form-label">Quantity</label>
                                            <input type="text" name="quantity" id="edit_quantity"
                                                class="form-control" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit_total" class="form-label">Total</label>
                                            <input type="text" name="total" id="edit_total" class="form-control"
                                                required
                                                {{ auth()->user()->role->name !== 'Administrator' ? 'readonly' : '' }}>
                                        </div>

                                        <div class="mb-3">
                                            <label for="edit_fulfill_fee" class="form-label">Fulfill Fee</label>
                                            <input type="text" name="fulfill_fee" id="edit_fulfill_fee"
                                                class="form-control" required>
                                        </div>
                                    </div>

                                    <div class="modal-footer px-4">
                                        <button type="submit" class="btn btn-success">Save</button>
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Modal xác nhận Export --}}
                    <div class="modal fade" id="exportConfirmModal" tabindex="-1"
                        aria-labelledby="exportConfirmModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-sm">
                                <div class="modal-header bg-light">
                                    <h5 class="modal-title">Confirm Export</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">Are you sure you want to export data?</div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-success" form="exportForm"
                                        data-bs-dismiss="modal">
                                        <i class="fas fa-file-excel me-1"></i> Confirm Export
                                    </button>
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Add Fulfill Fee --}}
    <div class="modal fade" id="addFulfillModal" tabindex="-1" aria-labelledby="addFulfillModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Add Fulfill Fee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('orders.download-sample') }}">
                            <i class="fas fa-download me-2 text-success"></i> Download Sample
                        </a>
                    </div>
                    <form action="{{ route('orders.import.fulfill_fee') }}" method="POST" enctype="multipart/form-data"
                        id="fulfillFeeForm">
                        @csrf
                        <div class="mb-3">
                            <label for="fulfill_fee_file" class="form-label">Select File (.xlsx/.xls)</label>
                            <input type="file" name="file" accept=".xlsx,.xls" class="form-control" required>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-upload me-1"></i> Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    @push('scripts')
        <script src="/js/order.js"></script>
        <script>
            document.getElementById('fulfillFeeForm')?.addEventListener('submit', function() {
                document.getElementById('importSpinner').style.display = 'block';
            });
        </script>
    @endpush
@endsection
