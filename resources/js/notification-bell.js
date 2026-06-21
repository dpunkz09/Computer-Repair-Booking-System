const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export function notificationBell(config) {
    return {
        open: false,
        loading: false,
        unreadCount: 0,
        notifications: [],
        feedUrl: config.feedUrl,
        markAllReadUrl: config.markAllReadUrl,

        init() {
            this.fetchNotifications();
        },

        toggle() {
            this.open = ! this.open;

            if (this.open) {
                this.fetchNotifications();
            }
        },

        async fetchNotifications() {
            this.loading = true;

            try {
                const response = await fetch(this.feedUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (! response.ok) {
                    return;
                }

                const data = await response.json();
                this.unreadCount = data.unread_count;
                this.notifications = data.notifications;
            } catch {
                // Ignore transient network errors.
            } finally {
                this.loading = false;
            }
        },

        async markAllRead() {
            try {
                const response = await fetch(this.markAllReadUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });

                if (response.ok) {
                    this.unreadCount = 0;
                    this.notifications = this.notifications.map((notification) => ({
                        ...notification,
                        read: true,
                    }));
                }
            } catch {
                // Ignore transient network errors.
            }
        },

        async markRead(notification) {
            if (notification.url) {
                window.location.href = notification.url;

                return;
            }

            try {
                const response = await fetch(`/notifications/${notification.id}/read`, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });

                if (response.ok) {
                    await this.fetchNotifications();
                }
            } catch {
                // Ignore transient network errors.
            }
        },
    };
}
