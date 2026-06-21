<section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
    x-data="ticketConversation({
        ticketId: {{ $ticket->id }},
        feedUrl: @js(route('tickets.comments.feed', $ticket)),
        storeUrl: @js(route('tickets.comments.store', $ticket)),
        canPostInternal: @js(Auth::user()->role !== 'customer'),
        initialComments: @js($initialComments ?? []),
    })"
    x-init="init()"
    @destroy="destroy()">
    <div class="mb-4 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Conversation</h2>
            <p class="text-xs text-gray-500">Messages refresh automatically every few seconds.</p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            Live
        </span>
    </div>

    <div x-ref="messageList" class="mb-5 max-h-[420px] space-y-4 overflow-y-auto rounded-xl border border-gray-100 bg-gray-50/80 p-4">
        <template x-if="comments.length === 0">
            <div class="py-8 text-center text-sm text-gray-500">
                No messages yet. Send the first message below.
            </div>
        </template>
        <template x-for="comment in comments" :key="comment.id">
            <div class="flex gap-3" :class="comment.is_internal_note ? 'opacity-90' : ''">
                <template x-if="comment.author_avatar">
                    <img :src="comment.author_avatar" :alt="comment.author_name" class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-white shadow-sm">
                </template>
                <template x-if="!comment.author_avatar">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-xs font-bold text-white ring-2 ring-white shadow-sm" x-text="comment.author_initials"></span>
                </template>
                <div class="min-w-0 flex-1 rounded-xl px-4 py-3 ring-1"
                    :class="comment.is_internal_note ? 'bg-rose-50 ring-rose-100' : 'bg-white ring-slate-100'">
                    <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-900" x-text="comment.author_name"></span>
                            <span class="text-xs text-gray-500" x-text="comment.author_role"></span>
                            <template x-if="comment.is_internal_note">
                                <span class="rounded-full bg-rose-200 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-800">Internal</span>
                            </template>
                        </div>
                        <span class="text-xs text-gray-400" x-text="comment.time_ago"></span>
                    </div>
                    <p class="whitespace-pre-wrap text-sm text-gray-700" x-text="comment.body"></p>
                </div>
            </div>
        </template>
    </div>

    @if($ticket->isCancelled())
        <p class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
            This ticket was cancelled. The conversation is now read-only.
        </p>
    @else
        <form @submit.prevent="sendMessage()" class="rounded-xl border border-gray-100 bg-gray-50 p-4">
            <label for="live-comment" class="block text-sm font-medium text-gray-700 mb-2">Write a message</label>
            <textarea x-model="commentText" id="live-comment" rows="3"
                placeholder="Ask a question or share an update..."
                class="w-full rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-3"></textarea>

            <template x-if="canPostInternal">
                <label class="mb-3 flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" x-model="isInternalNote" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Internal note (hidden from customer)
                </label>
            </template>

            <template x-if="errorMessage">
                <p class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700" x-text="errorMessage"></p>
            </template>

            <button type="submit" :disabled="sending || !commentText.trim()"
                class="inline-flex items-center gap-1.5 rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-white transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                <span x-text="sending ? 'Sending...' : 'Send Message'"></span>
            </button>
        </form>
    @endif
</section>
