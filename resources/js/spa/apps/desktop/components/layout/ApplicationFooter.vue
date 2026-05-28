<template>
	<div class="flex flex-wrap mb-2 leading-5 gap-2">
		<a v-if="$config('features.business_accounts.enabled')" v-bind:href="$getRoute('business_dashboard_index')" class="inline-flex items-center h-7 px-2.5 rounded-full border border-fill-qt/70 bg-bg-pr text-cap-s text-lab-pr2 hover:bg-fill-fv">
			{{ $t('labels.business_account') }}
		</a>
		<RouterLink v-bind:to="{ name: 'settings_theme' }" class="inline-flex items-center h-7 px-2.5 rounded-full border border-fill-qt/70 bg-bg-pr text-cap-s text-lab-pr2 hover:bg-fill-fv">
			{{ $t('labels.theme') }}
		</RouterLink>
		<RouterLink v-if="$config('features.jobs.enabled')" v-bind:to="{ name: 'jobs_index' }" class="inline-flex items-center h-7 px-2.5 rounded-full border border-fill-qt/70 bg-bg-pr text-cap-s text-lab-pr2 hover:bg-fill-fv">
			{{ $t('labels.jobs') }}
		</RouterLink>
		<a v-bind:href="$getRoute('help_center')" target="_blank" class="inline-flex items-center h-7 px-2.5 rounded-full border border-fill-qt/70 bg-bg-pr text-cap-s text-lab-pr2 hover:bg-fill-fv">
			{{ $t('labels.help_center') }}
		</a>
		<a v-bind:href="$getRoute('api_developers')" target="_blank" v-if="$config('features.api.enabled')" class="inline-flex items-center h-7 px-2.5 rounded-full border border-fill-qt/70 bg-bg-pr text-cap-s text-lab-pr2 hover:bg-fill-fv">
			{{ $t('labels.api_developers') }}
		</a>
		<a v-bind:href="$getRoute('privacy_policy')" target="_blank" class="inline-flex items-center h-7 px-2.5 rounded-full border border-fill-qt/70 bg-bg-pr text-cap-s text-lab-pr2 hover:bg-fill-fv">
			{{ $t('labels.privacy_policy') }}
		</a>
		<a v-bind:href="$getRoute('cookies_policy')" target="_blank" class="inline-flex items-center h-7 px-2.5 rounded-full border border-fill-qt/70 bg-bg-pr text-cap-s text-lab-pr2 hover:bg-fill-fv">
			{{ $t('labels.cookies_policy') }}
		</a>
		<a v-bind:href="$getRoute('terms_of_use')" target="_blank" class="inline-flex items-center h-7 px-2.5 rounded-full border border-fill-qt/70 bg-bg-pr text-cap-s text-lab-pr2 hover:bg-fill-fv">
			{{ $t('labels.terms') }}
		</a>
		<RouterLink v-bind:to="{ name: 'settings_language' }" class="inline-flex items-center h-7 px-2.5 rounded-full border border-fill-qt/70 bg-bg-pr text-cap-s text-lab-pr2 hover:bg-fill-fv">
			{{ $t('labels.language') }}
		</RouterLink>
	</div>
	<div class="leading-5">
		<span class="text-cap-s text-lab-sc">{{ $config('app.name') }} &copy; {{ currentYear }}. All rights reserved.</span>
	</div>
</template>

<script>
	import { defineComponent } from 'vue';

	export default defineComponent({
		setup: function() {
			return {
				currentYear: (new Date()).getFullYear()
			}
		}
	});
</script>
