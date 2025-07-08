@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'order'])

@section('_content')
    <div class="container mt-4">
        <h3>Create New Order</h3>
        <form action="{{ route('orders.store') }}" method="POST" class="card p-4 shadow-sm">
            @csrf

            <div class="mb-3">
                <label for="order_id" class="form-label fw-bold">ORDER ID</label>
                <input type="text" name="order_id" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="extra_id" class="form-label fw-bold">SKU ID</label>
                <input type="text" name="extra_id" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="sku" class="form-label fw-bold">SKU</label>
                <select name="sku" id="sku" class="form-select select2" required>
                    <option value="">-- Select SKU --</option>
                    @foreach ($skus as $sku)
                        <option value="{{ $sku->sku }}" data-cost="{{ $sku->cost }}">
                            {{ $sku->sku }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Shop Name</label>
                    <select id="shop_name" name="shop_name" class="form-select select2" required>
                        <option value="">-- Select Shop --</option>
                        @foreach ($shops as $shop)
                            <option value="{{ $shop->shop_name }}" data-code="{{ $shop->shop_code }}"
                                data-user="{{ $shop->user_id }}">
                                {{ $shop->shop_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Shop Code (auto-filled)</label>
                    <input type="text" id="shop_code" class="form-control" readonly placeholder="Select Shop Name">
                </div>

                <div class="col-md-6">
                    <input type="hidden" id="seller" class="form-control" name="user_id" readonly>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Quantity</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1"
                        required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Calculated Cost</label>
                    <input type="text" id="calculatedCost" class="form-control" readonly>
                    <input type="hidden" name="cost" id="cost">
                </div>
            </div>


            <div class="mb-3">
                <label for="total" class="form-label fw-bold">Total</label>
                <input type="number" name="total" step="0.01" class="form-control" required>
            </div>

            <div class="d-flex justify-content-end">
                <button class="btn btn-primary">Save</button>
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    </div>

    {{-- Script --}}
    @push('scripts')
        <script src="{{ asset('js/order.js') }}"></script>
    @endpush
@endsection
