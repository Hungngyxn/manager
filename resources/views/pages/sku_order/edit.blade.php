@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'sku'])

@section('_content')
    <div class="container-fluid mt-3 px-4">

        <div class="row mb-3">
            <div class="col-12">
                <h4 class="font-weight-bold">Edit SKU Order</h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 col-md-8 col-12 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-body p-4">

                        <form action="{{ route('sku-orders.update', $skuOrder->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            {{-- SKU --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold">SKU</label>
                                <input 
                                    type="text" 
                                    name="sku" 
                                    class="form-control"
                                    value="{{ old('sku', $skuOrder->sku) }}"
                                    required
                                >
                            </div>

                            {{-- ASIN --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold">ASIN</label>
                                <input 
                                    type="text" 
                                    name="asin" 
                                    class="form-control"
                                    value="{{ old('asin', $skuOrder->asin) }}"
                                    required
                                >
                            </div>

                            {{-- Product name --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold">Product Name</label>
                                <input 
                                    type="text" 
                                    name="product_name" 
                                    class="form-control"
                                    value="{{ old('product_name', $skuOrder->product_name) }}"
                                >
                            </div>

                            {{-- Warehouse --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold">Warehouse Name</label>
                                <input 
                                    type="text" 
                                    name="warehouse_name" 
                                    class="form-control"
                                    value="{{ old('warehouse_name', $skuOrder->warehouse_name) }}"
                                >
                            </div>

                            {{-- Quantity per pack --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold">Quantity / Pack</label>
                                <input 
                                    type="number" 
                                    name="quantity_per_pack" 
                                    class="form-control"
                                    value="{{ old('quantity_per_pack', $skuOrder->quantity_per_pack) }}"
                                    required
                                >
                            </div>

                            {{-- Submit --}}
                            <div class="d-flex justify-content-between mt-4">
                                <a href="{{ route('sku-orders.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Back
                                </a>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Update
                                </button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
