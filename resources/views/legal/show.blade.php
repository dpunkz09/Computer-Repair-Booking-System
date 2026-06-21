@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-800 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to home
        </a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-gray-900">{{ $title }}</h1>
        <p class="mt-1 text-sm text-gray-500">Last updated content managed in site settings.</p>
    </div>

    <article class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm prose prose-slate max-w-none">
        {!! nl2br(e($content)) !!}
    </article>
</div>
@endsection
