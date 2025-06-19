<nav id="sidebar" class="navbar-nav">
    <div class="sidebar-header">
        <a class="navbar-brand text-white" href="{{ url('/') }}">
            <h3> {{ config('app.name', 'TIKTOK-ORDER') }}</h3>
        </a>
    </div>
    <ul class="list-unstyled components">
        @foreach ($accesses as $access)
            @if ($access->status > 0)
                <li class="nav-item {{ $active == $access->menu->name ? 'nav-active' : '' }}" style="padding-top: 10px;">
                    @include('components.nav.' . $access->menu->name)
                </li>
            @endif
        @endforeach
    </ul>
    <div class="mt-auto text-center text-white pb-3" style="font-size: 0.9rem;">
        Version 1.0.0
    </div>
</nav>
