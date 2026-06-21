@if($photos->isNotEmpty())
<div x-data="{ lightbox: null }">
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
        @foreach($photos as $photo)
            <button type="button" @click="lightbox = '{{ $photo->url() }}'"
                class="group relative aspect-square overflow-hidden rounded-xl border border-gray-200 bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <img src="{{ $photo->url() }}" alt="Device photo"
                    class="h-full w-full object-cover transition group-hover:scale-105">
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-center justify-center">
                    <svg class="h-8 w-8 text-white opacity-0 group-hover:opacity-100 transition drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                    </svg>
                </div>
            </button>
        @endforeach
    </div>

    <div x-show="lightbox" x-cloak @keydown.escape.window="lightbox = null"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-4" @click.self="lightbox = null">
        <button type="button" @click="lightbox = null" class="absolute top-4 right-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <img :src="lightbox" alt="Device photo enlarged" class="max-h-[90vh] max-w-full rounded-lg shadow-2xl object-contain">
    </div>
</div>
@else
    <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-6 py-10 text-center">
        <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <p class="mt-2 text-sm text-gray-500">No device photos uploaded yet.</p>
    </div>
@endif
