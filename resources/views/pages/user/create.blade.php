@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'user'])

@section('_content')
    <div class="container-fluid mt-4 px-4">
        <div class="card shadow-sm bg-light p-4 rounded">
            <h4 class="mb-4">Create New User</h4>

            {{-- Hiển thị lỗi --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>There were some problems with your input.</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Form --}}
            <form action="{{ route('user.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label fw-bold">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}" placeholder="Enter user name">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-bold">Username</label>
                    <input name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}" placeholder="Enter username">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-bold">Password</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                        placeholder="Enter password">
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label fw-bold">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control"
                        placeholder="Re-enter password">
                </div>

                <div class="mb-3">
                    <label for="role_id" class="form-label fw-bold">Role</label>
                    <select name="role_id" class="form-select @error('role_id') is-invalid @enderror">
                        <option value="">-- Select Role --</option>
                        @foreach($roles as $role)
                            @if(Auth::user()->role->name === 'Administrator' || $role->name !== 'Administrator')
                                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="team_id" class="form-label fw-bold">Team</label>
                    <select name="team_id" class="form-select @error('team_id') is-invalid @enderror">
                        <option value="">-- Select Team --</option>
                        @foreach($teams as $team)
                                <option value="{{ $team->id }}" {{ old('team_id') == $team->id ? 'selected' : '' }}>
                                    {{ $team->name }}
                                </option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-4">
                    <a href="{{ route('user.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Create</button>
                </div>
            </form>
        </div>
    </div>
@endsection