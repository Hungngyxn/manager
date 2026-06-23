@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'account'])

@section('_content')
    <div class="container-fluid mt-2 px-4">
        <div class="row">
            <div class="col-12">
                <h4 class="font-weight-bold">User Profile</h4>
                <hr>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-12 mb-3">
                <div class="card shadow-sm bg-light p-4">
                    <form action="{{ route('profile.update', ['user' => auth()->id()]) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Name --}}
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">Name</label>
                            <input type="text" id="name" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $profile->name) }}"
                                {{ $profile->role->name !== 'Administrator' ? 'readonly' : '' }}>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">Username</label>
                            <input id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $profile->email) }}"
                                {{ $profile->role->name !== 'Administrator' ? 'readonly' : '' }}>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password (Hiển thị dạng text bình thường) --}}
                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold">Password</label>
                            <input type="text" id="password" name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                value="{{ old('password', '') }}">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Role --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Role</label>
                            <input type="text" class="form-control-plaintext" readonly
                                value="{{ $profile->role->name }}">
                        </div>

                        {{-- Team --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Role</label>
                            <input type="text" class="form-control-plaintext" readonly
                                value="{{ $profile->team->name ?? 'No Team' }}">
                        </div>

                        {{-- Nút Save --}}
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-save me-1"></i> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
