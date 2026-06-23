@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'shop'])

@section('_content')
    <div class="container mt-4">
        <h4>Add Shop Account</h4>
        <form method="POST" action="{{ route('shop-accounts.store') }}">
            @csrf
            @include('pages.shop_account.form')
        </form>
    </div>
@endsection
