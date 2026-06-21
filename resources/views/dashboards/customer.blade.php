@extends('layouts.app')

@section('title', 'Customer Dashboard')

@section('content')
{{-- Header --}}
<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="text-sm font-medium text-blue-600">{{ now()->format('l, F j') }}</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">
            Hello, {{ Auth::user()->name }} 👋
        </h1>
        <p class="mt-1 text-gray-500">Track your repair bookings and stay updated on every ticket.</p>
    </div>
    @can('create', App\Models\Ticket::class)
        <a href="{{ route('tickets.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Booking
        </a>
    @endcan
</div>

{{-- Stats --}}
<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
    @include('partials.dashboard.stat-card', [
        'label' => 'Total Tickets',
        'value' => $totalTickets,
        'color' => 'slate',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
    ])
    @include('partials.dashboard.stat-card', [
        'label' => 'Active',
        'value' => $activeTickets,
        'hint' => 'In progress or pending',
        'color' => 'blue',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
    ])
    @include('partials.dashboard.stat-card', [
        'label' => 'Resolved',
        'value' => $resolvedTickets,
        'color' => 'emerald',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    ])
    @include('partials.dashboard.stat-card', [
        'label' => 'Closed',
        'value' => $closedTickets,
        'color' => 'violet',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
    ])
    @include('partials.dashboard.stat-card', [
        'label' => 'Cancelled',
        'value' => $cancelledTickets,
        'color' => 'rose',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
    ])
</div>

{{-- Tickets --}}
<div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Your Tickets</h2>
            <p class="text-sm text-gray-500">{{ $tickets->count() }} repair request{{ $tickets->count() !== 1 ? 's' : '' }}</p>
        </div>
        <a href="{{ route('tickets.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">View all →</a>
    </div>

    @forelse($tickets as $ticket)
        <a href="{{ route('tickets.show', $ticket) }}" class="group flex flex-col gap-4 border-b border-gray-50 px-6 py-5 last:border-0 hover:bg-gray-50/80 transition sm:flex-row sm:items-center">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-sm">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-semibold text-gray-900 group-hover:text-blue-600 transition">#{{ $ticket->id }} · {{ $ticket->issue_summary }}</span>
                    @include('partials.dashboard.status-badge', ['status' => $ticket->displayStatus()])
                </div>
                <p class="mt-1 text-sm text-gray-500">{{ $ticket->device_type }} · {{ $ticket->brand }} · {{ $ticket->os }}</p>
                @if($ticket->technician)
                    <p class="mt-1 text-xs text-gray-400">Assigned to {{ $ticket->technician->name }}</p>
                @endif
            </div>
            <div class="flex items-center gap-4 sm:flex-col sm:items-end sm:gap-1">
                @include('partials.dashboard.priority', ['priority' => $ticket->priority])
                <span class="text-xs text-gray-400">{{ $ticket->created_at->diffForHumans() }}</span>
            </div>
            <svg class="hidden h-5 w-5 text-gray-300 group-hover:text-blue-500 sm:block transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
    @empty
        <div class="px-6 py-16 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-500">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900">No tickets yet</h3>
            <p class="mt-2 text-sm text-gray-500 max-w-sm mx-auto">Submit your first repair request and we'll get your device fixed in no time.</p>
            @can('create', App\Models\Ticket::class)
                <a href="{{ route('tickets.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 transition">
                    Create your first booking
                </a>
            @endcan
        </div>
    @endforelse
</div>
@endsection
