@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="max-w-md mx-auto bg-white rounded-lg shadow p-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Login</h2>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('login') }}" method="POST" id="login-form">
        @csrf

        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-bold mb-2">Email</label>
            <input type="email" name="email" id="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ old('email') }}" required>
        </div>

        <div class="mb-6">
            <label for="password" class="block text-gray-700 font-bold mb-2">Password</label>
            <input type="password" name="password" id="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>

        <div class="mb-4 text-right">
            <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-700 font-bold">Forgot password?</a>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700">Login</button>
    </form>

    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <p class="text-sm font-semibold text-gray-900 mb-3">Sample logins</p>
        <p class="text-xs text-gray-500 mb-3">All sample accounts use the password <code class="rounded bg-white px-1.5 py-0.5 font-mono text-gray-700">password</code>. Click a role to fill the form.</p>
        <ul class="space-y-2 text-sm">
            <li>
                <button type="button" data-demo-email="demo@example.com"
                    class="w-full rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-left transition hover:bg-amber-100">
                    <span class="font-semibold text-amber-900">Demo Admin</span>
                    <span class="mt-0.5 block text-xs text-amber-800">demo@example.com · explore admin features (read-only settings)</span>
                </button>
            </li>
            <li>
                <button type="button" data-demo-email="technician@example.com"
                    class="w-full rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-left transition hover:bg-blue-100">
                    <span class="font-semibold text-blue-900">Technician</span>
                    <span class="mt-0.5 block text-xs text-blue-800">technician@example.com · manage assigned tickets</span>
                </button>
            </li>
            <li>
                <button type="button" data-demo-email="test@example.com"
                    class="w-full rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-left transition hover:bg-emerald-100">
                    <span class="font-semibold text-emerald-900">Customer</span>
                    <span class="mt-0.5 block text-xs text-emerald-800">test@example.com · book repairs and track tickets</span>
                </button>
            </li>
        </ul>
    </div>

    <p class="text-center mt-4">
        Don't have an account?
        <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700 font-bold">Register here</a>
    </p>
</div>

<script>
    document.querySelectorAll('[data-demo-email]').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('email').value = button.dataset.demoEmail;
            document.getElementById('password').value = 'password';
            document.getElementById('email').focus();
        });
    });
</script>
@endsection
