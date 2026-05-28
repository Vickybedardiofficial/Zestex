<template>
	<div v-if="hasContent" class="text-par-s text-white px-3 cursor-pointer mb-2" v-on:click="showContent">
		{{ storyContent.substr(0, 90) }}...
	</div>
</template>

<script>
	import { defineComponent, inject, computed } from 'vue';
	import { ZESTEXEventBus } from '@/kernel/events/bus/index.js';

	export default defineComponent({
		setup: function() {
			const playerState = inject('playerState');

			return {
				hasContent: computed(() => {
					return playerState.frameData.content.length;
				}),
				storyContent: computed(() => {
					return playerState.frameData.content;
				}),
				showContent: () => {
					ZESTEXEventBus.emit('story:show-content');
				}
			};
		}
	});
</script>