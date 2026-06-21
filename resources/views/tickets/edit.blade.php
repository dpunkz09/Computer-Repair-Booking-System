@extends('layouts.app')

@section('title', 'Edit Ticket #' . $ticket->id)

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Update Ticket #{{ $ticket->id }}</h1>

    @if($ticket->isCancelled())
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-4 text-sm text-gray-700">
            This ticket was cancelled and can no longer be edited.
        </div>
        <a href="{{ route('tickets.show', $ticket) }}" class="mt-6 inline-block rounded-lg bg-gray-300 px-6 py-2 font-bold text-gray-700 hover:bg-gray-400">
            Back to ticket
        </a>
    @else
        <form action="{{ route('tickets.update', $ticket) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-6">
                <label for="status" class="block text-gray-700 font-bold mb-2">Status</label>
                <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    @foreach(\App\Support\TicketStatus::WORKFLOW as $status)
                        <option value="{{ $status }}" {{ $ticket->status === $status ? 'selected' : '' }}>
                            {{ \App\Support\TicketStatus::label($status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-6">
                <label for="priority" class="block text-gray-700 font-bold mb-2">Priority</label>
                <select name="priority" id="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="1" {{ $ticket->priority === 1 ? 'selected' : '' }}>⭐ Low</option>
                    <option value="2" {{ $ticket->priority === 2 ? 'selected' : '' }}>⭐⭐ Medium-Low</option>
                    <option value="3" {{ $ticket->priority === 3 ? 'selected' : '' }}>⭐⭐⭐ Medium</option>
                    <option value="4" {{ $ticket->priority === 4 ? 'selected' : '' }}>⭐⭐⭐⭐ High</option>
                    <option value="5" {{ $ticket->priority === 5 ? 'selected' : '' }}>⭐⭐⭐⭐⭐ Urgent</option>
                </select>
            </div>

            <div class="mb-6">
                <label for="estimated_completion_at" class="block text-gray-700 font-bold mb-2">Estimated completion / pickup</label>
                <input type="datetime-local" name="estimated_completion_at" id="estimated_completion_at"
                    value="{{ old('estimated_completion_at', optional($ticket->estimated_completion_at)?->format('Y-m-d\TH:i')) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="mt-1 text-sm text-gray-500">Optional. Visible to the customer on the ticket page.</p>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700">
                    Update Ticket
                </button>
                <a href="{{ route('tickets.show', $ticket) }}" class="bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded-lg hover:bg-gray-400">
                    Cancel
                </a>
            </div>
        </form>
    @endif
</div>
@endsection
