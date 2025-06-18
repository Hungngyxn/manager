@extends('layouts.app')

@section('title', 'Database Connection Error')

@section('content')
<div class="container text-center mt-5">
    <h1 class="display-1 text-danger">500</h1>
    <h3 class="mb-3">Database Connection Failed</h3>
    <p>Our server is currently experiencing issues connecting to the database. Please try again later.</p>
    <a href="{{ url()->previous() }}" class="btn btn-warning mt-3">Go Back</a>
</div>
@endsection
