<template>
	<div class="relative">
		<PrimaryIconButton v-on:click="openNotificationsModal" buttonColor="text-lab-pr" iconName="bell-01" iconType="line"></PrimaryIconButton>
		<span class="absolute -top-1.5 -right-0.5">
			<BadgeCounter v-if="notificationsCount.raw" v-bind:count="notificationsCount.formatted"></BadgeCounter>
		</span>
	</div>
</template>

<script>
	import { defineComponent, onMounted, computed } from 'vue';
	import { useNotificationsStore } from '@M/store/notifications/notifications.store.js';

	import PrimaryIconButton from '@M/components/inter-ui/buttons/PrimaryIconButton.vue';
	import BadgeCounter from '@M/components/general/counters/BadgeCounter.vue';

	export default defineComponent({
		setup: function() {
			const notificationsStore = useNotificationsStore();

			const notificationsCount = computed(() => {
                return notificationsStore.unreadCount;
            });

			onMounted(() => {
				notificationsStore.fetchUnreadCount();
			});

			return {
				notificationsCount: notificationsCount,
				openNotificationsModal: () => {
					notificationsStore.openNotifications();
				}
			};
		},
		components: {
			PrimaryIconButton: PrimaryIconButton,
			BadgeCounter: BadgeCounter
		}
	});
</script>
