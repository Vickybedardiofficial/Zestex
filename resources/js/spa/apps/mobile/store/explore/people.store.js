import { defineStore } from 'pinia';
import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';
import { useAuthStore } from '@M/store/auth/auth.store.js';

const useExplorePeopleStore = defineStore('mobile_explore_people_store', {
    state: function() {
		return {
			people: [],
			filter: {
				query: '',
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
		makeLoadRequest: async function () {
            const api = this.resolveApi();

            if (this.isGuest()) {
                return await api.params({ filter: this.filter }).getFrom('explore/people');
            }

			return await api.with({
				filter: this.filter
			}).sendTo('people');
		},
		fetchPeople: async function() {
			await this.makeLoadRequest().then((response) => {
				this.people = response.data.data;
			});
		},
		loadMorePeople: async function() {
			return await this.makeLoadRequest().then((response) => {
				let people = response.data.data;
				
				if (people.length) {	
					this.people = this.people.concat(people);
					return true;
				}

				return false;
			}).catch(() => {
				return false;
			});
		},
		getLastPersonId: function() {
			return this.people.at(-1).id;
		},
		resetFilter: function() {
			this.filter = {
				query: '',
				page: 1
			};
		}
    }
});

export { useExplorePeopleStore };
