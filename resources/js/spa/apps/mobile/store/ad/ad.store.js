import { defineStore } from 'pinia';
import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';

const useAdStore = defineStore('mobile_ad_store', {
    actions: {
        fetchAd: async function() {
            return await ZESTEXAPI().ads().params({
				prev_ad_id: this.ad ? this.ad.id : null
			}).getFrom('ad').then((response) => {
                return response.data.data;
            }).catch((error) => {
                return null;
            });
        }
    }
});

export { useAdStore };