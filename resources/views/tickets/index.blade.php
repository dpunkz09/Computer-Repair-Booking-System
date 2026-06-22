@extends('layouts.app')

@section('title', 'All Tickets')

@section('content')
<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Tickets</h1>
        <p class="mt-1 text-gray-500">
            @if(Auth::user()->isAdmin())
                All repair tickets across the system
            @elseif(Auth::user()->role === 'technician')
                Tickets assigned to you
            @else
                Your repair bookings and their status
            @endif
        </p>
    </div>
    @can('create', App\Models\Ticket::class)
        <a href="{{ route('tickets.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:brightness-105">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Booking
        </a>
    @endcan
</div>

@include('partials.tickets.filters')

@php
    $hasFilters = collect($filters ?? [])
        ->reject(fn ($value, $key) => $key === 'sort' && ($value === 'priority' || $value === null))
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->isNotEmpty();
@endphp

@if($tickets->isEmpty())
    <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-16 text-center shadow-sm">
        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <h2 class="mt-4 text-lg font-semibold text-gray-900">{{ $hasFilters ? 'No matching tickets' : 'No tickets yet' }}</h2>
        <p class="mt-1 text-sm text-gray-500">
            @if($hasFilters)
                Try adjusting your search or filters.
            @elseif(Auth::user()->can('create', App\Models\Ticket::class))
                Submit your first repair request to get started.
            @else
                Tickets will appear here once customers submit bookings.
            @endif
        </p>
        @can('create', App\Models\Ticket::class)
            <a href="{{ route('tickets.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white transition hover:brightness-105">
                Book a Repair
            </a>
        @endcan
    </div>
@else
    {{-- Desktop table --}}
    <div class="hidden md:block overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/80">
                    <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Ticket</th>
                    @if(Auth::user()->role !== 'customer')
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Customer</th>
                    @endif
                    <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Device</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Issue</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Priority</th>
                    @if(Auth::user()->isAdmin())
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Technician</th>
                    @endif
                    <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($tickets as $ticket)
                    <tr class="group hover:bg-gray-50/60 transition">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                @if($ticket->photos->isNotEmpty())
                                    <img src="{{ $ticket->photos->first()->url() }}" alt="" class="h-10 w-10 shrink-0 rounded-lg object-cover ring-1 ring-gray-200">
                                @else
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400 ring-1 ring-gray-200">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-semibold text-gray-900">#{{ $ticket->id }}</p>
                                    @if($ticket->photos->count() > 1)
                                        <p class="text-xs text-gray-400">{{ $ticket->photos->count() }} photos</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        @if(Auth::user()->role !== 'customer')
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $ticket->customer->name }}</td>
                        @endif
                        <td class="px-5 py-4 text-sm text-gray-700">{{ $ticket->device_type }} · {{ $ticket->brand }}</td>
                        <td class="px-5 py-4 text-sm text-gray-700 max-w-[200px] truncate">{{ $ticket->issue_summary }}</td>
                        <td class="px-5 py-4">@include('partials.dashboard.status-badge', ['status' => $ticket->displayStatus()])</td>
                        <td class="px-5 py-4">@include('partials.dashboard.priority', ['priority' => $ticket->priority])</td>
                        @if(Auth::user()->isAdmin())
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $ticket->technician?->name ?? '—' }}</td>
                        @endif
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-brand hover:underline">
                                View
                                <svg class="h-4 w-4 opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="md:hidden space-y-3">
        @foreach($tickets as $ticket)
            <a href="{{ route('tickets.show', $ticket) }}" class="block rounded-2xl border border-gray-100 bg-white p-4 shadow-sm hover:border-blue-200 hover:shadow-md transition">
                <div class="flex gap-4">
                    @if($ticket->photos->isNotEmpty())
                        <img src="{{ $ticket->photos->first()->url() }}" alt="" class="h-16 w-16 shrink-0 rounded-xl object-cover ring-1 ring-gray-200">
                    @else
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-400">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-semibold text-gray-900 truncate">#{{ $ticket->id }} · {{ $ticket->issue_summary }}</p>
                            @include('partials.dashboard.status-badge', ['status' => $ticket->displayStatus()])
                        </div>
                        <p class="mt-1 text-sm text-gray-500">{{ $ticket->device_type }} · {{ $ticket->brand }}</p>
                        @if(Auth::user()->role !== 'customer')
                            <p class="mt-0.5 text-xs text-gray-400">{{ $ticket->customer->name }}</p>
                        @endif
                        <div class="mt-2 flex items-center justify-between">
                            @include('partials.dashboard.priority', ['priority' => $ticket->priority])
                            <span class="text-xs text-gray-400">{{ $ticket->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $tickets->links() }}
    </div>
@endif
@endsection
