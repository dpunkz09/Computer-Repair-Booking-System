@extends('layouts.app')

@section('title', 'Technician Dashboard')

@section('content')
{{-- Header --}}
<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="text-sm font-medium text-amber-600">{{ now()->format('l, F j') }}</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">
            Work Queue
        </h1>
        <p class="mt-1 text-gray-500">Hi {{ Auth::user()->name }}, here are your assigned repair tickets sorted by priority.</p>
    </div>
    <a href="{{ route('tickets.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition">
        All Tickets
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>
</div>

{{-- Stats --}}
<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
    @include('partials.dashboard.stat-card', [
        'label' => 'Assigned',
        'value' => $assignedCount,
        'color' => 'blue',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>',
    ])
    @include('partials.dashboard.stat-card', [
        'label' => 'In Progress',
        'value' => $inProgressCount,
        'color' => 'amber',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    ])
    @include('partials.dashboard.stat-card', [
        'label' => 'Awaiting Parts',
        'value' => $awaitingPartsCount,
        'color' => 'violet',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>',
    ])
    @include('partials.dashboard.stat-card', [
        'label' => 'Completed',
        'value' => $resolvedCount,
        'color' => 'emerald',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    ])
    @include('partials.dashboard.stat-card', [
        'label' => 'Overdue ETA',
        'value' => $overdueEtaCount,
        'hint' => 'Past estimated completion',
        'color' => 'orange',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    ])
</div>

{{-- Ticket cards --}}
<div class="space-y-4">
    @forelse($assignedTickets as $ticket)
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm hover:shadow-md transition">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex gap-4 min-w-0 flex-1">
                    @include('partials.user-avatar', ['user' => $ticket->customer, 'size' => 'card', 'shape' => 'rounded'])
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('tickets.show', $ticket) }}" class="font-semibold text-gray-900 hover:text-blue-600 transition">
                                #{{ $ticket->id }} · {{ Str::limit($ticket->issue_summary, 50) }}
                            </a>
                            @include('partials.dashboard.status-badge', ['status' => $ticket->displayStatus()])
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            <span class="font-medium text-gray-700">{{ $ticket->customer->name }}</span>
                            · {{ $ticket->device_type }} · {{ $ticket->brand }}
                        </p>
                        <div class="mt-2 flex items-center gap-3">
                            @include('partials.dashboard.priority', ['priority' => $ticket->priority])
                            <span class="text-xs text-gray-400">{{ $ticket->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center lg:shrink-0">
                    <form action="{{ route('tickets.status.update', $ticket) }}" method="POST" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <label for="status_{{ $ticket->id }}" class="sr-only">Update status</label>
                        <select name="status" id="status_{{ $ticket->id }}" onchange="this.form.submit()"
                            class="rounded-xl border-gray-200 bg-gray-50 py-2 pl-3 pr-8 text-sm font-medium text-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach(\App\Support\TicketStatus::TECHNICIAN_QUICK_UPDATE as $status)
                                <option value="{{ $status }}" {{ $ticket->status === $status ? 'selected' : '' }}>
                                    {{ \App\Support\TicketStatus::label($status) }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                    <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 transition">
                        Open Ticket
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-16 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-500">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900">All caught up!</h3>
            <p class="mt-2 text-sm text-gray-500">No tickets assigned to you right now. Check back later.</p>
        </div>
    @endforelse
</div>
@endsection
