@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'user'])

@section('_content')
    <div class="container-fluid mt-2 px-4">
        <div class="row">
            <div class="col-12">
                <h4 class="font-weight-bold">Users</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-3">
                <div class="bg-light text-dark card p-3 overflow-auto">

                    {{-- Bộ lọc và nút tạo mới --}}
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        @if (Auth::user()->role->name != 'Seller')
                            <a href="{{ route('user.create') }}" class="btn btn-outline-dark d-flex align-items-center">
                                <i class="fas fa-plus me-1"></i> <span>Create</span>
                            </a>
                        @endif

                        <form method="GET" action="{{ route('user.index') }}" id="filterForm"
                            class="d-flex align-items-center gap-2 ms-auto">

                            <select name="team" class="form-select select2">
                                <option value="">-- All Team --</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}"
                                        {{ request('team') == $team->id ? 'selected' : '' }}>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>

                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                placeholder="Search ..." style="width: 200px;">

                            <button type="submit" class="btn btn-outline-secondary" id="btnsearch">
                                <i class="fas fa-search"></i>
                            </button>
                            <button type="button" class="btn btn-danger" onclick="resetFilters()">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>

                    {{-- Thông báo --}}
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{-- Bảng dữ liệu --}}
                    <table class="table table-light table-striped table-hover table-bordered text-center">
                        <thead>
                            <tr>
                                <th class="table-dark">#</th>
                                <th class="table-dark">Name</th>
                                <th class="table-dark">Username</th>
                                <th class="table-dark">Role</th>
                                <th class="table-dark">Team</th>
                                <th class="table-dark">Status</th>
                                <th class="table-dark">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <th scope="row">{{ $loop->iteration + $users->firstItem() - 1 }}</th>
                                    <td class="w-25">
                                        @if ($user->name !== 'Administrator' && Auth::user()->name === 'Administrator')
                                            <a href="{{ route('user.show', $user->id) }}">{{ $user->name }}</a>
                                        @else
                                            {{ $user->name }}
                                        @endif
                                    </td>
                                    <td class="w-25">{{ $user->email }}</td>
                                    <td>{{ $user->role->name }}</td>
                                    <td class="w-10">
                                        <form method="POST" action="{{ route('user.updateTeam', $user->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <select name="team_id" class="form-select" onchange="this.form.submit()">
                                                <option value=""> None </option>
                                                @foreach ($teams as $team)
                                                    <option value="{{ $team->id }}" {{ $user->team_id == $team->id ? 'selected' : '' }}>
                                                        {{ $team->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        @if ($user->status)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($user->name !== 'Administrator' && Auth::user()->role->name === 'Administrator')
                                            <a href="{{ route('user.edit', $user->id) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            {{-- Xóa --}}
                                            <form action="{{ route('user.destroy', $user->id) }}"
                                                method="POST" style="display: inline-block;"
                                                onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>

                                            {{-- Kích hoạt / Vô hiệu hóa --}}
                                            @if ($user->status)
                                                <form action="{{ route('user.deactivate', $user->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('user.activate', $user->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- Phân trang --}}
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('.select2').select2({
                width: '100%',
                theme: 'bootstrap-5',
            });
        });

        function resetFilters() {
            const form = document.getElementById('filterForm');
            const teamSelect = form.querySelector('select[name="team"]');
            if (teamSelect) $(teamSelect).val('').trigger('change');
            form.querySelector('input[name="search"]').value = '';
            document.getElementById('btnsearch').click();
        }
    </script>
@endsection
