@extends('layouts.app')

@section('title', 'Page Not Found')

@section('content')
<div class="container text-center mt-5">
    <h1 class="display-1">404</h1>
    <h3 class="mb-3">Oops! Page not found.</h3>
    <p>The page you’re looking for doesn’t exist or the server cannot be reached.</p>
    <a href="{{ url('/') }}" class="btn btn-primary mt-3">Go to Homepage</a>
</div>
@endsection