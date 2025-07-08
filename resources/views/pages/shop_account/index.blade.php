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
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

                        {{-- Nhóm nút thao tác bên trái --}}
                        <div class="d-flex flex-wrap gap-2">

                            {{-- Add --}}
                            <a href="{{ route('shop-accounts.create') }}" class="btn btn-success d-flex align-items-center">
                                <i class="fas fa-plus me-2"></i> Add Shop
                            </a>

                            {{-- Import --}}
                            <button class="btn btn-outline-primary d-flex align-items-center" type="button"
                                data-bs-toggle="modal" data-bs-target="#importShopModal">
                                <i class="fas fa-file-import me-2"></i> Import
                            </button>

                            {{-- Assign --}}
                            <form id="batchAssignForm" action="{{ route('shop-accounts.batch-assign') }}" method="POST"
                                class="d-flex gap-2 align-items-center">
                                @csrf
                                <select name="user_id" class="form-select select2 w-auto" required>
                                    <option value="">-- Select Seller --</option>
                                    @foreach ($allUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline-dark d-flex align-items-center">
                                    <i class="fas fa-user-tag me-2"></i> Assign
                                </button>
                            </form>
                        </div>

                        {{-- Search bên phải --}}
                        <form method="GET" action="{{ route('shop-accounts.index') }}"
                            class="d-flex gap-2 align-items-center">
                            <input type="text" name="search" value="{{ request('search') }}"
                                class="form-control form-control" placeholder="Search email or user...">
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

                    {{-- Bảng dữ liệu --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center align-middle">
                            <thead class="table-light text-uppercase">
                                <tr>
                                    <th><input type="checkbox" id="selectAllShops"></th>
                                    <th>ID</th>
                                    <th>Email</th>
                                    <th>Reg Month</th>
                                    <th>Age</th>
                                    <th>User</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($accounts as $index => $acc)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="shop_ids[]" value="{{ $acc->id }}"
                                                form="batchAssignForm">
                                        </td>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $acc->email }}</td>
                                        <td>{{ $acc->thang_reg }}</td>
                                        <td>{{ $acc->tuoi_acc }}</td>
                                        <td>{{ $acc->user->name ?? '—' }}</td>
                                        <td>{{ ucfirst($acc->status) }}</td>
                                        <td>
                                            <a href="{{ route('shop-accounts.edit', $acc->id) }}"
                                                class="btn btn-sm btn-primary">Edit</a>
                                            <form action="{{ route('shop-accounts.destroy', $acc->id) }}" method="POST"
                                                style="display:inline;">
                                                @csrf @method('DELETE')
                                                <button onclick="return confirm('Delete this record?')"
                                                    class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{ $accounts->links() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Import Shop Account -->
        <div class="modal fade" id="importShopModal" tabindex="-1" aria-labelledby="importShopModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="importShopModalLabel">Import Shop Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4">
                        <form action="{{ route('shop-accounts.import') }}" method="POST" enctype="multipart/form-data"
                            id="shopImportForm">
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
            document.addEventListener('DOMContentLoaded', function() {
                const checkboxAll = document.getElementById('selectAllShops');
                if (checkboxAll) {
                    checkboxAll.addEventListener('change', function() {
                        document.querySelectorAll('input[name="shop_ids[]"]').forEach(cb => cb.checked = this
                            .checked);
                    });
                }
            });
        </script>
    @endpush
@endsection
