<template>
	<ToastNotification></ToastNotification>

	<template v-if="repliedMessage">
		<MessageReplyPreview v-bind:messageData="repliedMessage" v-on:cancel="cancelReply" v-bind:key="repliedMessage.id"></MessageReplyPreview>
	</template>

	<div v-if="queuedAttachments.length" class="px-4 pb-2 flex flex-wrap gap-2">
		<div v-for="attachment in queuedAttachments" v-bind:key="attachment.id" class="flex items-center gap-2 px-2 py-1 rounded-full bg-fill-qt border border-bord-pr">
			<template v-if="attachment.type === 'image'">
				<img v-bind:src="attachment.preview_url" class="w-8 h-8 rounded object-cover" />
			</template>
			<span class="text-par-s text-lab-pr2 truncate max-w-40">{{ attachment.original_name }}</span>
			<button type="button" class="text-lab-sc hover:text-lab-pr2" v-on:click="removeAttachment(attachment.id)">×</button>
		</div>
	</div>
	
	<div class="pb-3 px-4 pt-3">
		<div class="relative leading-none">
			<textarea ref="messageContentField" class="resize-none pl-5 pt-3 pr-16 pb-2 leading-normal text-lab-pr text-par-l bg-fill-qt w-full h-12 min-h-12 max-h-40 overflow-x-hidden overflow-y-auto rounded-3xl outline-hidden placeholder:text-par-l placeholder:text-lab-sc placeholder:font-normal"
				v-model.trim="messageContent"
				v-on:input="messageInputHandler"
			v-bind:placeholder="inputPlaceholder"></textarea>

			<div v-if="state.mentions.length" class="absolute left-1 right-1 top-full mt-2 rounded-xl border border-bord-pr popup-background-tr max-h-56 overflow-y-auto z-20">
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
	
			<div class="absolute right-2 top-2">
				<div class="flex gap-4">
					<div class="shrink-0">
						<input ref="attachmentsInput" type="file" class="hidden" multiple
							accept="image/*,video/*,audio/*,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.openxmlformats-officedocument.presentationml.presentation,text/plain,application/zip,application/x-7z-compressed,application/x-rar-compressed,application/x-tar,application/gzip"
							v-on:change="handleAttachmentChange"
						/>
						<PrimaryIconButton v-on:click="triggerAttachmentPicker" v-bind:disabled="state.isSubmitting || state.isUploading" iconName="paperclip" iconSize="icon-normal" buttonColor="text-brand-900"></PrimaryIconButton>
					</div>
					<div class="shrink-0">
						<PrimaryIconButton v-bind:disabled="submitButtonStatus" v-on:click="submitForm" iconName="send-03" iconSize="icon-normal" buttonColor="text-brand-900"></PrimaryIconButton>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import { defineComponent, ref, reactive, computed, nextTick, onMounted } from 'vue';
	import { useChatStore } from '@M/store/chats/chat.store.js';
	import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';
	import { useInputHandlers } from '@/kernel/vue/composables/input/index.js';
	import { ZESTEXSounds } from '@/kernel/services/sounds/index.js';
	import { ZESTEXEventBus } from '@/kernel/events/bus/index.js';

	import PrimaryIconButton from '@M/components/inter-ui/buttons/PrimaryIconButton.vue';
	import ToastNotification from '@M/components/notifications/toast/ToastNotification.vue';
	import MessageReplyPreview from '@M/views/messenger/children/chat/parts/editor/MessageReplyPreview.vue';
	
	export default defineComponent({
		emits: ['typing'],
		setup: function (props, context) {
			const chatStore = useChatStore();
			const messageContentField = ref(null);
			const messageContent = ref('');
			const repliedMessage = ref(null);
			const attachmentsInput = ref(null);
			const queuedAttachments = ref([]);
			const { autoResize, matchMention, completeText } = useInputHandlers();

			const state = reactive({
				isSubmitting: false,
				isSearchingMentions: false,
				mentions: [],
				isUploading: false
			});

			onMounted(() => {
				ZESTEXEventBus.on('messenger-message:reply', (event) => {
					repliedMessage.value = event.messageData;

					if(messageContentField.value) {
						messageContentField.value.focus();
					}
				});
			});

			const messageInputHandler = function() {
				autoResize(messageContentField.value);

				const mentionMatch = matchMention(messageContentField.value);
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

				context.emit('typing');
			}

			const submitForm = async function(event) {
				try {
					state.isSubmitting = true;

					if(messageContent.value.length || queuedAttachments.value.length) {
						const content = messageContent.value;
						
						const payload = {
							content: content
						};

						if(queuedAttachments.value.length) {
							payload['attachments'] = queuedAttachments.value.map((item) => item.id);
						}

						messageContent.value = '';
						queuedAttachments.value = [];

						if(repliedMessage.value) {
							payload['parent_id'] = repliedMessage.value.id;
						}

						repliedMessage.value = null;
					
						await chatStore.sendMessage(payload);
						
						nextTick(() => {
							messageInputHandler();
						});

						ZESTEXSounds.chatMessageSent();
					}

					state.isSubmitting = false;
				} catch (error) {
					alert(error);
				}
            }

			return {
				state: state,
				messageContent: messageContent,
				submitForm: submitForm,
				repliedMessage: repliedMessage,
                messageContentField: messageContentField,
				messageInputHandler: messageInputHandler,
				selectMention: (username) => {
					const mentionMatch = matchMention(messageContentField.value);
					if(mentionMatch) {
						messageContent.value = completeText(messageContentField.value, {
							completable: `@${username}`,
							start: mentionMatch.start,
							end: mentionMatch.end
						});

						state.mentions = [];
						messageContentField.value.focus();
					}
				},
				isReplaying: computed(() => {
					return repliedMessage.value !== null;
				}),
				submitButtonStatus: computed(() => {
					return state.isSubmitting || (! messageContent.value.length && !queuedAttachments.value.length);
				}),
				cancelReply: () => {
					repliedMessage.value = null;
				},
				attachmentsInput: attachmentsInput,
				queuedAttachments: queuedAttachments,
				triggerAttachmentPicker: () => {
					if (attachmentsInput.value && !state.isSubmitting && !state.isUploading) {
						attachmentsInput.value.click();
					}
				},
				removeAttachment: (attachmentId) => {
					queuedAttachments.value = queuedAttachments.value.filter((item) => item.id !== attachmentId);
				},
				handleAttachmentChange: async (event) => {
					const files = Array.from(event.target.files || []);
					if (!files.length) {
						return;
					}

					state.isUploading = true;

					for (const file of files) {
						const formData = new FormData();
						formData.append('file', file);

						try {
							const response = await ZESTEXAPI()
								.messenger()
								.with(formData)
								.withHeaders({ 'Content-Type': 'multipart/form-data' })
								.sendTo('attachments/upload');

							if (response?.data?.data) {
								queuedAttachments.value.push(response.data.data);
							}
						} catch (error) {
							if (error.response) {
								toastError(error.response.data.message);
							}
						}
					}

					state.isUploading = false;
					if (attachmentsInput.value) {
						attachmentsInput.value.value = '';
					}
				},
				inputPlaceholder: computed(() => {
					if(state.isSubmitting) {
						return __t('chat.sending_message');
					}

					else if(repliedMessage.value) {
						return __t('chat.write_reply');
					}

					return __t('chat.write_message');
				})
			};
		},
		components: {
			PrimaryIconButton: PrimaryIconButton,
			ToastNotification: ToastNotification,
			MessageReplyPreview: MessageReplyPreview
		}
	});
</script>
