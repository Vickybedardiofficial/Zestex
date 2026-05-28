<template>
	<div class="block">
		<div class="block">
			<h1 class="text-title-2 font-bold leading-none text-lab-pr">
				{{ profileData.name }} 
				<VerificationBadge size="md" v-if="profileData.verified && profileData.type !== 'ai_agent'"></VerificationBadge>
				<span v-if="profileData.type === 'ai_agent'" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 ml-2">
					<span>🤖</span>
					<span>AI Agent</span>
				</span>
			</h1>
			<p class="text-par-s text-lab-sc">
				<span class="inline-flex items-center gap-1">
					<span>@{{ profileData.username }}</span>
				</span>
			</p>

			<div v-if="profileData.business_account && profileData.business_account.name" class="mt-2 inline-flex items-center gap-1 text-lab-sc leading-zero">
				<span class="text-lab-tr">
					<SvgIcon name="building-08" type="line" classes="size-icon-small"></SvgIcon>
				</span>
				<span class="text-par-s">
					{{ profileData.business_account.name }}
				</span>
				<span v-if="profileData.business_account.verified" class="text-green-900 font-semibold">&check;</span>
			</div>
		</div>
		<div v-if="profileData.bio" class="mt-4 max-w-8/12">
			<p class="text-par-m text-lab-pr2" v-html="markdownRenderer(profileData.bio)"></p>
		</div>
		<div v-if="profileData.website" class="mb-6">
			<a v-bind:href="profileData.website" target="_blank" class="text-brand-900 text-par-n font-medium hover:underline">
				{{ profileData.website }}
			</a>
		</div>
	</div>
</template>

<script>
	import { defineComponent, inject } from 'vue';
	import MarkdownParser from 'markdown-it';

	export default defineComponent({
		setup() {
			const profileData = inject('profileData');
            const MarkdownIT = new MarkdownParser({
                html: true,
                breaks: true,
                linkify: true
            });

			return {
				profileData: profileData,
                markdownRenderer: (text) => {
                    return MarkdownIT.renderInline(text);
                }
			};
		}
	});
</script>
