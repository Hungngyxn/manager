@php
    $skuActive = request()->routeIs('sku.*') || request()->routeIs('sku-orders.*');
    $isAllSku = request()->routeIs('sku.index');
    $isSkuOrder = request()->routeIs('sku-orders.*');
@endphp

<a href="#skuSubmenu" data-bs-toggle="collapse"
    class="nav-link d-flex justify-content-between align-items-center {{ $skuActive ? '' : 'collapsed' }}">
    <span>
        <i class="fas fa-box-open me-2"></i>
        SKU
    </span>
    <i class="fas fa-angle-down"></i>
</a>

<ul class="collapse list-unstyled {{ $skuActive ? 'show' : '' }}" id="skuSubmenu">
    <li class="nav-item">
        <a href="{{ route('sku.index') }}" class="{{ $isAllSku ? 'active' : '' }}">All SKU</a>
    </li>
    <li class="nav-item">
        <a href="{{ route('sku-orders.index') }}" class="{{ $isSkuOrder ? 'active' : '' }}">SKU Order</a>
    </li>
</ul>
