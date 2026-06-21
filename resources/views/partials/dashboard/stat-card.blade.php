@php
    $themes = [
        'blue' => ['wrap' => 'from-blue-500/10 to-blue-600/5', 'icon' => 'bg-blue-500 text-white', 'value' => 'text-blue-600'],
        'amber' => ['wrap' => 'from-amber-500/10 to-amber-600/5', 'icon' => 'bg-amber-500 text-white', 'value' => 'text-amber-600'],
        'emerald' => ['wrap' => 'from-emerald-500/10 to-emerald-600/5', 'icon' => 'bg-emerald-500 text-white', 'value' => 'text-emerald-600'],
        'rose' => ['wrap' => 'from-rose-500/10 to-rose-600/5', 'icon' => 'bg-rose-500 text-white', 'value' => 'text-rose-600'],
        'orange' => ['wrap' => 'from-orange-500/10 to-orange-600/5', 'icon' => 'bg-orange-500 text-white', 'value' => 'text-orange-600'],
        'violet' => ['wrap' => 'from-violet-500/10 to-violet-600/5', 'icon' => 'bg-violet-500 text-white', 'value' => 'text-violet-600'],
        'slate' => ['wrap' => 'from-slate-500/10 to-slate-600/5', 'icon' => 'bg-slate-600 text-white', 'value' => 'text-slate-700'],
    ];
    $theme = $themes[$color ?? 'blue'];
@endphp
<div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
    <div class="absolute inset-0 bg-gradient-to-br {{ $theme['wrap'] }} pointer-events-none"></div>
    <div class="relative flex items-start justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
            <p class="mt-2 text-3xl font-bold tracking-tight {{ $theme['value'] }}">{{ $value }}</p>
            @if(!empty($hint))
                <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
            @endif
        </div>
        <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $theme['icon'] }} shadow-sm">
            {!! $icon !!}
        </div>
    </div>
</div>
