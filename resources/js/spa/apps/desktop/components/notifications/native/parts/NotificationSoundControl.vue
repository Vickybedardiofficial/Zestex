<template>
	<div class="flex items-center text-lab-pr2 pr-4 leading-none h-14 border-t border-t-fill-pr px-3">
		<span class="shrink-0 size-6">
			<SvgIcon name="volume-max" type="line"></SvgIcon>
		</span>
		<span class="ml-2 text-par-m">
			{{ $t('notifs.notifications_sound') }}
		</span>
		<span class="shrink-0 ml-auto">
			<SecondarySwitcher v-model="isSoundEnabled"></SecondarySwitcher>
		</span>
	</div>
</template>

<script>
	import { defineComponent, ref, onMounted, watch } from 'vue';
	import { ZESTEXSounds } from '@/kernel/services/sounds/index.js';
	import SecondarySwitcher from '@D/components/inter-ui/switchers/SecondarySwitcher.vue';

	export default defineComponent({
		props: {
			
		},
		setup: function(props, context) {
			const isSoundEnabled = ref(false);

			onMounted(() => {
				if(localStorage.getItem('notificationsSound')) {
					isSoundEnabled.value = true;
				}
				else {
					// Keep notification sounds enabled by default for first-time users.
					localStorage.setItem('notificationsSound', 1);
					isSoundEnabled.value = true;
				}
			});

			watch(isSoundEnabled, (newValue) => {
				if(newValue) {
					localStorage.setItem('notificationsSound', 1);
					ZESTEXSounds.uiFeedback();
				}
				
				else {
					localStorage.removeItem('notificationsSound');
				}
			});

			return {
				isSoundEnabled: isSoundEnabled
			};
		},
		components: {
			SecondarySwitcher: SecondarySwitcher
		}
	});
</script>
