@extends('layouts.app')

@section('title', 'Assign Tickets')

@section('content')
<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Unassigned Tickets</h1>
        <p class="mt-1 text-gray-500">Assign open tickets without a technician to your team.</p>
    </div>
    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-800 transition">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to dashboard
    </a>
</div>

@if($tickets->isEmpty())
    <div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-16 text-center shadow-sm">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h2 class="text-lg font-semibold text-gray-900">All caught up</h2>
        <p class="mt-1 text-sm text-gray-500">Every open ticket has a technician assigned.</p>
    </div>
@else
    <div class="space-y-4">
        @foreach($tickets as $ticket)
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-5">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <h3 class="font-semibold text-gray-900">#{{ $ticket->id }} · {{ $ticket->issue_summary }}</h3>
                            @include('partials.dashboard.status-badge', ['status' => $ticket->displayStatus()])
                        </div>
                        <p class="text-sm text-gray-500">Customer: {{ $ticket->customer->name }}</p>
                        <p class="text-sm text-gray-500">{{ $ticket->device_type }} · {{ $ticket->brand }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Issue</p>
                        <p class="text-sm text-gray-700">{{ Str::limit($ticket->description, 150) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Priority</p>
                        @include('partials.dashboard.priority', ['priority' => $ticket->priority])
                        <p class="mt-2 text-xs text-gray-400">{{ $ticket->created_at->diffForHumans() }}</p>
                    </div>
                </div>

                <form action="{{ route('admin.assign-ticket', $ticket) }}" method="POST" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1">
                        <label for="technician_id_{{ $ticket->id }}" class="block text-sm font-medium text-gray-700 mb-1.5">Assign to technician</label>
                        <select name="technician_id" id="technician_id_{{ $ticket->id }}" class="w-full rounded-xl border-gray-200 bg-gray-50 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="">Choose a technician...</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white transition hover:brightness-105 shrink-0">
                        Assign
                    </button>
                    <a href="{{ route('tickets.show', $ticket) }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 shrink-0">
                        View ticket
                    </a>
                </form>
            </div>
        @endforeach
    </div>

    @if($tickets->hasPages())
        <div class="mt-8">
            {{ $tickets->links() }}
        </div>
    @endif
@endif
@endsection
