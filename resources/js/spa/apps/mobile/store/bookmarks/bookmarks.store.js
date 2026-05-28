import { defineStore } from 'pinia';
import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';

const useBookmarksStore = defineStore('mobile_bookmarks_store', {
	state: function() {
		return {
			bookmarks: []
		}
	},
	actions: {
		resetBookmarks: function() {
			this.bookmarks = [];
		},
		fetchBookmarks: async function(filter = {}) {
			await ZESTEXAPI().bookmarks().with(filter).sendTo('bookmarks').then((response) => {
			    this.bookmarks = response.data.data;
			}).catch((error) => {
				this.bookmarks = [];
			});
		}
	}
});

export { useBookmarksStore };