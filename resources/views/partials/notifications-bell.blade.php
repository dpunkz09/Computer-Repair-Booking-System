<div class="relative" x-data="notificationBell({
        feedUrl: @js(route('notifications.index')),
        markAllReadUrl: @js(route('notifications.read-all')),
    })" @keydown.escape.window="open = false">
    <button type="button" @click="toggle()"
        class="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition"
        aria-label="Notifications" aria-expanded="false" :aria-expanded="open">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span x-show="unreadCount > 0" x-cloak
            class="absolute -top-0.5 -right-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"
            x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
    </button>

    <div x-show="open" x-cloak @click.outside="open = false"
        class="absolute right-0 mt-2 w-80 sm:w-96 origin-top-right rounded-xl border border-gray-200 bg-white shadow-xl z-50">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
            <button type="button" x-show="unreadCount > 0" @click="markAllRead()"
                class="text-xs font-medium text-blue-600 hover:text-blue-800">
                Mark all read
            </button>
        </div>

        <div class="max-h-96 overflow-y-auto">
            <template x-if="loading && notifications.length === 0">
                <div class="px-4 py-10 text-center text-sm text-gray-500">Loading notifications...</div>
            </template>

            <template x-if="!loading && notifications.length === 0">
                <div class="px-4 py-10 text-center text-sm text-gray-500">
                    <svg class="mx-auto h-8 w-8 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    No notifications yet
                </div>
            </template>

            <template x-for="notification in notifications" :key="notification.id">
                <button type="button" @click="markRead(notification)"
                    class="w-full text-left px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition"
                    :class="!notification.read ? 'bg-blue-50/50' : ''">
                    <div class="flex items-start gap-3">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                            :class="!notification.read ? 'bg-blue-500' : 'bg-transparent'"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 truncate" x-text="notification.title"></p>
                            <p class="text-xs text-gray-600 mt-0.5 line-clamp-2" x-text="notification.message"></p>
                            <p class="text-xs text-gray-400 mt-1" x-text="notification.created_at"></p>
                        </div>
                    </div>
                </button>
            </template>
        </div>
    </div>
</div>
