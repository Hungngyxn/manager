@extends('layouts.admin', ['active' => 'sku'])

@section('_content')
    <div class="container-fluid mt-2 px-4">
        <div class="row">
            <div class="col-12">
                <h4 class="font-weight-bold">SKU Management</h4>
                <hr>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="row">
            <div class="col-12 mb-3">
                <div class="card bg-light shadow-sm p-4">

                    {{-- Toolbar --}}
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            @if (Auth::user()->role->name !== 'Seller')
                                {{-- Add --}}
                                <div class="btn-group">
                                    <button class="btn btn-outline-dark dropdown-toggle px-4 py-2" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-plus me-2"></i> Add SKU
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('sku.create') }}">
                                                <i class="fas fa-keyboard me-1"></i> Add Manual
                                            </a>
                                        </li>
                                        <li>
                                            <form action="{{ route('sku.import') }}" method="POST"
                                                enctype="multipart/form-data" class="dropdown-item p-0 m-0 border-0">
                                                @csrf
                                                <label class="dropdown-item d-block" style="cursor: pointer">
                                                    <i class="fas fa-file-excel me-1"></i> Import Excel
                                                    <input type="file" name="file" accept=".xlsx,.xls"
                                                        onchange="this.form.submit()" style="display: none;">
                                                </label>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            @endif
                        </div>

                        {{-- Search --}}
                        <form method="GET" action="{{ route('sku.index') }}" id="filterForm"
                            class="d-flex align-items-center gap-2">
                            <select name="tier" class="form-select" style="min-width: 140px">
                                <option value="">-- Tất cả Tier --</option>
                                @foreach ($tiers as $tier)
                                    <option value="{{ $tier->tier }}"
                                        {{ request('tier') == $tier->tier ? 'selected' : '' }}>
                                        {{ $tier->tier }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="text" name="search" class="form-control px-3 py-2" placeholder="Search SKU..."
                                value="{{ request('search') }}" style="min-width: 250px;">
                            <button type="submit" class="btn btn-outline-secondary px-4" id="btnsearch">
                                <i class="fas fa-search"></i>
                            </button>
                            <button type="button" class="btn btn-danger" onclick="resetFilters()">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>

                    {{-- Alerts --}}
                    @if (session('status'))
                        <div class="alert alert-info">
                            {!! session('status') !!}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center align-middle">
                            <thead class="table-light text-uppercase">
                                <tr>
                                    <th>SKU</th>
                                    <th>Mặt Hàng</th>
                                    <th>Base Cost</th>
                                    <th>Quantity</th>
                                    <th>Tier</th>
                                    @if (Auth::user()->role->name !== 'Seller')
                                        <th>Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($skus as $sku)
                                    <tr>
                                        <td>{{ $sku->sku }}</td>
                                        <td>{{ $sku->name }}</td>
                                        <td>{{ number_format($sku->cost, 1) }}</td>
                                        <td>{{ number_format($sku->quantity) }}</td>
                                        <td>{{ $sku->tier }} </td>
                                        @if (collect($accesses)->where('menu_id', 7)->first()->status == 2)
                                            <td>
                                                <a href="{{ route('sku.edit', $sku->id) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('sku.destroy', $sku->id) }}" method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this SKU?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No SKUs found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- PerPage + Pagination --}}
                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                        <form method="GET" action="{{ route('sku.index') }}" class="d-flex align-items-center">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <select name="perPage" class="form-select" onchange="this.form.submit()">
                                @foreach ([10, 20, 50, 100] as $size)
                                    <option value="{{ $size }}"
                                        {{ request('perPage', 10) == $size ? 'selected' : '' }}>
                                        {{ $size }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        <div>
                            {{ $skus->appends(request()->only(['search', 'perPage']))->links() }}
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @if (session('import_error') || session('import_status'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @if (session('import_error'))
                    const error = @json(session('import_error'));

                    const modalTitle = 'Lỗi khi import SKU';
                    const modalBody = `
                    <p><strong>Lỗi:</strong> ${error.message}</p>
                    <p><strong>File:</strong> ${error.file}</p>
                    <p><strong>Dòng:</strong> ${error.line}</p>
                `;
                @elseif (session('import_status'))
                    const modalTitle = 'Thông báo Import SKU';
                    let rawHtml = `{!! session('import_status') !!}`;
                    let modalBody = rawHtml.replace(/⚠️ Bỏ qua:/g,
                        '<span style="color:#ff0404; font-weight:600;">⚠️ Bỏ qua:</span>');
                @endif

                const modalHtml = `
                <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg"> <!-- modal rộng hơn -->
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="importModalLabel">${modalTitle}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body">${modalBody}</div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

                document.body.insertAdjacentHTML('beforeend', modalHtml);

                var importModal = new bootstrap.Modal(document.getElementById('importModal'));
                importModal.show();
            });
        </script>
    @endif


    <script>
        function resetFilters() {
            const form = document.getElementById('filterForm');
            if (form.querySelector('input[name="search"]')) {
                form.querySelector('input[name="search"]').value = '';
            }
            document.getElementById('btnsearch').click();
        }
    </script>
@endsection
