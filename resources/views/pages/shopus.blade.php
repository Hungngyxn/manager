@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'orders'])

@section('_content')
<div class="container-fluid mt-2 px-4">
    <div class="row">
        <div class="col-12">
            <h4 class="font-weight-bold">Orders List</h4>
            <hr>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered text-center align-middle">
            <thead class="table-light">
                <tr>
                    <th>Order ID</th>
                    <th>Product</th>
                    <th>Customer Info</th>
                    <th>Tracking Number / Label</th>
                    <th>Status</th>
                    <th>Total Amount</th>
                    <th>Created At</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td>{{ $order->order_id }}</td>
                        <td class="text-start">
                            <div class="d-flex align-items-center">
                                @if(!empty($order->product_image))
                                    <img src="{{ $order->product_image }}" alt="Product Image" class="me-2" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $order->product_name }}</div>
                                    <small class="text-muted">{{ $order->sku }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="text-start">
                            <div class="fw-semibold">{{ $order->customer_name }}</div>
                            <div>{{ $order->customer_phone }}</div>
                            <small class="text-muted">{{ $order->customer_address }}</small>
                        </td>
                        <td>
                            <div>{{ $order->tracking_number ?? '-' }}</div>
                            @if(!empty($order->label_link))
                                <a href="{{ $order->label_link }}" target="_blank" class="text-decoration-none">
                                    <small>View Label</small>
                                </a>
                            @endif
                            <div class="mt-2">
                                <form action="{{ route('shopus.createLabel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        Create Label
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td>
                            <span class="badge 
                                @if($order->status == 'Shipped') bg-success 
                                @elseif($order->status == 'Pending') bg-warning 
                                @else bg-secondary 
                                @endif">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td>{{ number_format($order->total_amount, 2) }}</td>
                        <td>{{ $order->created_at->format('Y-m-d') }}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editModal" 
                                data-id="{{ $order->id }}"
                                data-tracking="{{ $order->tracking_number }}"
                                data-label="{{ $order->label_link }}">
                                Edit
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editForm" method="POST" action="{{ route('shopus.update') }}">
            @csrf
            <input type="hidden" name="id" id="orderId">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tracking Number</label>
                        <input type="text" class="form-control" name="tracking_number" id="trackingNumber">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Label Link</label>
                        <input type="text" class="form-control" name="label_link" id="labelLink">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const tracking = button.getAttribute('data-tracking');
        const label = button.getAttribute('data-label');

        document.getElementById('orderId').value = id;
        document.getElementById('trackingNumber').value = tracking ?? '';
        document.getElementById('labelLink').value = label ?? '';
    });
</script>
@endpush
@endsection
