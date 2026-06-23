@extends('layouts.admin', ['active' => 'sku'])

@section('_content')
    <div class="container mt-4">
        <h3>Edit SKU</h3>

        <form action="{{ route('sku.update', $sku->id) }}" method="POST" class="card p-4 shadow-sm">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-bold">SKU</label>
                <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror"
                    value="{{ old('sku', $sku->sku) }}" required>
                @error('sku')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $sku->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Cost</label>
                <input name="cost" class="form-control @error('cost') is-invalid @enderror"
                    value="{{ old('cost', $sku->cost) }}" required>
                @error('cost')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Quantity</label>
                <input name="quantity" class="form-control @error('quantity') is-invalid @enderror"
                    value="{{ old('quantity', $sku->quantity) }}" required>
                @error('quantity')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Tier --}}
            <div class="mb-3">
                <label class="form-label fw-bold">Tier</label>
                <select name="tier" class="form-select select2 @error('tier') is-invalid @enderror" required>
                    <option value="">-- Select Tier --</option>
                    @foreach ($tiers as $tier)
                        <option value="{{ $tier->tier }}" {{ old('tier', $sku->tier) == $tier->tier ? 'selected' : '' }}>
                            {{ $tier->tier }} ({{ $tier->bonus }}%)
                        </option>
                    @endforeach
                </select>
                @error('tier')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Update Orders --}}
            <div class="mb-3">
                <label class="form-label fw-bold">Update related orders</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="update_scope" id="update_all" value="all"
                        {{ old('update_scope') === 'after_update' ? '' : 'checked' }}>
                    <label class="form-check-label" for="update_all">
                        All orders containing this SKU
                    </label>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="radio" name="update_scope" id="update_after"
                        value="after_update" {{ old('update_scope') === 'after_update' ? 'checked' : '' }}>
                    <label class="form-check-label" for="update_after">
                        Only orders created after this update
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary me-2">Save Changes</button>
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
