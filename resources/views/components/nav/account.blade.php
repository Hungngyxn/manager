@php
    $userActive = request()->routeIs('profile.*');
@endphp

<a href="#userSubmenu"
   data-bs-toggle="collapse"
   aria-expanded="{{ $userActive ? 'true' : 'false' }}"
   class="nav-link d-flex justify-content-between align-items-center {{ $userActive ? '' : 'collapsed' }}">
    <span>
        <i class="fas fa-user-alt me-2"></i>
        {{ auth()->user()->name }}
    </span>
    <i class="fas fa-angle-down"></i>
</a>

<ul class="collapse list-unstyled {{ $userActive ? 'show' : '' }}" id="userSubmenu">
    <li class="nav-item">
        <a href="{{ route('profile.index') }}"
           class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            Profile
        </a>
    </li>
    <li>
        <a class="nav-link" href="{{ request()->isSecure() ? secure_url('logout') : route('logout') }}"
           onclick="confirmLogout(event)">
            {{ __('Logout') }}
        </a>

        <form id="logout-form" action="{{ request()->isSecure() ? secure_url('logout') : route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </li>
</ul>
<script>
    function confirmLogout(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Are you sure you want to logout?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: 'Cancel',
            confirmButtonText: 'Yes, Logout',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('logout-form').submit();
            }
        });
    }
</script>