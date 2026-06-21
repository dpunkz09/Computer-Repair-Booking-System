<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ $site->name ?? 'ComTech Repair' }}</title>
    <meta name="description" content="{{ $site->seo_description ?? '' }}">
    @if(!empty($site->seo_keywords))
        <meta name="keywords" content="{{ $site->seo_keywords }}">
    @endif
    <meta property="og:title" content="{{ $site->seo_title ?? $site->name }}">
    <meta property="og:description" content="{{ $site->seo_description ?? '' }}">
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root { --brand-primary: {{ $site->primary_color ?? '#2563eb' }}; }</style>
</head>
<body class="bg-slate-50 antialiased" x-data="{ mobileNavOpen: false }" @keydown.escape.window="mobileNavOpen = false">
    <nav class="sticky top-0 z-50 border-b border-gray-200/80 bg-white/90 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-3 md:gap-8 min-w-0">
                    @auth
                        <button type="button" @click="mobileNavOpen = !mobileNavOpen"
                            class="md:hidden inline-flex items-center justify-center rounded-lg p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900"
                            aria-label="Toggle navigation">
                            <svg x-show="!mobileNavOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                            <svg x-show="mobileNavOpen" x-cloak class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endauth
                    @include('partials.site-brand')
                    @auth
                        <div class="hidden md:flex items-center gap-1">
                            @include('partials.nav-links')
                        </div>
                    @endauth
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    @auth
                        @include('partials.notifications-bell')
                        <a href="{{ route('profile.edit') }}" class="hidden sm:flex items-center gap-2 rounded-xl border px-3 py-1.5 transition {{ request()->routeIs('profile.*') ? 'border-blue-200 bg-blue-50' : 'border-gray-200 bg-white hover:bg-gray-50' }}">
                            @include('partials.user-avatar', ['user' => Auth::user(), 'size' => 'xs'])
                            <span class="text-sm font-medium text-gray-700">{{ Auth::user()->name }}</span>
                            <span class="text-xs rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-600">{{ ucfirst(Auth::user()->role) }}</span>
                        </a>
                        <form action="{{ route('logout') }}" method="POST" class="hidden sm:inline">
                            @csrf
                            <button type="submit" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">Login</a>
                        <a href="{{ route('register') }}" class="rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-white transition">Register</a>
                    @endauth
                </div>
            </div>
        </div>

        @auth
            <div x-show="mobileNavOpen" x-cloak x-transition class="border-t border-gray-200 bg-white md:hidden">
                <div class="space-y-1 px-4 py-3">
                    @include('partials.nav-links', ['mobile' => true])
                    <form action="{{ route('logout') }}" method="POST" class="pt-2 border-t border-gray-100 sm:hidden">
                        @csrf
                        <button type="submit" class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-medium text-gray-600 hover:bg-gray-50">Logout</button>
                    </form>
                </div>
            </div>
        @endauth
    </nav>

    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
                <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
                <svg class="h-5 w-5 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    <footer class="border-t border-gray-200 bg-white mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-sm text-gray-500">
                @if(!empty($site->footer_text))
                    {{ $site->footer_text }}
                @else
                    &copy; {{ date('Y') }} {{ $site->name ?? 'ComTech Repair' }}. All rights reserved.
                @endif
            </p>
            @if(!empty($site->contact_email) || !empty($site->contact_phone))
                <p class="text-center text-xs text-gray-400 mt-2">
                    @if(!empty($site->contact_email))
                        <a href="mailto:{{ $site->contact_email }}" class="hover:text-gray-600">{{ $site->contact_email }}</a>
                    @endif
                    @if(!empty($site->contact_email) && !empty($site->contact_phone)) · @endif
                    @if(!empty($site->contact_phone))
                        {{ $site->contact_phone }}
                    @endif
                    @if(!empty($site->support_hours))
                        · {{ $site->support_hours }}
                    @endif
                </p>
            @endif
            @if(!empty($site->has_privacy_policy) || !empty($site->has_terms_of_service))
                <p class="text-center text-xs text-gray-400 mt-3 flex flex-wrap items-center justify-center gap-x-3 gap-y-1">
                    @if(!empty($site->has_privacy_policy))
                        <a href="{{ route('legal.privacy') }}" class="hover:text-gray-600">{{ $site->privacy_policy_title }}</a>
                    @endif
                    @if(!empty($site->has_privacy_policy) && !empty($site->has_terms_of_service))
                        <span aria-hidden="true">·</span>
                    @endif
                    @if(!empty($site->has_terms_of_service))
                        <a href="{{ route('legal.terms') }}" class="hover:text-gray-600">{{ $site->terms_of_service_title }}</a>
                    @endif
                </p>
            @endif
        </div>
    </footer>
</body>
</html>
