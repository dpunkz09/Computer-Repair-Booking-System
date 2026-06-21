@php
    $styles = match($status) {
        'new' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        'assigned' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        'in_progress' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'awaiting_parts' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'resolved' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'closed' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
        'cancelled' => 'bg-gray-100 text-gray-600 ring-gray-500/20',
        default => 'bg-gray-50 text-gray-700 ring-gray-500/20',
    };
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $styles }}">
    {{ ucfirst(str_replace('_', ' ', $status)) }}
</span>
