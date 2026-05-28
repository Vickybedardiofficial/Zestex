import { defineStore } from 'pinia';
import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';

const useRecommendStore = defineStore('mobile_recommend_store', {
    actions: {
		fetchFollowRecommendations: async function() {
			return await ZESTEXAPI().recommendations().getFrom('follow').then((response) => {
				return response.data.data;
			}).catch((error) => {
				return [];
			});
		}
    }
});

export { useRecommendStore };