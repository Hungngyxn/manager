@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'sku'])

@section('_content')
    <div class="container-fluid mt-2 px-4">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="font-weight-bold">
                    {{ isset($skuOrder) ? 'Edit SKU Order' : 'Add SKU Order' }}
                </h4>
                <hr>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-3">
                <div class="card bg-light text-dark p-4 shadow-sm rounded">
                    <form
                        action="{{ isset($skuOrder) ? route('sku-orders.update', $skuOrder->id) : route('sku-orders.store') }}"
                        method="POST">
                        @csrf
                        @if (isset($skuOrder))
                            @method('PUT')
                        @endif

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" name="sku" class="form-control" required
                                    value="{{ old('sku', $skuOrder->sku ?? '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ASIN</label>
                                <input type="text" name="asin" class="form-control"
                                    value="{{ old('asin', $skuOrder->asin ?? '') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quantity per Pack <span class="text-danger">*</span></label>
                                <input type="number" step="1" name="quantity_per_pack" class="form-control" required
                                    value="{{ old('quantity_per_pack', $skuOrder->quantity_per_pack ?? '') }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="product_name" class="form-control"
                                    value="{{ old('product_name', $skuOrder->product_name ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Warehouse Name</label>
                                <input type="text" name="warehouse_name" class="form-control"
                                    value="{{ old('warehouse_name', $skuOrder->warehouse_name ?? '') }}">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('sku-orders.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i> {{ isset($skuOrder) ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
