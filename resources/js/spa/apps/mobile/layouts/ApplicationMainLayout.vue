<template>
	<ApplicationHeader v-if="! hideHeader"></ApplicationHeader>

	<div class="pb-14">
		<RouterView></RouterView>
	</div>

	<LightboxPlayer></LightboxPlayer>

	<ConfirmationModal></ConfirmationModal>

	<ApplicationNavbar v-if="! hideNavbar"></ApplicationNavbar>

	<ReportModal></ReportModal>

	<NotificationsModal v-if="isNotificationsOpen"></NotificationsModal>


</template>

<script>
	import { defineComponent, computed, onMounted, onUnmounted } from 'vue';
	import { useRouter, useRoute } from 'vue-router';
	import { useNotificationsStore } from '@M/store/notifications/notifications.store.js';
	import BRD from '@/kernel/websockets/brd/index.js';
	import { ZESTEXEventBus } from '@/kernel/events/bus/index.js';
	import { useAuthStore } from '@M/store/auth/auth.store.js';
	import { ZESTEXSounds } from '@/kernel/services/sounds/index.js';
	import { usePostEditorStore } from '@M/store/timeline/editor.store.js';

	import ApplicationHeader from '@M/components/layout/ApplicationHeader.vue';
	import ApplicationNavbar from '@M/components/layout/ApplicationNavbar.vue';
	import LightboxPlayer from '@M/components/lightbox/LightboxPlayer.vue';
	import ConfirmationModal from '@M/components/general/modals/prompt/ConfirmationModal.vue';
	import ReportModal from '@M/components/reports/ReportModal.vue';
	import NotificationsModal from '@M/components/notifications/native/NotificationsModal.vue';


	export default defineComponent({
		setup: function() {
			const notificationsStore = useNotificationsStore();
			const authStore = useAuthStore();
			const postEditorStore = usePostEditorStore();
			const router = useRouter();
			const route = useRoute();

			const openEditor = (data) => {
				if(data && data.mentionName) {
					postEditorStore.mentionName = data.mentionName;
				}

				if(data && data.quotePostId) {
					postEditorStore.setQuotePostId(data.quotePostId);
				}

				let query = {};

				if(data && data.quotePostId) {
					query.quote_post_id = data.quotePostId;
				}

				router.push({
					name: 'post_editor',
					query: query
				});
			};

			onMounted(() => {
				notificationsStore.fetchUnreadCount();

				if(localStorage.getItem('notificationsSound') === null) {
					localStorage.setItem('notificationsSound', 1);
				}

				if(window.ZESTEXBRD) {
                    ZESTEXBRD.private(BRD.getChannel('AUTH_USER', [authStore.userData.id])).notification(function (event) {
                        if(event.type === 'chat.notification') {
                            // TODO: Handle chat notifications
                        }
                        else {
                            notificationsStore.setUnreadNotificationsCount(event.data);
                            ZESTEXEventBus.emit('notifications:received');
                        }

                        if(localStorage.getItem('notificationsSound')) {
                        	ZESTEXSounds.notificationReceived();
                        }
                    });
                }

				ZESTEXEventBus.on('post-editor:open', openEditor);
			});

			onUnmounted(() => {
                if(window.ZESTEXBRD) {
                    ZESTEXBRD.leave(BRD.getChannel('AUTH_USER', [authStore.userData.id]));
                }

				ZESTEXEventBus.off('post-editor:open', openEditor);
            });

			return {
				isNotificationsOpen: computed(() => {
					return notificationsStore.isOpen;
				}),
				hideNavbar: computed(() => {
					return route.meta.hideNavbar || false;
				}),
				hideHeader: computed(() => {
					return route.meta.hideHeader || false;
				})
			};
		},
		components: {
			ApplicationHeader: ApplicationHeader,
			ApplicationNavbar: ApplicationNavbar,
			LightboxPlayer: LightboxPlayer,
			ConfirmationModal: ConfirmationModal,
			ReportModal: ReportModal,
			NotificationsModal: NotificationsModal
		}
	});
</script>
