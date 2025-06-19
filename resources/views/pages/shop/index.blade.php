@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'shop'])

@section('_content')
    <div class="container-fluid mt-2 px-4">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="font-weight-bold">Shop Management</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-3">
                <div class="card bg-light text-dark p-4 shadow-sm rounded">

                    {{-- Toolbar --}}
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('shop.create') }}" class="btn btn-outline-dark px-3 py-2">
                                <i class="fas fa-plus me-2"></i> Add New Shop
                            </a>

                            {{-- Dropdown Import --}}
                            <div class="dropdown">
                                <button class="btn btn-outline-dark px-3 py-2" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-file-excel me-2"></i> Import Excel
                                    <i class="fas fa-caret-down ms-1"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('shop.download-sample') }}">
                                            <i class="fas fa-download me-2 text-success"></i> Tải file mẫu
                                        </a>
                                    </li>
                                    <li>
                                        <form action="{{ route('shop.import') }}" method="POST"
                                            enctype="multipart/form-data" id="importForm">
                                            @csrf
                                            <label class="dropdown-item mb-0" style="cursor: pointer;">
                                                <i class="fas fa-upload me-2 text-primary"></i> Chọn file để import
                                                <input type="file" name="file" accept=".xlsx,.xls"
                                                    onchange="handleImport(this)" hidden>
                                            </label>
                                        </form>
                                    </li>
                                </ul>
                            </div>

                            <button type="button" class="btn btn-outline-primary px-3 py-2" data-bs-toggle="modal"
                                data-bs-target="#connectTikTokModal" hidden>
                                <i class="fab fa-tiktok me-2"></i> Kết nối TikTok Shop
                            </button>
                        </div>

                        {{-- Filter Form --}}
                        <form method="GET" action="{{ route('shop.index') }}" id="filterForm"
                            class="d-flex align-items-center gap-2">
                            @if (count($sellers))
                                <select name="user_id" class="form-select px-4 py-2 select2">
                                    <option value="">-- All Sellers --</option>
                                    <option value="null" {{ request('user_id') === 'null' ? 'selected' : '' }}>Unassigned
                                        Shops</option>
                                    @foreach ($sellers as $seller)
                                        <option value="{{ $seller->id }}"
                                            {{ request('user_id') == $seller->id ? 'selected' : '' }}>
                                            {{ $seller->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif

                            <input type="text" name="search" value="{{ request('search') }}"
                                class="form-control px-4 py-2" placeholder="Search ..." style="min-width: 250px;">
                            <button type="submit" class="btn btn-outline-secondary px-4" id="btnsearch">
                                <i class="fas fa-search"></i></button>
                            <button type="button" class="btn btn-danger" onclick="resetFilters()"><i
                                    class="fas fa-times"></i></button>
                        </form>
                    </div>

                    {{-- Alerts --}}
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    <div id="importSpinner" class="text-center my-3" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Đang xử lý file Excel, vui lòng chờ...</p>
                    </div>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center align-middle">
                            <thead class="table-light text-uppercase">
                                <tr>
                                    <th>#</th>
                                    <th>Shop Name</th>
                                    <th>Shop Code</th>
                                    <th>Bank</th>
                                    <th>On Hold</th>
                                    <th>Payout</th>
                                    <th>Seller</th>
                                    @if (Auth::user()->role->name !== 'Seller')
                                        <th>Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($shops as $shop)
                                    <tr>
                                        <td>{{ $loop->iteration + $shops->firstItem() - 1 }}</td>
                                        <td>{{ $shop->shop_name }}</td>
                                        <td>{{ $shop->shop_code }}</td>
                                        <td>{{ $shop->bank }}</td>
                                        <td>{{ $shop->on_hold }}</td>
                                        <td>{{ $shop->payout }}</td>
                                        <td>{{ optional($shop->seller)->name ?? 'Unassigned' }}</td>
                                        @if (Auth::user()->role->name !== 'Seller')
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="openEditModal({{ $shop->id }}, '{{ addslashes($shop->shop_name) }}', '{{ $shop->shop_code }}', '{{ $shop->user_id }}', '{{ $shop->on_hold }}', '{{ $shop->payout }}')">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <form action="{{ route('shop.destroy', $shop->id) }}" method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this shop?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i
                                                            class="fas fa-trash"></i></button>
                                                </form>

                                                <a href="{{ route('tiktok.reconnect', $shop->id) }}"
                                                    class="btn btn-sm btn-outline-dark"
                                                    onclick="return confirm('Bạn có chắc chắn muốn kết nối lại TikTok Shop này?')">
                                                    <i class="fa-solid fa-repeat me-1"></i>
                                                </a>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No shops found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="100%" class="text-end fw-bold">
                                        Tổng số shop: {{ $totalShops }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="d-flex justify-content-start">
                        {{ $shops->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal: Connect TikTok --}}
        <div class="modal fade" id="connectTikTokModal" tabindex="-1" aria-labelledby="connectTikTokLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <form method="GET" action="{{ route('tiktok.connect') }}" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="connectTikTokLabel">Kết nối TikTok Shop</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Shop Name</label>
                            <input type="text" name="shop_name" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="fw-bold">Shop Code</label>
                            <input type="text" name="shop_code" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Kết nối ngay</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal: Edit Shop --}}
        <div class="modal fade" id="editShopModal" tabindex="-1" aria-labelledby="editShopLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form id="editShopForm" method="POST" class="modal-content">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" id="editShopId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editShopLabel">Chỉnh sửa Shop</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Tên Shop</label>
                            <input type="text" name="shop_name" id="editShopName" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="fw-bold">Mã Shop</label>
                            <input type="text" name="shop_code" id="editShopCode" class="form-control">
                        </div>
                        <div class="form-group mb-3">
                            <label class="fw-bold">Seller</label>
                            @if (count($sellers))
                                <select name="user_id" id="editSellerId" class="form-select select2" required>
                                    <option value="">-- Chọn Seller --</option>
                                    @foreach ($sellers as $seller)
                                        <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="form-group mb-3">
                            <label class="fw-bold">On Hold</label>
                            <input type="number" step="0.01" name="on_hold" id="editOnHold" class="form-control">
                        </div>
                        <div class="form-group mb-3">
                            <label class="fw-bold">Payout</label>
                            <input type="number" step="0.01" name="payout" id="editPayout" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Lưu</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="/js/shop.js"></script>
    @endpush
@endsection
