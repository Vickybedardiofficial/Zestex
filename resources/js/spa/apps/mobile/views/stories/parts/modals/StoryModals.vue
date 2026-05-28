<template>
	<StoryContentModal v-if="state.isContentModalOpen" v-on:hide="handleHideContent"></StoryContentModal>
	<StoryViewsModal v-if="state.isViewsModalOpen" v-on:hide="handleHideViews"></StoryViewsModal>
</template>

<script>
	import { defineComponent, onMounted, reactive, onUnmounted } from 'vue';
	import { ZESTEXEventBus } from '@/kernel/events/bus/index.js';

	import StoryViewsModal from '@M/views/stories/parts/modals/StoryViewsModal.vue';
	import StoryContentModal from '@M/views/stories/parts/modals/StoryContentModal.vue';

	export default defineComponent({
		setup: function() {
			const state = reactive({
				isContentModalOpen: false,
				isViewsModalOpen: false
			});

            const handleShowContent = () => {
				state.isContentModalOpen = true;
				ZESTEXEventBus.emit('story:pause');
			}

            const handleShowViews = () => {
				state.isViewsModalOpen = true;
				ZESTEXEventBus.emit('story:pause');
			}

			onMounted(() => {
				ZESTEXEventBus.on('story:show-content', handleShowContent);
				ZESTEXEventBus.on('story:show-views', handleShowViews);
			});

			onUnmounted(() => {
				ZESTEXEventBus.off('story:show-content', handleShowContent);
				ZESTEXEventBus.off('story:show-views', handleShowViews);
			});

			return {
				state: state,
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
			StoryContentModal: StoryContentModal
		}
	});
</script>