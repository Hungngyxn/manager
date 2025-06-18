@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'sku'])

@section('_content')
    <div class="container mt-3">
        <h4>Add New SKU</h4>
        <form action="{{ route('sku.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">SKU</label>
                <input type="text" name="sku" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Cost</label>
                <input name="cost" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Quantity</label>
                <input name="quantity" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Tier</label>
                <select name="tier" class="form-select select2" required>
                    <option value="">-- Select Tier --</option>
                    @foreach ($tiers as $tier)
                        <option value="{{ $tier->tier }}">{{ $tier->tier}} ({{ $tier->bonus }}%)
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add SKU
                </button>
                <a href="{{ route('sku.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2();
        });
    </script>
@endpush
