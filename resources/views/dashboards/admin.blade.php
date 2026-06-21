@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
{{-- Header --}}
<div class="mb-8">
    <p class="text-sm font-medium text-indigo-600">{{ now()->format('l, F j') }}</p>
    <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">System Overview</h1>
    <p class="mt-1 text-gray-500">Monitor tickets, manage users, and keep operations running smoothly.</p>
</div>

{{-- Stats --}}
<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
    @include('partials.dashboard.stat-card', [
        'label' => 'Total Tickets',
        'value' => $totalTickets,
        'color' => 'blue',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
    ])
    @include('partials.dashboard.stat-card', [
        'label' => 'Open',
        'value' => $openTickets,
        'hint' => 'Needs attention',
        'color' => 'amber',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    ])
    @include('partials.dashboard.stat-card', [
        'label' => 'Unassigned',
        'value' => $unassignedTickets,
        'color' => 'rose',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>',
    ])
    @include('partials.dashboard.stat-card', [
        'label' => 'Overdue ETA',
        'value' => $overdueEtaTickets,
        'hint' => 'Past estimated completion',
        'color' => 'orange',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
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
        'color' => 'slate',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
    ])
    @include('partials.dashboard.stat-card', [
        'label' => 'Cancelled',
        'value' => $cancelledTickets,
        'color' => 'rose',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
    ])
    @include('partials.dashboard.stat-card', [
        'label' => 'Technicians',
        'value' => $technicians,
        'color' => 'violet',
        'icon' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    ])
</div>

<div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
    {{-- Quick actions --}}
    <div class="lg:col-span-1 space-y-4">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400">Quick Actions</h2>

        <a href="{{ route('admin.unassigned-tickets') }}" class="group flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm hover:border-orange-200 hover:shadow-md transition">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-500 text-white shadow-sm group-hover:scale-105 transition-transform">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-900 group-hover:text-orange-600 transition">Assign Tickets</p>
                <p class="text-sm text-gray-500">{{ $unassignedTickets }} waiting</p>
            </div>
            @if($unassignedTickets > 0)
                <span class="flex h-6 min-w-[1.5rem] items-center justify-center rounded-full bg-orange-100 px-2 text-xs font-bold text-orange-700">{{ $unassignedTickets }}</span>
            @endif
        </a>

        <a href="{{ route('admin.users') }}" class="group flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm hover:border-blue-200 hover:shadow-md transition">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-500 text-white shadow-sm group-hover:scale-105 transition-transform">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div>
                <p class="font-semibold text-gray-900 group-hover:text-blue-600 transition">Manage Users</p>
                <p class="text-sm text-gray-500">Roles & permissions</p>
            </div>
        </a>

        <a href="{{ route('admin.settings.index') }}" class="group flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm hover:border-indigo-200 hover:shadow-md transition">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-500 text-white shadow-sm group-hover:scale-105 transition-transform">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <p class="font-semibold text-gray-900 group-hover:text-indigo-600 transition">Site Settings</p>
                <p class="text-sm text-gray-500">Branding, SEO & automation</p>
            </div>
        </a>

        <a href="{{ route('admin.categories.index') }}" class="group flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm hover:border-emerald-200 hover:shadow-md transition">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-sm group-hover:scale-105 transition-transform">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            </div>
            <div>
                <p class="font-semibold text-gray-900 group-hover:text-emerald-600 transition">Service Categories</p>
                <p class="text-sm text-gray-500">Repair types</p>
            </div>
        </a>

        <a href="{{ route('tickets.index') }}" class="group flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm hover:border-violet-200 hover:shadow-md transition">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-violet-500 text-white shadow-sm group-hover:scale-105 transition-transform">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            </div>
            <div>
                <p class="font-semibold text-gray-900 group-hover:text-violet-600 transition">All Tickets</p>
                <p class="text-sm text-gray-500">Full ticket list</p>
            </div>
        </a>
    </div>

    {{-- Recent activity --}}
    <div class="lg:col-span-2 rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Recent Activity</h2>
                <p class="text-sm text-gray-500">Latest tickets across the system</p>
            </div>
        </div>

        @forelse($recentTickets as $ticket)
            <a href="{{ route('tickets.show', $ticket) }}" class="group flex items-center gap-4 border-b border-gray-50 px-6 py-4 last:border-0 hover:bg-gray-50/80 transition">
                @include('partials.user-avatar', ['user' => $ticket->customer, 'size' => 'md-sm'])
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-gray-900 group-hover:text-indigo-600 transition">{{ $ticket->customer->name }}</p>
                    <p class="truncate text-sm text-gray-500">#{{ $ticket->id }} · {{ Str::limit($ticket->issue_summary, 45) }}</p>
                </div>
                <div class="hidden sm:block shrink-0">
                    @include('partials.dashboard.status-badge', ['status' => $ticket->displayStatus()])
                </div>
                <div class="hidden md:flex shrink-0 items-center gap-2 text-right">
                    @if($ticket->technician)
                        @include('partials.user-avatar', ['user' => $ticket->technician, 'size' => 'xs'])
                        <div>
                            <p class="text-sm font-medium text-gray-700">{{ $ticket->technician->name }}</p>
                            <p class="text-xs text-gray-400">Technician</p>
                        </div>
                    @else
                        <p class="text-sm text-rose-500 font-medium">Unassigned</p>
                    @endif
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-xs text-gray-400">{{ $ticket->created_at->format('M d') }}</p>
                    <p class="text-xs text-gray-300">{{ $ticket->created_at->format('g:i A') }}</p>
                </div>
            </a>
        @empty
            <div class="px-6 py-12 text-center text-gray-500">
                <p>No tickets in the system yet.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
