@extends('layouts.app')

@section('content')
        <div class="container min-vh-100 d-flex flex-column justify-content-center align-items-center position-relative">
            <div class="row w-100 justify-content-center">
                <div class="col-md-8">
                    <div class="card" style="background: rgba(255, 255, 255, 0.42); backdrop-filter: blur(4px);">
                        <div class="card-header">{{ __('Login') }}</div>

                        <div class="card-body">
                            <form method="POST" action=""{{ request()->isSecure() ? secure_url('login') : route('login') }}">
                                @csrf

                                <div class="form-group row">
                                    <label for="email" class="col-md-4 col-form-label text-md-right">Username</label>
                                    <div class="col-md-6">
                                        <input id="email" class="form-control @error('email') is-invalid @enderror"
                                               name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                                        @error('email')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row mt-3">
                                    <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Password') }}</label>
                                    <div class="col-md-6">
                                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                                               name="password" required autocomplete="current-password">
                                        @error('password')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-group row mt-4 mb-0">
                                    <div class="col-12 d-flex justify-content-center">
                                        <button type="submit" class="btn btn-primary px-5">
                                            {{ __('Login') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Footer năm --}}
            <div class="text-black position-absolute bottom-0 mb-5 w-100 text-center " style="font-size: 16px;">
                &copy; 2025
            </div>
        </div>
        <style>
        html, body {
            overflow: hidden; /* Ngăn cuộn */
        }

        body {
            background: url('{{ asset('images/bg.jpg') }}') no-repeat center center fixed;
            background-size: cover;
        }
    </style>
@endsection
