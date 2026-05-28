<template>
	<form v-on:submit.prevent="submitComment">
		<template v-if="repliedComment">
			<ReplyPreview v-bind:repliedComment="repliedComment" v-on:cancel="cancelCommentReply"></ReplyPreview>
		</template>
		<div class="px-4 flex items-start gap-3 pt-3 leading-none">
			<div class="shrink-0">
				<AvatarExtraSmall v-bind:avatarSrc="userData.avatar_url"></AvatarExtraSmall>
			</div>
			<div class="flex-1 overflow-hidden">
				<div class="max-h-72 overflow-y-auto">
					<textarea
						ref="commentTextInputField"
						v-model.trim="commentInputText"
					v-on:input="commentInputHandler"
					v-bind:placeholder="$t('labels.enter_comment_placeholder') + '...'"
					class="pt-1 leading-6 bg-transparent outline-hidden w-full h-x-small-avatar min-h-x-small-avatar resize-none text-lab-pr text-par-l placeholder:text-par-n"></textarea>
				</div>
				<div v-if="state.mentions.length" class="mt-1 rounded-xl border border-bord-pr popup-background-tr max-h-56 overflow-y-auto">
					<button
						v-for="mention in state.mentions"
						:key="mention.id"
						type="button"
						class="w-full text-left px-3 py-2 border-b border-bord-pr last:border-b-0"
						v-on:click="selectMention(mention.username)"
					>
						<div class="text-par-m text-lab-pr2 font-medium">@{{ mention.username }}</div>
						<div class="text-par-s text-lab-sc truncate">{{ mention.name }}</div>
					</button>
				</div>
			</div>
			<div class="ml-auto shrink-0">
				<PrimaryIconButton v-bind:disabled="state.isSubmitting || ! commentInputText.length" v-on:click="submitComment" iconName="send-03" iconSize="icon-normal" buttonColor="text-brand-900"></PrimaryIconButton>
			</div>
		</div>
	</form>
</template>

<script>
	import { defineComponent, ref, defineAsyncComponent, reactive, onMounted } from 'vue';
	import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';
	import { useAuthStore } from '@M/store/auth/auth.store.js';
    import { useInputHandlers } from '@/kernel/vue/composables/input/index.js';
	import { ZESTEXEventBus } from '@/kernel/events/bus/index.js';

	import AvatarExtraSmall from '@M/components/general/avatars/AvatarExtraSmall.vue';
	import PrimaryIconButton from '@M/components/inter-ui/buttons/PrimaryIconButton.vue';
	import ReplyPreview from '@M/components/timeline/feed/parts/comments/editor/ReplyPreview.vue';
	
	export default defineComponent({
		props: {
			postId: {
				type: Number,
				default: 0
			}
		},
		emits: ['created'],
		setup: function(props, context) {
			const commentInputText = ref('');
			const authStore = useAuthStore();
            const userData = ref(authStore.userData);
			const postId = ref(props.postId);
			const repliedComment = ref(null);

			const state = reactive({
                isSubmitting: false,
				isSearchingMentions: false,
				mentions: []
            });

			const { autoResize, matchMention, completeText } = useInputHandlers();
			const commentTextInputField = ref(null);

            const commentInputHandler = function() {
                autoResize(commentTextInputField.value);

				const mentionMatch = matchMention(commentTextInputField.value);
				if(mentionMatch) {
					if(! state.isSearchingMentions) {
						state.isSearchingMentions = true;
						ZESTEXAPI().autocompletes().with({
							query: mentionMatch.username
						}).sendTo('mentions').then((response) => {
							state.mentions = response.data.data || [];
						}).catch(() => {
							state.mentions = [];
						}).finally(() => {
							state.isSearchingMentions = false;
						});
					}
				}
				else {
					state.mentions = [];
				}
            }

			onMounted(() => {
				ZESTEXEventBus.on('publication-comment:reply', (event) => {
					repliedComment.value = event.commentData;
				});
			});

			return {
				state: state,
				userData: userData,
				commentInputText: commentInputText,
				selectMention: (username) => {
					const mentionMatch = matchMention(commentTextInputField.value);
					if(mentionMatch) {
						commentInputText.value = completeText(commentTextInputField.value, {
							completable: `@${username}`,
							start: mentionMatch.start,
							end: mentionMatch.end
						});

						state.mentions = [];
						commentTextInputField.value.focus();
					}
				},
				repliedComment: repliedComment,
				commentInputHandler: commentInputHandler,
				commentTextInputField: commentTextInputField,
				submitComment: async () => {
                    if(commentInputText.value.length > 0) {
                        
                        state.isSubmitting = true;
                        let parentId = null;

                        if(repliedComment.value) {
                            parentId = repliedComment.value.id;
                        }

                        await ZESTEXAPI().userTimeline().with({
                            post_id: postId.value,
                            content: commentInputText.value,
                            parent_id: parentId
                        }).sendTo('post/comment/create').then((response) => {
                            context.emit('created', response.data.data);
                            commentInputText.value = '';

                            autoResize(commentTextInputField.value);

                            repliedComment.value = null;
                        }).catch((error) => {
                            if (error.response) {
                                toastError(error.response.data.message);
                            }
                        });

                        state.isSubmitting = false;
                    }
                },
				cancelCommentReply: () => {
                    repliedComment.value = null;
                }
			};
		},
		components: {
			AvatarExtraSmall: AvatarExtraSmall,
			PrimaryIconButton: PrimaryIconButton,
			ReplyPreview: ReplyPreview
		}
	});
</script>
