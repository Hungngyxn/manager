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
                        @canEdit
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
                                            <i class="fas fa-download me-2 text-success"></i> Download sample file
                                        </a>
                                    </li>
                                    <li>
                                        <form action="{{ route('shop.import') }}" method="POST"
                                            enctype="multipart/form-data" id="importForm">
                                            @csrf
                                            <label class="dropdown-item mb-0" style="cursor: pointer;">
                                                <i class="fas fa-upload me-2 text-primary"></i> Choose file to import
                                                <input type="file" name="file" accept=".xlsx,.xls"
                                                    onchange="handleImport(this)" hidden>
                                            </label>
                                        </form>
                                    </li>
                                </ul>
                            </div>

                            <button type="button" class="btn btn-outline-primary px-3 py-2" data-bs-toggle="modal"
                                data-bs-target="#connectTikTokModal" hidden>
                                <i class="fab fa-tiktok me-2"></i> Connect TikTok Shop
                            </button>
                        </div>
                        @endcanEdit

                        {{-- Filter Form --}}
                        <form method="GET" action="{{ route('shop.index') }}" id="filterForm"
                            class="d-flex align-items-center gap-2 ms-auto">
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

                            <select name="team_id" class="form-select px-4 py-2 select2 ms-2">
                                <option value="">-- All Teams --</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}"
                                        {{ request('team_id') == $team->id ? 'selected' : '' }}>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>

                            <select id="bankStatus" name="filter_pending_nullbank" class="select2-bank"
                                style="padding:5px 10px; border-radius:6px;">
                                <option value="0" {{ request('filter_pending_nullbank') == '0' ? 'selected' : '' }}>
                                    All Shops</option>
                                <option value="1" {{ request('filter_pending_nullbank') == '1' ? 'selected' : '' }}>No
                                    Bank Linked</option>
                            </select>

                            <input type="text" name="search" value="{{ request('search') }}"
                                class="form-control px-4 py-2 col-4" placeholder="Search ..." style="min-width: 250px;">
                            <button type="submit" class="btn btn-outline-secondary px-2" id="btnsearch">
                                <i class="fas fa-search"></i></button>
                        </form>
                        <form method="GET" action="{{ route('shop.index') }}" class="ms-4">
                            <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i></button>
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
                        <p class="mt-2">Processing Excel file, please wait...</p>
                    </div>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center align-middle">
                            <thead class="table-light text-uppercase">
                                <tr>
                                    <th>#</th>
                                    <th>Shop Name/ Shop Code</th>
                                    <th>Team</th>
                                    <th>Email</th>
                                    <th>Bank</th>
                                    <th>Pending</th>
                                    <th>On Hold</th>
                                    <th>Payout</th>
                                    <th>Limit Order</th>
                                    <th>Seller</th>
                                    @canEdit
                                    <th>Actions</th>
                                    @endcanEdit
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($shops as $shop)
                                    <tr>
                                        <td>{{ $loop->iteration + $shops->firstItem() - 1 }}</td>
                                        <td>{{ $shop->shop_name }} / {{ $shop->shop_code }}</td>
                                        <td>{{ optional($shop->team)->name ?? 'Unassigned' }}</td>
                                        <td>{{ $shop->email }}</td>
                                        <td>{{ $shop->bank }}</td>
                                        <td>{{ $shop->pending }}</td>
                                        <td>{{ $shop->onhold }}</td>
                                        <td>{{ $shop->payout }}</td>
                                        <td>{{ $shop->limit_order }}</td>
                                        <td>{{ optional($shop->seller)->name ?? 'Unassigned' }}</td>
                                        @canEdit
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="openEditModal({{ $shop->id }}, '{{ addslashes($shop->shop_name) }}', '{{ $shop->shop_code }}', '{{ $shop->email }}', '{{ $shop->user_id }}', '{{ $shop->team_id }}', '{{ $shop->on_hold }}', '{{ $shop->payout }}')">
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
                                                onclick="return confirm('Are you sure you want to reconnect this TikTok Shop?')"
                                                hidden>
                                                <i class="fa-solid fa-repeat me-1"></i>
                                            </a>
                                        </td>
                                        @endcanEdit
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">No shops found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="5" class="text-end"></td>
                                    <td>{{ number_format($totals['pending'], 2) }}</td>
                                    <td>{{ number_format($totals['onhold'], 2) }}</td>
                                    <td>{{ number_format($totals['payout'], 2) }}</td>
                                    <td colspan="1"></td>
                                    <td colspan="100%" class="text-end">
                                        Total shops: {{ $totalShops }}
                                    </td>
                                </tr>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                        <form method="GET" action="{{ route('shop.index') }}" class="d-flex align-items-center">
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
                            {{ $shops->appends(request()->only(['search', 'perPage', 'user_id', 'team_id']))->links() }}
                        </div>
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
                        <h5 class="modal-title" id="connectTikTokLabel">Connect TikTok Shop</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                        <button type="submit" class="btn btn-primary">Connect Now</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal: Edit Shop --}}
        <div class="modal fade" id="editShopModal" tabindex="-1" aria-labelledby="editShopLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" id="editShopForm" class="modal-content">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" id="editShopId">

                    <div class="modal-header">
                        <h5 class="modal-title" id="editShopLabel">Edit Shop</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="fw-bold">Shop Name</label>
                            <input type="text" name="shop_name" id="editShopName" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="fw-bold">Shop Code</label>
                            <input type="text" name="shop_code" id="editShopCode" class="form-control">
                        </div>
                        <div class="form-group mb-3">
                            <label class="fw-bold">Email</label>
                            <input type="email" name="email" id="editEmail" class="form-control">
                        </div>
                        <div class="form-group mb-3">
                            <label class="fw-bold">Seller</label>
                            @if (count($sellers))
                                <select name="user_id" id="editSellerId" class="form-select select2-edit" required>
                                    <option value="">-- Select Seller --</option>
                                    @foreach ($sellers as $seller)
                                        <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="form-group mb-3">
                            <label class="fw-bold">Team</label>
                            <select name="team_id" id="editTeamId" class="form-select select2-edit">
                                <option value="">-- Select Team --</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </select>
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
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="/js/shop.js"></script>
        <script>
            $(document).ready(function() {
                $(".select2-bank").select2({
                    dropdownAutoWidth: true,
                    width: "100%",
                    theme: "bootstrap-5",
                    closeOnSelect: true,
                    minimumResultsForSearch: Infinity,
                });
            });
        </script>
    @endpush
@endsection
