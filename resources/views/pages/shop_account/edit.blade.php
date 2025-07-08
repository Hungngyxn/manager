@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'shop'])

@section('_content')
    <div class="container mt-4">
        <h4>Edit Shop Account</h4>
        <form method="POST" action="{{ route('shop-accounts.update', $account->id) }}">
            @csrf
            @method('PUT')
            @include('pages.shop_account.form', ['account' => $account])
        </form>
    </div>
@endsection
