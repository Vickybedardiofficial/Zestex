import { defineStore } from 'pinia';
import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';

const useRecommendStore = defineStore('recommend_store', {
    state: function() {
		return {
			followRecommendations: [],
		}
	},
    getters: {
    },
    actions: {
		fetchFollowRecommendations: async function() {
			const state = this;

			await ZESTEXAPI().recommendations().getFrom('follow').then((response) => {
				state.followRecommendations = response.data.data;
			});
		}
    }
});

export { useRecommendStore };