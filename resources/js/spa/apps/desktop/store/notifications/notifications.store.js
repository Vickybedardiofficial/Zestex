import { defineStore } from 'pinia';
import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';

const useNotificationsStore = defineStore('notifications_store', {
    state: function() {
		return {
			isOpen: false,
			unreadCount: {
				formatted: 0,
				raw: 0
			},
			notifications: []
		}
	},
    actions: {
		openNotifications: function() {
			this.isOpen = true;
		},
		closeNotifications: function() {
			this.isOpen = false;
		},
		fetchNotifications: async function(type = 'all') {
			await ZESTEXAPI().notifications().getFrom(type).then((response) => {
				const payload = response?.data?.data;
				const list = Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : []);
				this.notifications = list.filter((item) => item && typeof item === 'object');
			}).catch(() => {
				this.notifications = [];
			});
		},
		fetchUnreadCount: function() {
			ZESTEXAPI().notifications().getFrom('unread/count').then((response) => {
				this.unreadCount = response.data.data;
			}).catch(() => {
				this.unreadCount = {
					formatted: 0,
					raw: 0
				};
			});
		},
		deleteNotification: function(notificationId) {
			ZESTEXAPI().notifications().with({
				notification_id: notificationId
			}).delete('delete');

			this.notifications = this.notifications.filter((notification) => notification.id !== notificationId);
		},
		setUnreadNotificationsCount: function(unreadCount) {
			const raw = Number(unreadCount?.raw ?? unreadCount ?? 0);
			const formatted = unreadCount?.formatted ?? raw;

			this.unreadCount = {
				raw: Number.isFinite(raw) ? raw : 0,
				formatted: formatted ?? 0
			};
		}
    }
});

export { useNotificationsStore };
