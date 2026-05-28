<template>
	<TimelineContainer>
		<Toolbar v-on:close="$router.back" v-bind:title="$t('labels.publication_page_title')"></Toolbar>
		<TimelinePublicationSkeleton v-if="state.isLoading"></TimelinePublicationSkeleton>
		<template v-else>
			<TimelinePublication v-bind:postData="postData" v-on:delete="handlePostDelete"></TimelinePublication>
		</template>
	</TimelineContainer>
</template>

<script>
	import { defineComponent, onMounted, reactive, ref } from 'vue';
	import { useRouter } from 'vue-router';
	import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';
	import { useAuthStore } from '@M/store/auth/auth.store.js';

	import TimelinePublication from '@M/components/timeline/feed/TimelinePublication.vue';
    import TimelinePublicationSkeleton from '@M/components/timeline/feed/TimelinePublicationSkeleton.vue';
    import TimelineContainer from '@M/components/timeline/feed/TimelineContainer.vue';
	import Toolbar from '@M/components/layout/Toolbar.vue';

	export default defineComponent({
		props: {
			hash_id: {
				type: String,
				required: true
			}
		},
		setup: function(props) {
			const router = useRouter();
			const state = reactive({
				isLoading: true
			});
			const authStore = useAuthStore();

			const postData = ref(null);
			const postAuthor = ref(null);

			onMounted(async () => {
				const api = authStore.authCheck ? ZESTEXAPI().userTimeline() : ZESTEXAPI().publicApi();
				const endpoint = authStore.authCheck
					? `post/${props.hash_id}`
					: `timeline/publication/${props.hash_id}`;

				await api.getFrom(endpoint).then(function(response) {
					postData.value = response.data.data.post;
					postAuthor.value = response.data.data.author;
					state.isLoading = false;
				}).catch(function(error) {
					router.push({
                        name: 'error_404'
                    });
				});
			});

			return {
				state: state,
				postData: postData,
				postAuthor: postAuthor,
				handlePostDelete: () => {
					alert('post deleted');
				}
			};
		},
		components: {
			TimelinePublication: TimelinePublication,
			TimelinePublicationSkeleton: TimelinePublicationSkeleton,
			TimelineContainer: TimelineContainer,
			Toolbar: Toolbar
		}
	});
</script>
