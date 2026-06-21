@extends('layouts.app')

@section('title', 'Two-Factor Authentication')

@section('content')
<div class="mx-auto max-w-md">
    <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-900">Authentication code</h1>
        <p class="mt-2 text-sm text-gray-600">Enter the 6-digit code from your authenticator app, or use a recovery code.</p>

        @if($errors->any())
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('two-factor.verify') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1.5">Authenticator code</label>
                <input type="text" name="code" id="code" inputmode="numeric" autocomplete="one-time-code"
                    class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-center text-lg tracking-widest shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="recovery_code" class="block text-sm font-medium text-gray-700 mb-1.5">Recovery code (optional)</label>
                <input type="text" name="recovery_code" id="recovery_code"
                    class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-105">
                Continue
            </button>
        </form>

        <form action="{{ route('logout') }}" method="POST" class="mt-4 text-center">
            @csrf
            <button type="submit" class="text-sm font-medium text-gray-500 hover:text-gray-800">Sign out</button>
        </form>
    </div>
</div>
@endsection
