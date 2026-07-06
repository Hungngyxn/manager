@php
    $fnActive = request()->routeIs('finance.*') || request()->routeIs('finance.*');
    $isStatement = request()->routeIs('finance.index');
    $isPayout = request()->routeIs('finance.*');
@endphp

<a href="#fnSubmenu" data-bs-toggle="collapse"
    class="nav-link d-flex justify-content-between align-items-center {{ $fnActive ? '' : 'collapsed' }}">
    <span>
        <i class="fas fa-box-open me-2"></i>
        Finance
    </span>
    <i class="fas fa-angle-down"></i>
</a>

<ul class="collapse list-unstyled {{ $fnActive ? 'show' : '' }}" id="fnSubmenu">
    <li class="nav-item">
        <a href="{{ route('finance.statement') }}" class="{{ $isStatement ? 'active' : '' }}">Statements</a>
    </li>
    <li class="nav-item">
        <a href="{{ route('finance.payout') }}" class="{{ $isPayout ? 'active' : '' }}">Payouts</a>
    </li>
</ul>