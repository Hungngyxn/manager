@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'shops'])

@section('_content')
    <div class="container-fluid mt-2 px-4">
        <div class="row">
            <div class="col-12">
                <h4 class="font-weight-bold">Add New Shop</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm p-4">
                    <form method="POST" action="{{ route('shop.store') }}" id="createShopForm">
                        @csrf
                        {{-- Seller select --}}
                        <div class="mb-3">
                            <label for="seller_id" class="form-label fw-bold">Select Seller</label>
                            @if (auth()->user()->role->name === 'Seller')
                                <input type="hidden" name="seller_id" value="{{ auth()->user()->id }}">
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly
                                    disabled>
                            @else
                                <select name="seller_id" id="seller_id" class="form-select select2" required>
                                    <option value="">-- Select Seller --</option>
                                    @foreach ($sellers as $seller)
                                        <option value="{{ $seller->id }}">{{ $seller->name }} ({{ $seller->email }})
                                        </option>
                                    @endforeach
                                </select>
                            @endif

                        </div>

                        {{-- Shop Name --}}
                        <div class="mb-3">
                            <label for="shop_name" class="form-label fw-bold">Shop Name</label>
                            <input type="text" id="shop_name" name="shop_name" class="form-control" required>
                        </div>

                        {{-- Shop Code --}}
                        <div class="mb-3">
                            <label for="shop_code" class="form-label fw-bold">Shop Code</label>
                            <input type="text" id="shop_code" name="shop_code" class="form-control" required>
                        </div>

                        {{-- Submit --}}
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Shop
                            </button>
                            <a href="{{ route('shop.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Script --}}
    @push('scripts')
        <script src="{{ asset('js/order.js') }}"></script>
    @endpush
@endsection
