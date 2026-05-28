import { defineStore } from 'pinia';
import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';

const useWalletStore = defineStore('wallet_store', {
    state: function() {
		return {
			walletData: null,
			transactions: {
				today: [],
				thisWeek: [],
				thisMonth: [],
				other: []
			},
			paymentProviders: [],
			receiverHistory: []
		}
	},
    getters: {
    },
    actions: {
		fetchWalletData: async function() {
			await ZESTEXAPI().wallet().getFrom('data').then((response) => {
				this.walletData = response.data.data;
			}).catch((error) => {
				if(error.response) {
					alert(error.response.data.message);
				}
			});
		},
		fetchPaymentProviders: async function() {
			await ZESTEXAPI().wallet().getFrom('payment/providers').then((response) => {
				this.paymentProviders = response.data.data;
			}).catch((error) => {
				if(error.response) {
					alert(error.response.data.message);
				}
			});
		},
		createDepositPayment: async function(data) {
			return await ZESTEXAPI().wallet().with(data).sendTo('deposit');
		},
		fetchTransactions: async function() {
			await ZESTEXAPI().wallet().getFrom('transactions').then((response) => {
				this.transactions.today = response.data.data.today;
				this.transactions.thisWeek = response.data.data.this_week;
				this.transactions.thisMonth = response.data.data.this_month;
				this.transactions.other = response.data.data.other;
			}).catch((error) => {
				if(error.response) {
					alert(error.response.data.message);
				}
			});
		},
		fetchReceivers: async function(walletNumber) {
			return await ZESTEXAPI().wallet().params({
				wallet_number: walletNumber
			}).getFrom('receiver/find');
		},
		fetchReceiverHistory: async function() {
			if(this.receiverHistory.length) {
				return false;
			}

			await ZESTEXAPI().wallet().getFrom('receiver/history').then((response) => {
				this.receiverHistory = response.data.data;
			}).catch((error) => {
				this.receiverHistory = [];
			});
		},
		makeTransfer: async function(data) {
			return await ZESTEXAPI().wallet().with(data).sendTo('transfer');
		}
    }
});

export { useWalletStore };