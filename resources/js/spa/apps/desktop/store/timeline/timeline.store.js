import { defineStore } from 'pinia';
import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';

const useTimelineStore = defineStore('timeline_store', {
    // This is used to tell the postDeleteListener to listen to this store
    // This is used only for timeline stores, on desktop and mobile with the same logic.
    deleteAware: true,
    state: function() {
		return {
			posts: [],
            update: [],
            usePublic: false,
			filter: {
				page: 1,
                onset: null,
                sort: 'hot'
			}
		}
	},
    actions: {
        updateFeed: async function() {
            let state = this;

            const api = state.usePublic ? ZESTEXAPI().publicApi() : ZESTEXAPI().userTimeline();

            await api.params({
                filter: {
                    onset: state.posts.at(0).id
                }
            }).getFrom(state.usePublic ? 'timeline/feed' : 'feed').then((response) => {
                state.update = response.data.data;
            }).catch((error) => {
                if(error.response) {
                    state.update = [];
                }
            });
        },
        applyUpdate: function() {
            // Check if post already exists before adding
            // Otherwise, add the post to the beginning of the posts array.

            this.update.forEach((postItem) => {
                const exists = this.posts.slice(0, this.update.length).some((post) => {
                    return post.id === postItem.id;
                });
                
                if (! exists) {
                    this.posts.unshift(postItem);
                }
            });

            this.update = [];
        },
        initialLoad: async function() {
            let state = this;

            if (! state.posts.length) {
                await this.load().then(async function(response) {
                    const data = response?.data?.data || [];
                    state.posts = data;

                    if (! state.usePublic && data.length === 0 && state.filter.page === 1) {
                        state.usePublic = true;
                        try {
                            const retry = await state.load();
                            state.posts = retry.data.data;
                        } catch (e) {
                            // keep empty
                        }
                    }
                }).catch(async function(error) {
                    const status = error?.response?.status;
                    if ((status === 401 || status === 419 || (status && status >= 500)) && ! state.usePublic) {
                        state.usePublic = true;
                        try {
                            const retry = await state.load();
                            state.posts = retry.data.data;
                            return;
                        } catch (e) {
                            // fall through to empty
                        }
                    }
                    state.posts = [];
                });
            }
        },
        loadNextPage: async function() {
            this.filter.page += 1;

            return await this.load();
        },
        load: async function() {
            const api = this.usePublic ? ZESTEXAPI().publicApi() : ZESTEXAPI().userTimeline();

            return await api.params({
                filter: this.filter
            }).getFrom(this.usePublic ? 'timeline/feed' : 'feed');
        },
        appendPosts: function(posts) {
            this.posts = this.posts.concat(posts);
        },
        prependPost: function(postData) {
            this.posts.unshift(postData);

            return this.posts;
        },
        removePost: function(postId) {
            let postIndex = this.posts.findIndex((item) => {
                return item.id == postId;
            });

            if(postIndex !== -1) {
                this.posts.splice(postIndex, 1);
            }
        },
        setPostMedia: function(mediaData) {
            const postItem = this.posts.find((item) => {
                return item.id == mediaData.mediaable_id;
            });

            if(postItem?.relations?.media?.length) {
                let mediaIndex = postItem.relations.media.findIndex((item) => {
                    return item.id == mediaData.id;
                });

                if(mediaIndex !== -1) {
                    postItem.relations.media[mediaIndex] = mediaData;
                }
            }
        },
        setPostPollData: function(pollData) {
            const postItem = this.posts.find((item) => {
                return item.id == pollData.post_id;
            });

            if(postItem?.relations?.poll) {
                postItem.relations.poll = pollData;
            }
        },
        setPostCommentData: function(commentData) {
            const postItem = this.posts.find((item) => {
                return item.id == commentData.post_id;
            });

            if(! postItem) {
                return;
            }

            if(! postItem.relations) {
                postItem.relations = {};
            }

            if(! Array.isArray(postItem.relations.comments)) {
                postItem.relations.comments = [];
            }

            const exists = postItem.relations.comments.some((item) => {
                return Number(item.id) === Number(commentData.id);
            });

            if(! exists) {
                postItem.relations.comments.unshift({
                    id: commentData.id,
                    content: commentData.content,
                    user: commentData.user || {}
                });
            }

            if(postItem.relations.comments.length > 5) {
                postItem.relations.comments = postItem.relations.comments.slice(0, 5);
            }

            if(! postItem.comments_count) {
                postItem.comments_count = { raw: 0, formatted: '0' };
            }

            const currentRaw = Number(postItem.comments_count.raw || 0);
            const nextRaw = currentRaw + (exists ? 0 : 1);
            postItem.comments_count.raw = nextRaw;
            postItem.comments_count.formatted = String(nextRaw);
        }
    }
});

export { useTimelineStore };
