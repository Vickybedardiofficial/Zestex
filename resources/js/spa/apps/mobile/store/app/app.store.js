import { defineStore } from 'pinia';
import { useRouter } from 'vue-router';
import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';
import { useAuthStore } from '@M/store/auth/auth.store.js';

const useAppStore = defineStore('mobile_app_store', {
    state: () => {
        return {
            appData: null
        };
    },
    actions: {
        bootstrapApplication: async function() {
            let state = this;

            const authStore = useAuthStore();

            const router = useRouter();

            await fetch('sanctum/csrf-cookie', {
                method: 'GET',
                credentials: 'include'
            });

            await ZESTEXAPI().bootstrap().getFrom('bootstrap').then(function(response) {
                state.appData = response.data.data;
                authStore.setUser(state.appData.auth.user);
            }).catch(function(error) {
                if (error.response && [401, 419].includes(error.response.status)) {
                    ZESTEXAPI().publicApi().getFrom('bootstrap').then(function(response) {
                        state.appData = response.data.data;
                        authStore.setUser(null);
                    }).catch(function() {
                        router.push({
                            name: 'bootstrap_error'
                        });
                    });
                    return;
                }

                if (error.response) {
                    router.push({
                        name: 'bootstrap_error'
                    });
                }
            });
        }
    }
});

export { useAppStore };
