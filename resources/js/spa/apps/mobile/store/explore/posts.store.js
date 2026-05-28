import { defineStore } from 'pinia';
import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';
import { useAuthStore } from '@M/store/auth/auth.store.js';

const useExplorePostsStore = defineStore('mobile_explore_posts_store', {
	deleteAware: true,
    state: function() {
		return {
			updateAttempts: 0,
			posts: [],
			update: [],
			filter: {
				page: 1
			}
		}
	},
    actions: {
        resolveApi: function() {
            const authStore = useAuthStore();
            return authStore.authCheck ? ZESTEXAPI().explore() : ZESTEXAPI().publicApi();
        },
        isGuest: function() {
            const authStore = useAuthStore();
            return ! authStore.authCheck;
        },
		updateFeed: async function() {
            const api = this.resolveApi();
            const onsetId = this.posts.at(0)?.id || 0;

            const request = this.isGuest()
                ? api.params({ filter: { onset: onsetId } }).getFrom('explore/posts')
                : api.params({ filter: { onset: onsetId } }).sendTo('posts');

            await request.then((response) => {
                this.update = response.data.data;
            }).catch((error) => {
                if(error.response) {
                    state.update = [];
                }
            });
        },
		applyUpdate: function() {
            this.update.forEach((postItem) => {
                // Check if post already exists before adding
                const exists = this.posts.slice(0, this.update.length).some((post) => {
					return post.id === postItem.id;
				});

                if (! exists) {
                    this.posts.unshift(postItem);
                }
            });

            this.update = [];
        },
		makeLoadRequest: async function () {
            const api = this.resolveApi();

            if (this.isGuest()) {
                return await api.params({ filter: this.filter }).getFrom('explore/posts');
            }

			return await api.with({
				filter: this.filter
			}).sendTo('posts');
		},
		fetchPosts: async function() {
			await this.makeLoadRequest().then((response) => {
				this.posts = response.data.data;
			});
		},
		loadMorePosts: async function() {
			return await this.makeLoadRequest().then((response) => {
				let posts = response.data.data;
				
				if (posts.length) {	
					this.posts = this.posts.concat(posts);
					return true;
				}

				return false;
			}).catch(() => {
				return false;
			});
		},
		getLastPostId: function() {
			return this.posts.at(-1).id;
		},
		resetFilter: function() {
			this.filter = {
				page: 1
			};
		}
    }
});

export { useExplorePostsStore };
