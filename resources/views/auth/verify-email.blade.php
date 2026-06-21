@extends('layouts.app')

@section('title', 'Verify Email')

@section('content')
<div class="mx-auto max-w-lg">
    <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-600">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Verify your email</h1>
        <p class="mt-3 text-sm text-gray-600">
            We sent a verification link to <strong>{{ Auth::user()->email }}</strong>.
            Click the link in that email to activate your account and start booking repairs.
        </p>

        <form action="{{ route('verification.send') }}" method="POST" class="mt-6">
            @csrf
            <button type="submit" class="rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white transition hover:brightness-105">
                Resend verification email
            </button>
        </form>

        <form action="{{ route('logout') }}" method="POST" class="mt-4">
            @csrf
            <button type="submit" class="text-sm font-medium text-gray-500 hover:text-gray-800">Sign out</button>
        </form>
    </div>
</div>
@endsection
