<form method="GET" action="{{ route('tickets.index') }}" class="mb-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex-1">
            <label for="q" class="block text-sm font-medium text-gray-700 mb-1.5">Search</label>
            <input type="search" name="q" id="q" value="{{ $filters['q'] ?? '' }}"
                placeholder="Ticket #, issue, device, brand..."
                class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 flex-1">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                <select name="status" id="status" class="w-full rounded-xl border-gray-200 bg-gray-50 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All statuses</option>
                    @foreach(\App\Support\TicketStatus::FILTERABLE as $status)
                        <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                            {{ \App\Support\TicketStatus::label($status) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="priority" class="block text-sm font-medium text-gray-700 mb-1.5">Priority</label>
                <select name="priority" id="priority" class="w-full rounded-xl border-gray-200 bg-gray-50 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All priorities</option>
                    @for($p = 1; $p <= 5; $p++)
                        <option value="{{ $p }}" {{ (string) ($filters['priority'] ?? '') === (string) $p ? 'selected' : '' }}>Priority {{ $p }}</option>
                    @endfor
                </select>
            </div>
            @if(Auth::user()->isAdmin())
                <div>
                    <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-1.5">Customer</label>
                    <select name="customer_id" id="customer_id" class="w-full rounded-xl border-gray-200 bg-gray-50 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All customers</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (string) ($filters['customer_id'] ?? '') === (string) $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="technician_id" class="block text-sm font-medium text-gray-700 mb-1.5">Technician</label>
                    <select name="technician_id" id="technician_id" class="w-full rounded-xl border-gray-200 bg-gray-50 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All technicians</option>
                        @foreach($technicians as $technician)
                            <option value="{{ $technician->id }}" {{ (string) ($filters['technician_id'] ?? '') === (string) $technician->id ? 'selected' : '' }}>
                                {{ $technician->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1.5">From date</label>
                <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] ?? '' }}"
                    class="w-full rounded-xl border-gray-200 bg-gray-50 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1.5">To date</label>
                <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] ?? '' }}"
                    class="w-full rounded-xl border-gray-200 bg-gray-50 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="sort" class="block text-sm font-medium text-gray-700 mb-1.5">Sort by</label>
                <select name="sort" id="sort" class="w-full rounded-xl border-gray-200 bg-gray-50 px-3 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="priority" {{ ($filters['sort'] ?? 'priority') === 'priority' ? 'selected' : '' }}>Priority</option>
                    <option value="newest" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Newest first</option>
                    <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Oldest first</option>
                </select>
            </div>
        </div>
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:brightness-105">
            Apply filters
        </button>
        <a href="{{ route('tickets.index') }}" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
            Clear
        </a>
    </div>
</form>
