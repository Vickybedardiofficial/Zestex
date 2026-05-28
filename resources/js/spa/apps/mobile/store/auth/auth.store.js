import { defineStore } from 'pinia';
import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';

const useAuthStore = defineStore('mobile_auth_store', {
    state: function() {
		return {
            user: null,
		}
	},
    getters: {
        authCheck: function() {
            return this.user !== null;
        },
        userData: function(state) {
            return this.user;
        }
    },
    actions: {
        setUser: function(userData) {
           this.user = userData;
        },
        setProperty: function(key, value) {
            this.user[key] = value;
        },
        logoutUser: async function() {
            return await ZESTEXAPI().userAuth().sendTo('logout');
        }
    }
});

export { useAuthStore };