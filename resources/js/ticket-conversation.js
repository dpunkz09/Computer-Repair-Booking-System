const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export function ticketConversation(config) {
    return {
        ticketId: config.ticketId,
        feedUrl: config.feedUrl,
        storeUrl: config.storeUrl,
        canPostInternal: config.canPostInternal,
        comments: config.initialComments ?? [],
        commentText: '',
        isInternalNote: false,
        sending: false,
        pollTimer: null,
        pollingInterval: 5000,
        consecutiveErrors: 0,
        errorMessage: '',
        lastCount: 0,
        boundVisibilityHandler: null,

        init() {
            this.boundVisibilityHandler = () => this.handleVisibilityChange();
            document.addEventListener('visibilitychange', this.boundVisibilityHandler);

            this.fetchComments().then(() => {
                this.$nextTick(() => this.scrollToBottom());
            });
            this.schedulePoll();
        },

        destroy() {
            if (this.pollTimer) {
                clearTimeout(this.pollTimer);
            }

            if (this.boundVisibilityHandler) {
                document.removeEventListener('visibilitychange', this.boundVisibilityHandler);
            }
        },

        handleVisibilityChange() {
            if (document.visibilityState === 'hidden') {
                if (this.pollTimer) {
                    clearTimeout(this.pollTimer);
                    this.pollTimer = null;
                }

                return;
            }

            this.fetchComments();
            this.schedulePoll();
        },

        schedulePoll() {
            if (this.pollTimer) {
                clearTimeout(this.pollTimer);
            }

            if (document.visibilityState === 'hidden') {
                return;
            }

            this.pollTimer = setTimeout(async () => {
                await this.fetchComments();
                this.schedulePoll();
            }, this.pollingInterval);
        },

        async fetchComments() {
            try {
                const response = await fetch(this.feedUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (! response.ok) {
                    this.registerPollError();

                    return;
                }

                this.consecutiveErrors = 0;
                this.pollingInterval = 5000;

                const data = await response.json();
                const previousCount = this.lastCount;
                this.comments = data.comments;
                this.lastCount = this.comments.length;

                if (this.comments.length > previousCount) {
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch {
                this.registerPollError();
            }
        },

        registerPollError() {
            this.consecutiveErrors += 1;
            this.pollingInterval = Math.min(30000, 5000 * (2 ** this.consecutiveErrors));
        },

        async sendMessage() {
            if (! this.commentText.trim() || this.sending) {
                return;
            }

            this.sending = true;
            this.errorMessage = '';

            try {
                const body = new FormData();
                body.append('comment_text', this.commentText.trim());
                if (this.canPostInternal && this.isInternalNote) {
                    body.append('is_internal_note', '1');
                }

                const response = await fetch(this.storeUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body,
                });

                const data = await response.json();

                if (! response.ok) {
                    this.errorMessage = data.message
                        ?? data.errors?.comment_text?.[0]
                        ?? 'Unable to send message.';

                    return;
                }

                this.commentText = '';
                this.isInternalNote = false;
                this.comments = data.comments;
                this.lastCount = this.comments.length;
                this.$nextTick(() => this.scrollToBottom());
            } catch {
                this.errorMessage = 'Unable to send message. Please try again.';
            } finally {
                this.sending = false;
            }
        },

        scrollToBottom() {
            const el = this.$refs.messageList;
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        },
    };
}
