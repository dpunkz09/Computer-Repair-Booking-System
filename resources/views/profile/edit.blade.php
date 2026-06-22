@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="mb-8">
    <p class="text-sm font-medium text-blue-600">{{ now()->format('l, F j') }}</p>
    <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Account Settings</h1>
    <p class="mt-1 text-gray-500">Manage your profile, photo, and security preferences.</p>
</div>

@if($errors->any())
    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
        <ul class="list-inside list-disc text-sm">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 gap-8 lg:grid-cols-3" x-data="{ preview: null, fileName: '' }">
    {{-- Sidebar: avatar card --}}
    <div class="lg:col-span-1">
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col items-center text-center">
                <template x-if="preview">
                    <img :src="preview" alt="Preview" class="h-32 w-32 rounded-full object-cover ring-4 ring-blue-100 shadow-md">
                </template>
                <div x-show="!preview">
                    @include('partials.user-avatar', ['user' => $user, 'size' => 'xl'])
                </div>

                <h2 class="mt-4 text-lg font-semibold text-gray-900">{{ $user->name }}</h2>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                <span class="mt-2 inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-600/20">
                    {{ ucfirst($user->role) }}
                </span>
                <p class="mt-4 text-xs text-gray-400">Member since {{ $user->created_at->format('M Y') }}</p>
            </div>

            <div class="mt-6 space-y-3 border-t border-gray-100 pt-6">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Photo tips</p>
                <ul class="space-y-2 text-xs text-gray-500">
                    <li class="flex gap-2"><span class="text-blue-500">•</span> JPG, PNG, WebP or GIF up to 5 MB</li>
                    <li class="flex gap-2"><span class="text-blue-500">•</span> Automatically resized to 400×400 max</li>
                    <li class="flex gap-2"><span class="text-blue-500">•</span> Compressed as optimized JPEG</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Main forms --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Profile picture upload --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Profile Picture</h3>
            <p class="mt-1 text-sm text-gray-500">Upload a photo. It will be compressed automatically to save space.</p>

            @error('profile_picture')
                <p class="mt-3 text-sm text-rose-600">{{ $message }}</p>
            @enderror

            <form action="{{ route('profile.picture.store') }}" method="POST" enctype="multipart/form-data" class="mt-6">
                @csrf

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <label class="flex flex-1 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 px-6 py-8 transition hover:border-blue-400 hover:bg-blue-50/50">
                        <svg class="mb-3 h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-sm font-semibold text-gray-700">Click to choose an image</span>
                        <span class="mt-1 text-xs text-gray-400" x-text="fileName || 'JPG, PNG, WebP or GIF'"></span>
                        <input type="file" name="profile_picture" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" required
                               @change="fileName = $event.target.files[0]?.name ?? ''; preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                    </label>
                    <button type="submit" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition">
                        Upload Photo
                    </button>
                </div>
            </form>

            @if($user->profile_picture)
                <form action="{{ route('profile.picture.destroy') }}" method="POST" class="mt-4" onsubmit="return confirm('Remove your profile picture?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-semibold text-rose-600 hover:text-rose-700">
                        Remove current photo
                    </button>
                </form>
            @endif
        </div>

        {{-- Account details --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Account Details</h3>
            <p class="mt-1 text-sm text-gray-500">Update your name and email address.</p>

            <form action="{{ route('profile.update') }}" method="POST" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Full Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
                    <input type="text" value="{{ ucfirst($user->role) }}" disabled
                        class="w-full rounded-xl border-gray-200 bg-gray-100 px-4 py-2.5 text-gray-500 cursor-not-allowed">
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition">
                        Save Details
                    </button>
                    <a href="{{ route('dashboard') }}" class="rounded-xl border border-gray-200 bg-white px-6 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        {{-- Password --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900">Password & Security</h3>
            <p class="mt-1 text-sm text-gray-500">Leave blank to keep your current password.</p>

            <form action="{{ route('profile.update') }}" method="POST" class="mt-6 space-y-5">
                @csrf
                @method('PUT')
                <input type="hidden" name="name" value="{{ old('name', $user->name) }}">
                <input type="hidden" name="email" value="{{ old('email', $user->email) }}">

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                    <input type="password" name="current_password" id="current_password" autocomplete="current-password"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                        <input type="password" name="password" id="password" autocomplete="new-password"
                            class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password"
                            class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <button type="submit" class="rounded-xl bg-gray-900 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 transition">
                    Update Password
                </button>
            </form>
        </div>
        {{-- Two-factor authentication (admins) --}}
        @if($user->isFullAdmin())
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Two-Factor Authentication</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if(\App\Support\SiteSettings::bool('require_admin_2fa'))
                        Required for admin accounts on this site.
                    @else
                        Optional extra protection for your admin account.
                    @endif
                </p>

                @if(session('two_factor_recovery_codes'))
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-semibold text-amber-900">Save these recovery codes now</p>
                        <p class="mt-1 text-xs text-amber-800">Each code can be used once if you lose access to your authenticator app.</p>
                        <ul class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm text-amber-950">
                            @foreach(session('two_factor_recovery_codes') as $code)
                                <li>{{ $code }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($user->hasTwoFactorEnabled())
                    <p class="mt-4 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20">
                        Enabled {{ $user->two_factor_confirmed_at?->diffForHumans() }}
                    </p>

                    <form action="{{ route('two-factor.disable') }}" method="POST" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label for="two_factor_password" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm password to disable</label>
                            <input type="password" name="password" id="two_factor_password" required
                                class="w-full max-w-md rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('password')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        @if(\App\Support\SiteSettings::bool('require_admin_2fa'))
                            <p class="text-xs text-amber-700">Disabling 2FA will block admin access until you enable it again.</p>
                        @endif
                        <button type="submit" class="rounded-xl border border-rose-200 bg-rose-50 px-5 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                            Disable 2FA
                        </button>
                    </form>
                @else
                    <a href="{{ route('two-factor.setup') }}" class="mt-6 inline-flex rounded-xl bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-800">
                        Set up authenticator app
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
