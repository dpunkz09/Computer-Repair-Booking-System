<a href="{{ route('home') }}" class="flex items-center gap-2">
    @if($site->logo_url ?? null)
        <img src="{{ $site->logo_url }}" alt="{{ $site->name }}" class="h-9 max-w-[160px] object-contain">
    @else
        <span class="flex h-8 w-8 items-center justify-center rounded-lg text-white text-sm font-bold" style="background-color: {{ $site->primary_color ?? '#2563eb' }}">
            {{ strtoupper(substr($site->name, 0, 1)) }}
        </span>
    @endif
    <span class="text-lg font-bold text-gray-900 hidden sm:block">{{ $site->name ?? 'ComTech Repair' }}</span>
</a>
