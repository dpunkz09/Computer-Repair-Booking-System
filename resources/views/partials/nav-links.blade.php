@php
    $linkClass = ($mobile ?? false)
        ? 'block rounded-lg px-3 py-2.5 text-sm font-medium'
        : 'rounded-lg px-3 py-2 text-sm font-medium';

    $activeClass = ($mobile ?? false) ? 'bg-blue-50 text-blue-700' : 'bg-blue-50 text-blue-700';
    $inactiveClass = ($mobile ?? false) ? 'text-gray-700 hover:bg-gray-50' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900';
@endphp

<a href="{{ route('dashboard') }}" @if($mobile ?? false) @click="mobileNavOpen = false" @endif
    class="{{ $linkClass }} {{ request()->routeIs('dashboard') ? $activeClass : $inactiveClass }}">
    Dashboard
</a>
<a href="{{ route('tickets.index') }}" @if($mobile ?? false) @click="mobileNavOpen = false" @endif
    class="{{ $linkClass }} {{ request()->routeIs('tickets.*') ? $activeClass : $inactiveClass }}">
    Tickets
</a>
@if(Auth::user()->isAdmin())
    <a href="{{ route('admin.users') }}" @if($mobile ?? false) @click="mobileNavOpen = false" @endif
        class="{{ $linkClass }} {{ request()->routeIs('admin.*') ? $activeClass : $inactiveClass }}">
        Admin
    </a>
@endif
