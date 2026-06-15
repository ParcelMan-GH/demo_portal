export function registerAdminNotifications(Alpine) {
    Alpine.data('adminNotifications', (config) => ({
        open: false,
        loading: false,
        unreadCount: 0,
        notifications: [],
        error: null,
        pollTimer: null,

        init() {
            this.fetchNotifications();
            this.pollTimer = window.setInterval(() => this.fetchNotifications(false), 45000);
        },

        destroy() {
            if (this.pollTimer) {
                window.clearInterval(this.pollTimer);
            }
        },

        toggle() {
            this.open = !this.open;

            if (this.open) {
                this.fetchNotifications();
            }
        },

        async fetchNotifications(showLoading = true) {
            if (showLoading) {
                this.loading = true;
            }
            this.error = null;

            try {
                const response = await fetch(config.indexUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Unable to load notifications.');
                }

                const payload = await response.json();
                this.unreadCount = Number(payload?.data?.unread_count || 0);
                this.notifications = payload?.data?.notifications || [];
            } catch (error) {
                this.error = error.message || 'Unable to load notifications.';
            } finally {
                this.loading = false;
            }
        },

        markReadUrl(notification) {
            return config.markReadUrlTemplate.replace('__ID__', notification.id);
        },

        csrfHeaders() {
            return {
                'X-CSRF-TOKEN': config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            };
        },

        async markRead(notification) {
            if (notification.read_at) {
                return;
            }

            const response = await fetch(this.markReadUrl(notification), {
                method: 'POST',
                headers: this.csrfHeaders(),
            });

            if (!response.ok) {
                throw new Error('Unable to mark notification as read.');
            }

            notification.read_at = new Date().toISOString();
            this.unreadCount = Math.max(0, this.unreadCount - 1);
        },

        async markAllRead() {
            const response = await fetch(config.readAllUrl, {
                method: 'POST',
                headers: this.csrfHeaders(),
            });

            if (!response.ok) {
                this.error = 'Unable to mark notifications as read.';
                return;
            }

            const now = new Date().toISOString();
            this.notifications = this.notifications.map((notification) => ({
                ...notification,
                read_at: notification.read_at || now,
            }));
            this.unreadCount = 0;
        },

        async openNotification(notification) {
            try {
                await this.markRead(notification);
            } catch (error) {
                this.error = error.message || 'Unable to open notification.';
                return;
            }

            this.open = false;

            if (notification.url) {
                window.location.href = notification.url;
            }
        },

        badgeText() {
            return this.unreadCount > 99 ? '99+' : String(this.unreadCount);
        },
    }));
}
