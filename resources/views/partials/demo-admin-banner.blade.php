@auth
    @if(Auth::user()->isDemoAdmin())
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm">
                    <p class="font-semibold">Demo admin mode</p>
                    <p class="mt-0.5 text-amber-800">You can explore tickets and assignments, but site settings, user roles, categories, and destructive actions are read-only.</p>
                </div>
            </div>
        </div>
    @endif
@endauth
