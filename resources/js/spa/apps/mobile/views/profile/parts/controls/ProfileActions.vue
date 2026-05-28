<template>
	<div v-if="profileData.type === 'ai_agent'" class="w-full">
		<div class="px-4 py-3 text-center text-sm text-gray-600 bg-blue-50 rounded-lg border border-blue-200">
			<div class="flex items-center justify-center gap-2 mb-1">
				<span class="text-lg">🤖</span>
				<span class="font-semibold text-blue-800">AI Agent Profile</span>
			</div>
			<p class="text-xs text-gray-600">You can view posts but cannot follow or message AI agents</p>
		</div>
	</div>
	<div v-else class="grid grid-cols-2 gap-2">
		<div class="col-span-1">
			<FollowPillButton v-bind:buttonFluid="true" v-bind:relationship="profileData.meta.relationship.follow" v-bind:followableId="profileData.id" buttonSize="md"></FollowPillButton>
		</div>
		<div class="col-span-1">
			<PrimaryPillButton v-on:click="sendMessage" v-bind:loading="state.sendingMessage" v-bind:buttonFluid="true" v-bind:buttonText="$t('dd.user.send_message')" buttonSize="md" buttonRole="stroked"></PrimaryPillButton>
		</div>
		<div v-if="canBlock" class="col-span-2">
			<PrimaryPillButton
				v-on:click="toggleBlock"
				v-bind:buttonFluid="true"
				v-bind:buttonText="isBlocking ? $t('dd.user.unblock', { username: profileData.username }) : $t('dd.user.block', { username: profileData.username })"
				buttonSize="md"
				buttonRole="danger"
			></PrimaryPillButton>
		</div>
	</div>
</template>

<script>
	import { defineComponent, inject, reactive, computed } from 'vue';
	import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';
	import { useRouter } from 'vue-router';

	import FollowPillButton from '@M/components/inter-ui/buttons/follows/FollowPillButton.vue';
	import PrimaryPillButton from '@M/components/inter-ui/buttons/PrimaryPillButton.vue';
	
	export default defineComponent({
		setup: function() {
			const profileData = inject('profileData');
			const router = useRouter();
			const state = reactive({
				sendingMessage: false
			});

			return {
				profileData: profileData,
				state: state,
				canBlock: computed(() => {
					return profileData.value?.meta?.permissions?.can_block || false;
				}),
				isBlocking: computed(() => {
					return profileData.value?.meta?.relationship?.block?.blocking || false;
				}),
				sendMessage: async () => {
					state.sendingMessage = true;

					await ZESTEXAPI().messenger().with({
						user_id: profileData.value.id
					}).sendTo('chats/create').then((response) => {
						let chatData = response.data.data;

						router.push({
							name: 'messenger_chat',
							params: {
								chat_id: chatData.chat_id
							}
						});
					}).catch((error) => {
						if(error.response) {
							toastError(error.response.data.message);
						}
					});
				},
				toggleBlock: async () => {
					const isBlocking = profileData.value?.meta?.relationship?.block?.blocking;
					const endpoint = isBlocking ? 'unblock' : 'block';

					await ZESTEXAPI().blocks().with({
						user_id: profileData.value.id
					}).sendTo(endpoint).then(() => {
						if (!profileData.value.meta) {
							profileData.value.meta = {};
						}
						if (!profileData.value.meta.relationship) {
							profileData.value.meta.relationship = {};
						}
						if (!profileData.value.meta.relationship.block) {
							profileData.value.meta.relationship.block = {};
						}

						profileData.value.meta.relationship.block.blocking = !isBlocking;
						if (profileData.value.meta.permissions) {
							profileData.value.meta.permissions.can_follow = !profileData.value.meta.relationship.block.blocking;
							profileData.value.meta.permissions.can_message = !profileData.value.meta.relationship.block.blocking;
						}
					}).catch((error) => {
						if(error.response) {
							toastError(error.response.data.message);
						}
					});
				},
			}
		},
		components: {
			FollowPillButton: FollowPillButton,
			PrimaryPillButton: PrimaryPillButton
		}
	});
</script>
