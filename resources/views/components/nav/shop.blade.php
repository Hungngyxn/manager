@php
$shopActive = request()->routeIs('shop.*') || request()->routeIs('shop-accounts.*');
$isAllShop = request()->routeIs('shop.index');
$isAccountTTS = request()->routeIs('shop-accounts.*');
@endphp
@if (auth()->user()->role->is_super_user === 1)
    <a href="#shopSubmenu" data-bs-toggle="collapse"
        class="nav-link d-flex justify-content-between align-items-center {{ $isAccountTTS ? 'collapsed' : '' }}">
        <span>
            <i class="fas fa-user-alt me-2"></i>
            Shop
        </span>
        <i class="fas fa-angle-down"></i>
    </a>
    <ul class="collapse list-unstyled {{ $isAllShop || $isAccountTTS ? 'show' : '' }}" id="shopSubmenu">
        <li class="nav-item">
            <a href="{{ route('shop.index') }}">All Shop</a>
        </li>
        <li class="nav-item">
            <a href="{{ route('shop-accounts.index') }}">Account TTS</a>
        </li>
    </ul>
@else
    <a href="{{ route('shop.index') }}" class="nav-link">
        <i class="fa-solid fa-shop mr-2"></i> Shop
    </a>
@endif
