@extends('layouts.app')

@section('title', 'Enable Two-Factor Authentication')

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-900">Set up two-factor authentication</h1>
        <p class="mt-2 text-sm text-gray-600">
            Scan the setup key below with Google Authenticator, 1Password, Authy, or another TOTP app.
        </p>

        <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Setup key</p>
            <p class="mt-2 break-all font-mono text-sm text-gray-900">{{ $secret }}</p>
            <p class="mt-3 text-xs text-gray-500 break-all">Or open this URL in your authenticator app: {{ $otpAuthUrl }}</p>
        </div>

        @if($errors->any())
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ $errors->first('code') }}
            </div>
        @endif

        <form action="{{ route('two-factor.confirm') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm with a 6-digit code</label>
                <input type="text" name="code" id="code" required inputmode="numeric" autocomplete="one-time-code"
                    class="w-full max-w-xs rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-center text-lg tracking-widest shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white transition hover:brightness-105">
                    Enable 2FA
                </button>
                <a href="{{ route('profile.edit') }}" class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
