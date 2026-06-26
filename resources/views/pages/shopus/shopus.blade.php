@extends('layouts.admin', ['accesses' => $accesses, 'active' => 'orders'])

@section('_content')
    <div class="container-fluid mt-3 px-4">
        {{-- 🔹 Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="bi bi-box-seam"></i> Orders List</h4>
        </div>

        {{-- 🔹 Filters --}}
        <div class="card shadow-sm mb-3 border-0">
            <div class="card-body py-3">
                <div class="row align-items-center g-2">
                    {{-- Export + Label --}}
                    <div class="col-md-4 d-flex gap-2">
                        <form id="exportForm" action="{{ route('shopus.export.selected') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-success px-3">
                                <i class="bi bi-download"></i> Export Selected
                            </button>
                        </form>

                        <a class="btn btn-outline-primary px-3" href="{{ route('shopus.createLabel') }}">
                            <i class="bi bi-printer"></i> Create Label
                        </a>

                        <a class="btn btn-outline-primary px-3" href="{{ route('shopus.getLabel') }}">
                            <i class="bi bi-printer"></i> Get Label
                        </a>
                    </div>

                    {{-- Form Filter --}}
                    <div class="col-md-8">
                        <form method="GET" action="{{ route('shopus.index') }}" class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control"
                                    placeholder="🔍 Search by name, phone, or order ID" value="{{ request('search') }}">
                            </div>

                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    <input type="text" id="date" name="date" class="form-control"
                                        placeholder="Select date" value="{{ request('date') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select name="shop" class="form-select">
                                    <option value="">-- Filter by Shop --</option>
                                    @foreach ($shops as $shop)
                                        <option value="{{ $shop }}"
                                            {{ request('shop') == $shop ? 'selected' : '' }}>
                                            {{ $shop }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i>
                                    Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
        </div>

        {{-- 🔹 Orders Table --}}
        <div class="table-responsive shadow-sm border rounded">
            <form id="ordersForm">
                <table class="table table-hover align-middle text-center mb-0">
                    <span class="fw-semibold ms-2 mt-2 d-inline-block mb-2">Total orders: {{ $ordercount }}</span>
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px"><input type="checkbox" id="checkAll"></th>
                            <th>Action</th>
                            <th>Order ID</th>
                            <th>Customer Info</th>
                            <th>Product</th>
                            <th>Tracking / Label</th>
                            <th>Status</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $order->order_id }}"></td>

                                {{-- Cột Action --}}
                                <td>
                                    <div class="dropdown">
                                        <button class="btn" type="button" data-bs-toggle="dropdown"
                                            style="background-color: #7b8ea3; border: none; border-radius: 10px; display: inline-flex; align-items: center; gap: 12px; color: white;">
                                            <i class="fa-solid fa-gears" style="font-size: 0.8rem;"></i>
                                            <i class="fa-solid fa-chevron-down" style="font-size: 0.6rem;"></i>
                                        </button>

                                        <ul class="dropdown-menu shadow border-0">
                                            <li><a class="dropdown-item" href="#"><i
                                                        class="bi bi-pencil-fill me-2"></i> Sent to printer </a></li>
                                            <li>
                                                @php
                                                    $modalProducts = is_string($order->products)
                                                        ? json_decode($order->products, true)
                                                        : $order->products ?? [];
                                                    $orderJsonData = [
                                                        'order_id' => $order->order_id,
                                                        'label_link' => $order->label_link ?? '',
                                                        'products' => $modalProducts,
                                                    ];
                                                @endphp
                                                <button type="button" class="dropdown-item btn-send-printer"
                                                    data-bs-toggle="modal" data-bs-target="#updatePrintModal"
                                                    data-order="{{ json_encode($orderJsonData) }}">
                                                    <i class="bi bi-send-fill me-2"></i>Print setup
                                                </button>
                                            </li>
                                            <li><a class="dropdown-item" href="#"><i
                                                        class="bi bi-arrow-repeat me-2"></i> Re-update info</a></li>
                                            <li><a class="dropdown-item" href="#"><i class="bi bi-tag-fill me-2"></i>
                                                    Buy label</a></li>
                                        </ul>
                                    </div>
                                </td>

                                {{-- Order ID --}}
                                <td class="text-start">
                                    <div class="fw-semibold">{{ $order->order_id }}</div>
                                    <small class="text-muted d-block">{{ $order->shop_code }}</small>
                                    <small
                                        class="text-muted d-block mt-1">{{ $order->created_at->format('Y-m-d H:i') }}</small>
                                </td>

                                {{-- Customer Info --}}
                                <td class="text-start">
                                    <div class="fw-semibold">{{ $order->customer_name }}</div>
                                    <div class="text-muted small">{{ $order->customer_phone }}</div>
                                    <small class="text-muted d-block"
                                        style="max-width:250px; white-space:normal; word-break:break-word;">
                                        {{ $order->customer_address }}<br>
                                        {{ $order->customer_city }}, {{ $order->customer_state }}<br>
                                        {{ $order->customer_country }} {{ $order->customer_postcode }}
                                    </small>
                                </td>

                                {{-- Products ngoài bảng --}}
                                <td class="text-start">
                                    @php
                                        $products = is_string($order->products)
                                            ? json_decode($order->products, true)
                                            : $order->products ?? [];
                                    @endphp
                                    @foreach ($products as $p)
                                        @php
                                            $short =
                                                strlen($p['product_name'] ?? '') > 40
                                                    ? substr($p['product_name'], 0, 40) . '...'
                                                    : $p['product_name'] ?? 'N/A';
                                        @endphp
                                        <div class="d-flex align-items-start mb-3">
                                            @if (!empty($p['product_image']))
                                                <img src="{{ $p['product_image'] }}" alt="Product" width="65"
                                                    height="65" class="rounded border me-3 shadow-sm"
                                                    style="object-fit: cover;">
                                            @endif
                                            <div>
                                                <div class="fw-semibold mb-1">{{ $short }}</div>
                                                <div class="text-muted small">
                                                    <span>SKU: {{ $p['sku'] ?? 'N/A' }}</span> | <span>Qty:
                                                        {{ $p['quantity'] ?? 1 }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ $order->tracking_number ?? '—' }}</div>
                                    @if ($order->label_link)
                                        <a href="{{ $order->label_link }}" target="_blank"
                                            class="text-decoration-underline small text-primary">Label Link</a>
                                    @endif
                                </td>
                                <td><span class="badge bg-success">{{ $order->status }}</span></td>
                                <td class="text-muted small">{{ $order->price }}</td>
                                <td class="text-muted small">{{ $order->total_amount }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-4 text-muted">No orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </form>
        </div>
    </div>

    {{-- 🔹 Modal Update Print Info (Đã tinh gọn theo ảnh mẫu 2) --}}
    <div class="modal fade" id="updatePrintModal" tabindex="-1" aria-labelledby="updatePrintModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 580px;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-secondary" id="updatePrintModalLabel">Update print info</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-2">
                    <form id="printInfoForm" method="POST" action="">
                        @csrf

                        <div id="modalProductsContainer" class="mb-3 pe-1"
                            style="max-height: 480px; overflow-y: auto; overflow-x: hidden;">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-muted mb-0"
                                style="font-size:0.75rem;">Shipment:</label>
                            <select name="products[${index}][shipment]" class="form-select form-select-sm">
                                <option value="Standard">Standard</option>
                                <option value="Rush Product">Rush Product</option>
                                <option value="Expedite Tiktok">Expedite Tiktok</option>
                            </select>
                        </div>


                        <div class="mb-3 px-1">
                            <label class="form-label small fw-semibold text-dark mb-1">Printer: <span
                                    class="text-danger">*</span></label>
                            <select id="modalPrinter" name="printer" class="form-select form-select-sm" required>
                                <option value="">-Select printer-</option>
                                <option value="printer_1">Printer 1</option>
                                <option value="printer_2">Printer 2</option>
                            </select>
                        </div>

                        <div class="mb-3 px-1">
                            <label class="form-label small fw-semibold text-dark mb-1">Shipping label url: <span
                                    class="text-danger">*</span></label>
                            <input type="url" id="modalShippingLabel" name="shipping_label_url"
                                class="form-control form-control-sm" required>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-3 border-top px-1">
                            <button type="button" class="btn btn-sm btn-secondary px-4" data-bs-dismiss="modal"
                                style="background-color: #7b8ea3; border: none;"><i class="bi bi-x-lg"></i> Close</button>
                            <button type="submit" class="btn btn-sm btn-danger px-4"
                                style="background-color: #d1127d; border: none;"><i class="bi bi-save"></i> Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- 🔹 JS Xử Lý Logic Giao Diện Mới --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Checkbox Select All
                const checkAll = document.getElementById('checkAll');
                checkAll?.addEventListener('click', e => {
                    document.querySelectorAll('input[name="ids[]"]').forEach(cb => cb.checked = e.target
                        .checked);
                });

                // Datepicker
                if (typeof flatpickr !== 'undefined') {
                    flatpickr("#date", {
                        dateFormat: "Y-m-d"
                    });
                }

                // Export Form
                document.getElementById('exportForm')?.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const checked = document.querySelectorAll('input[name="ids[]"]:checked');
                    if (checked.length === 0) {
                        alert('Please select at least one order.');
                        return;
                    }
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = this.action;
                    form.appendChild(document.querySelector('input[name="_token"]').cloneNode(true));
                    checked.forEach(cb => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = cb.value;
                        form.appendChild(input);
                    });
                    document.body.appendChild(form);
                    form.submit();
                });

                // 🌟 RENDER ĐA SẢN PHẨM LẶP ĐỘC LẬP VÀO MODAL POPUP
                const printModal = document.getElementById('updatePrintModal');
                if (printModal) {
                    printModal.addEventListener('show.bs.modal', function(event) {
                        const button = event.relatedTarget;
                        const orderData = JSON.parse(button.getAttribute('data-order'));

                        // Đổ dữ liệu dùng chung của Order
                        printModal.querySelector('#modalShippingLabel').value = orderData.label_link || '';

                        const form = printModal.querySelector('#printInfoForm');
                        form.action = `/admin/orders/${orderData.order_id}/save-print-info`;

                        const container = printModal.querySelector('#modalProductsContainer');
                        container.innerHTML = ''; // Clear dữ liệu cũ

                        const products = orderData.products || [];

                        products.forEach((product, index) => {
                            const imgUrl = product.product_image ? product.product_image :
                                'https://via.placeholder.com/150';
                            const pName = product.product_name || 'Tshirt';
                            const pColor = product.color || 'White';
                            const pSize = product.size || 'M';
                            const pSku = product.sku || 'N/A';
                            const pQty = product.quantity || 1;
                            const pType = product.product_type || 'Tshirt';

                            // Tạo danh sách 12 nút vị trí in (Độc lập theo từng sản phẩm)
                            const positions = ['Front', 'Back', 'Neck', 'Right', 'Left', '3D',
                                'Front Right', 'Front Left', 'Back Right', 'Back Left',
                                'Center Front', 'Center Back'
                            ];
                            let positionsHtml = '';
                            positions.forEach(pos => {
                                const slug = pos.toLowerCase().replace(/ /g, '_');
                                const inputId = `pos_${index}_${slug}`;
                                // Mặc định tích chọn nút 'Front' giống thiết kế
                                const checked = pos === 'Front' ? 'checked' : '';

                                positionsHtml += `
                                    <div class="p-0">
                                        <input type="checkbox" class="btn-check" name="products[${index}][print_position][]" value="${pos}" id="${inputId}" ${checked}>
                                        <label class="btn btn-sm btn-outline-secondary px-2 py-1 text-nowrap" style="font-size: 0.72rem; min-width: 65px;" for="${inputId}">${pos}</label>
                                    </div>
                                `;
                            });

                            // Khối giao diện sản phẩm clone chuẩn theo ảnh mẫu số 2
                            const productHtml = `
                                <div class="product-item-block p-3 mb-3 rounded" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                                    <div class="fw-bold mb-2 text-dark text-truncate d-block" style="font-size: 0.85rem;">
                                        ${pName}, ${pColor}, ${pSize}
                                    </div>
                                    
                                    <div class="row g-3 align-items-start mb-2">
                                        <div class="col-3 text-center">
                                            <img src="${imgUrl}" class="img-fluid rounded border bg-white shadow-sm" alt="Product" style="object-fit: cover; aspect-ratio: 1/1; max-height: 90px; width: 100%;">
                                        </div>
                                        <div class="col-9">
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-muted mb-0" style="font-size:0.75rem;">Product type: <span class="text-danger">*</span></label>
                                                <input type="text" name="products[${index}][product_type]" class="form-control form-control-sm" value="${pType}" required>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold text-muted mb-0" style="font-size:0.75rem;">Color: <span class="text-danger">*</span></label>
                                                    <input type="text" name="products[${index}][color]" class="form-control form-control-sm" value="${pColor}" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold text-muted mb-0" style="font-size:0.75rem;">Size: <span class="text-danger">*</span></label>
                                                    <input type="text" name="products[${index}][size]" class="form-control form-control-sm" value="${pSize}" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold text-muted mb-1" style="font-size:0.75rem;">Print position:</label>
                                        <div class="d-flex flex-wrap gap-1">
                                            ${positionsHtml}
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold text-muted mb-0" style="font-size:0.75rem;">Design URL: <span class="text-danger">*</span></label>
                                            <input type="url" name="design_url" class="form-control form-control-sm" value="" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold text-muted mb-0" style="font-size:0.75rem;">Mockup URL: <span class="text-danger">*</span></label>
                                            <input type="url" name="mockup_url" class="form-control form-control-sm" value="" required>
                                        </div>
                                    </div>

                                    <div class="mt-2 d-flex flex-column gap-1">
                                        <div class="form-check form-check-sm">
                                            <input class="form-check-input" type="checkbox" id="specialPrint_${index}" name="products[${index}][special_print]" value="1">
                                            <label class="form-check-label small text-muted" style="font-size:0.8rem;" for="specialPrint_${index}">Special print</label>
                                        </div>
                                        <div class="form-check form-check-sm">
                                            <input class="form-check-input" type="checkbox" id="isEmbroidered_${index}" name="products[${index}][is_embroidered]" value="1">
                                            <label class="form-check-label small text-muted" style="font-size:0.8rem;" for="isEmbroidered_${index}">Is embroidered</label>
                                        </div>
                                    </div>

                                    <input type="hidden" name="products[${index}][sku]" value="${pSku}">
                                    <input type="hidden" name="products[${index}][quantity]" value="${pQty}">
                                </div>
                            `;
                            container.insertAdjacentHTML('beforeend', productHtml);
                        });
                    });
                }
            });
        </script>

        {{-- Thêm đoạn CSS nhỏ để bổ sung kiểu dáng viền đỏ khi click chọn Nút vị trí in giống ảnh mẫu số 1 của bạn --}}
        <style>
            .product-item-block .btn-check:checked+.btn-outline-secondary {
                border-color: #dc3545 !important;
                color: #dc3545 !important;
                background-color: transparent !important;
                font-weight: 600;
            }
        </style>
    @endpush
@endsection
