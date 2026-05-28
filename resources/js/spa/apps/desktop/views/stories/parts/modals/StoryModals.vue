<template>
	<StoryShareModal v-if="state.isShareModalOpen" v-on:cancel="handleStoryShareCancel"></StoryShareModal>
	<StoryContentModal v-if="state.isContentModalOpen" v-on:hide="handleHideContent"></StoryContentModal>
	<StoryViewsModal v-if="state.isViewsModalOpen" v-on:hide="handleHideViews"></StoryViewsModal>
</template>

<script>
	import { defineComponent, onMounted, reactive, onUnmounted } from 'vue';
	import { ZESTEXEventBus } from '@/kernel/events/bus/index.js';

	import StoryViewsModal from '@D/views/stories/parts/modals/StoryViewsModal.vue';
	import StoryShareModal from '@D/views/stories/parts/modals/StoryShareModal.vue';
	import StoryContentModal from '@D/views/stories/parts/modals/StoryContentModal.vue';

	export default defineComponent({
		setup: function() {
			const state = reactive({
				isShareModalOpen: false,
				isContentModalOpen: false,
				isViewsModalOpen: false
			});
			
            const handleStoryShare = () => {
				state.isShareModalOpen = true;
				ZESTEXEventBus.emit('story:pause');
			}
			
            const handleShowContent = () => {
				state.isContentModalOpen = true;
				ZESTEXEventBus.emit('story:pause');
			}

            const handleShowViews = () => {
				state.isViewsModalOpen = true;
				ZESTEXEventBus.emit('story:pause');
			}

			onMounted(() => {
				ZESTEXEventBus.on('story:share', handleStoryShare);
				ZESTEXEventBus.on('story:show-content', handleShowContent);
				ZESTEXEventBus.on('story:show-views', handleShowViews);
			});

			onUnmounted(() => {
				ZESTEXEventBus.off('story:share', handleStoryShare);
				ZESTEXEventBus.off('story:show-content', handleShowContent);
				ZESTEXEventBus.off('story:show-views', handleShowViews);
			});

			return {
				state: state,
				handleStoryShareCancel: () => {
					ZESTEXEventBus.emit('story:play');
					state.isShareModalOpen = false;
				},
				handleHideContent: () => {
					state.isContentModalOpen = false;
					ZESTEXEventBus.emit('story:play');
				},
				handleHideViews: () => {
					state.isViewsModalOpen = false;
					ZESTEXEventBus.emit('story:play');
				}
			};
		},
		components: {
            StoryViewsModal: StoryViewsModal,
            StoryShareModal: StoryShareModal,
			StoryContentModal: StoryContentModal
		}
	});
</script>