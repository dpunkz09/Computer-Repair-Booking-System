@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->id)

@section('content')
<div class="mx-auto max-w-5xl">
    {{-- Breadcrumb & actions --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-800 transition mb-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                All tickets
            </a>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Ticket #{{ $ticket->id }}</h1>
                @include('partials.dashboard.status-badge', ['status' => $ticket->displayStatus()])
            </div>
            <p class="mt-1 text-sm text-gray-500">Submitted {{ $ticket->created_at->format('M j, Y \a\t g:i A') }} · {{ $ticket->created_at->diffForHumans() }}</p>
            @if($ticket->isCancelled())
                <p class="mt-2 text-sm font-medium text-gray-600">Cancelled {{ $ticket->cancelled_at->format('M j, Y g:i A') }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            @can('update', $ticket)
                <a href="{{ route('tickets.edit', $ticket) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Status
                </a>
            @endcan
            @if(Auth::user()->role === 'admin' && $ticket->technician_id && ! $ticket->isCancelled())
                <form action="{{ route('admin.unassign-ticket', $ticket) }}" method="POST" onsubmit="return confirm('Unassign this ticket?')">
                    @csrf
                    <button type="submit" class="rounded-xl border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-700 hover:bg-orange-100 transition">Unassign</button>
                </form>
            @endif
            @can('cancel', $ticket)
                <form action="{{ route('tickets.cancel', $ticket) }}" method="POST" onsubmit="return confirm('Cancel this repair request? This cannot be undone.')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel Request
                    </button>
                </form>
            @endcan
            @can('delete', $ticket)
                <form action="{{ route('tickets.destroy', $ticket) }}" method="POST" onsubmit="return confirm('Delete this ticket permanently?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100 transition">Delete</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Main column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Issue card --}}
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900 mb-1">{{ $ticket->issue_summary }}</h2>
                <p class="text-sm text-gray-500 mb-4">Issue description</p>
                <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $ticket->description }}</p>
            </section>

            @can('updateDetails', $ticket)
                <section class="rounded-2xl border border-blue-100 bg-blue-50/40 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900 mb-1">Edit booking details</h2>
                    <p class="text-sm text-gray-500 mb-4">Update device or issue information while your ticket is still new.</p>
                    <form action="{{ route('tickets.details.update', $ticket) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label for="device_type" class="block text-sm font-medium text-gray-700 mb-1">Device type</label>
                                <input type="text" name="device_type" id="device_type" value="{{ old('device_type', $ticket->device_type) }}" required
                                    class="w-full rounded-lg border-gray-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="brand" class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                                <input type="text" name="brand" id="brand" value="{{ old('brand', $ticket->brand) }}" required
                                    class="w-full rounded-lg border-gray-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="os" class="block text-sm font-medium text-gray-700 mb-1">Operating system</label>
                                <input type="text" name="os" id="os" value="{{ old('os', $ticket->os) }}" required
                                    class="w-full rounded-lg border-gray-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                        <div>
                            <label for="issue_summary" class="block text-sm font-medium text-gray-700 mb-1">Issue summary</label>
                            <input type="text" name="issue_summary" id="issue_summary" value="{{ old('issue_summary', $ticket->issue_summary) }}" required maxlength="255"
                                class="w-full rounded-lg border-gray-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" id="description" rows="4" required maxlength="10000"
                                class="w-full rounded-lg border-gray-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $ticket->description) }}</textarea>
                        </div>
                        <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:brightness-105">
                            Save changes
                        </button>
                    </form>
                </section>
            @endcan

            {{-- Device photos --}}
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Device Photos</h2>
                        <p class="text-sm text-gray-500">{{ $ticket->photos->count() }} photo{{ $ticket->photos->count() !== 1 ? 's' : '' }} uploaded</p>
                    </div>
                </div>

                @include('partials.tickets.photo-gallery', ['photos' => $ticket->photos])

                @if(Auth::user()->role === 'customer' && $ticket->customer_id === Auth::id() && $ticket->photos->count() < 8 && ! $ticket->isCancelled())
                    <div class="mt-5 border-t border-gray-100 pt-5">
                        <form action="{{ route('tickets.photos.store', $ticket) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            @csrf
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Add more photos</label>
                                <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required
                                    class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                            <button type="submit" class="rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-105 shrink-0">
                                Upload
                            </button>
                        </form>
                    </div>
                @endif
            </section>

            {{-- Live conversation --}}
            @include('partials.tickets.conversation')
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-4">Device</h3>
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Type & Brand</dt>
                        <dd class="mt-0.5 font-semibold text-gray-900">{{ $ticket->device_type }} · {{ $ticket->brand }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Operating System</dt>
                        <dd class="mt-0.5 font-semibold text-gray-900">{{ $ticket->os }}</dd>
                    </div>
                    @if($ticket->serviceCategory)
                        <div>
                            <dt class="text-gray-500">Service Category</dt>
                            <dd class="mt-0.5 font-semibold text-gray-900">{{ $ticket->serviceCategory->name }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-4">Ticket Info</h3>
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Priority</dt>
                        <dd class="mt-1">@include('partials.dashboard.priority', ['priority' => $ticket->priority])</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Customer</dt>
                        <dd class="mt-1 flex items-center gap-2">
                            @include('partials.user-avatar', ['user' => $ticket->customer, 'size' => 'sm'])
                            <span class="font-semibold text-gray-900">{{ $ticket->customer->name }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Estimated completion</dt>
                        <dd class="mt-1">
                            @if($ticket->estimated_completion_at)
                                <span class="font-semibold text-gray-900">{{ $ticket->estimated_completion_at->format('M j, Y g:i A') }}</span>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $ticket->estimated_completion_at->diffForHumans() }}</p>
                            @elseif($ticket->isCancelled())
                                <span class="text-gray-500">—</span>
                            @else
                                <span class="text-gray-500">Not set yet</span>
                            @endif
                        </dd>
                    </div>
                    @can('setEta', $ticket)
                        <div class="border-t border-gray-100 pt-4">
                            <form action="{{ route('tickets.eta.update', $ticket) }}" method="POST" class="space-y-3">
                                @csrf
                                @method('PATCH')
                                <label for="estimated_completion_at" class="block text-xs font-medium text-gray-600">Set pickup / completion ETA</label>
                                <input type="datetime-local" name="estimated_completion_at" id="estimated_completion_at"
                                    value="{{ old('estimated_completion_at', optional($ticket->estimated_completion_at)?->format('Y-m-d\TH:i')) }}"
                                    class="w-full rounded-lg border-gray-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <div class="flex flex-wrap gap-2">
                                    <button type="submit" class="rounded-lg bg-brand px-3 py-1.5 text-xs font-semibold text-white">Save ETA</button>
                                    @if($ticket->estimated_completion_at)
                                        <button type="submit" name="estimated_completion_at" value="" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50">Clear</button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    @endcan
                    @if(Auth::user()->role === 'customer' && $ticket->canBeCancelledByCustomer())
                        <div class="rounded-xl border border-amber-100 bg-amber-50 px-3 py-2.5 text-xs text-amber-900">
                            You can cancel while the ticket is <strong>New</strong> or <strong>Assigned</strong> and before a technician starts work.
                        </div>
                    @endif
                    <div>
                        <dt class="text-gray-500">Technician</dt>
                        <dd class="mt-1">
                            @if($ticket->technician)
                                <div class="flex items-center gap-2">
                                    @include('partials.user-avatar', ['user' => $ticket->technician, 'size' => 'sm'])
                                    <span class="font-semibold text-gray-900">{{ $ticket->technician->name }}</span>
                                </div>
                            @else
                                <span class="font-semibold text-rose-500">Awaiting assignment</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            @if($ticket->photos->isNotEmpty())
                <section class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm lg:hidden">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Preview</p>
                    <img src="{{ $ticket->photos->first()->url() }}" alt="Device" class="w-full rounded-xl object-cover aspect-video">
                </section>
            @endif
        </div>
    </div>
</div>

@endsection
