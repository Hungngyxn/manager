@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'sku'])

@section('_content')
    <div class="container-fluid mt-2 px-4">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="font-weight-bold">SKU Order Management</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-3">
                <div class="card bg-light text-dark p-4 shadow-sm rounded">

                    {{-- Toolbar --}}
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

                        {{-- Left --}}
                        <div class="d-flex flex-wrap gap-2">
                            {{-- Add --}}
                            <a href="{{ route('sku-orders.create') }}" class="btn btn-success d-flex align-items-center">
                                <i class="fas fa-plus me-2"></i> Add SKU
                            </a>

                            {{-- Import --}}
                            <button class="btn btn-outline-primary d-flex align-items-center" type="button"
                                data-bs-toggle="modal" data-bs-target="#importSkuOrderModal">
                                <i class="fas fa-file-import me-2"></i> Import
                            </button>
                        </div>

                        {{-- Search --}}
                        <form method="GET" action="{{ route('sku-orders.index') }}" class="d-flex gap-2 align-items-center">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                placeholder="Search SKU or ASIN...">
                            <button type="submit" class="btn btn-outline-secondary d-flex align-items-center">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                        </form>
                    </div>

                    {{-- Thông báo --}}
                    @if (session('skipped'))
                        <div class="alert alert-warning mt-2">
                            <strong>Skipped rows:</strong>
                            <ul>
                                @foreach (session('skipped') as $msg)
                                    <li>{{ $msg }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center align-middle">
                            <thead class="table-light text-uppercase">
                                <tr>
                                    <th>ID</th>
                                    <th>SKU</th>
                                    <th>ASIN</th>
                                    <th>Product Name</th>
                                    <th>Warehouse Name</th>
                                    <th>Quantity/Pack</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($skuOrders as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->sku }}</td>
                                        <td>{{ $item->asin }}</td>
                                        <td>{{ $item->product_name }}</td>
                                        <td>{{ $item->warehouse_name }}</td>
                                        <td>{{ $item->quantity_per_pack }}</td>
                                        <td>
                                            <a href="{{ route('sku-orders.edit', $item->id) }}"
                                                class="btn btn-sm btn-primary">Edit</a>
                                            <form action="{{ route('sku-orders.destroy', $item->id) }}" method="POST"
                                                style="display:inline;">
                                                @csrf @method('DELETE')
                                                <button onclick="return confirm('Delete this SKU?')"
                                                    class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{ $skuOrders->appends(request()->only(['search']))->links() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Import --}}
        <div class="modal fade" id="importSkuOrderModal" tabindex="-1" aria-labelledby="importSkuOrderModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="importSkuOrderModalLabel">Import SKU Orders</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4">
                        <form action="{{ route('sku-orders.import') }}" method="POST" enctype="multipart/form-data"
                            id="skuOrderImportForm">
                            @csrf
                            <div class="mb-3">
                                <label for="import_file" class="form-label">Select Excel File (.xlsx/.xls)</label>
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

    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('.select2').select2({
                    dropdownAutoWidth: true,
                    width: '100%',
                    theme: 'bootstrap-5',
                    closeOnSelect: true
                });
            });
        </script>
    @endpush
@endsection
