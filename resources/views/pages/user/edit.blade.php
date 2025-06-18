@extends('layouts.admin', ['active' => 'user'])

@section('_content')
    <div class="container mt-4">
        <h3>Edit User</h3>
        <form action="{{ route('user.update', $user->id) }}" method="POST" class="card p-4">
            @csrf
            @method('PUT')

            {{-- Name --}}
            <div class="form-group mb-3">
                <label for="name" class="fw-bold">Name</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $user->name) }}">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- user_name --}}
            <div class="form-group mb-3">
                <label for="user_name" class="fw-bold">User Name</label>
                <input name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email', $user->email) }}">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Password --}}
            <div class="form-group mb-3">
                <label for="password" class="fw-bold">Password</label>
                <input type="password" name="password" id="password"
                    class="form-control @error('password') is-invalid @enderror" value="{{ old('password', '') }}">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Role --}}
            <div class="form-group mb-4">
                <label for="role_id" class="fw-bold">Role</label>
                <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
                    <option value="">-- Select Role --</option>
                    @foreach ($roles as $role)
                        @if(Auth::user()->role->name === 'Administrator' || $role->name !== 'Administrator')
                            <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endif
                    @endforeach
                </select>
                @error('role_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Buttons --}}
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary me-2 px-5">
                    <i class="fas fa-save me-1"></i> Save
                </button>
                <a href="{{ route('user.index') }}" class="btn btn-secondary px-5">Cancel</a>
            </div>
        </form>
    </div>
@endsection