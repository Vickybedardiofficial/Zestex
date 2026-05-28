<template>
	<div class="py-4 px-6 bg-bg-pr" v-bind:class="[isReplaying ? 'border-t border-t-bord-pr' : '']">
		<div v-if="isReplaying" class="mb-3">
			<MessageReplyPreview v-on:cancel="cancelMessageReply" v-bind:key="repliedMessage.id" v-bind:messageData="repliedMessage"></MessageReplyPreview>
		</div>
		<div v-if="queuedAttachments.length" class="mb-3 flex flex-wrap gap-2">
			<div v-for="attachment in queuedAttachments" v-bind:key="attachment.id" class="flex items-center gap-2 px-2 py-1 rounded-full bg-fill-qt border border-bord-pr">
				<template v-if="attachment.type === 'image'">
					<img v-bind:src="attachment.preview_url" class="w-8 h-8 rounded object-cover" />
				</template>
				<span class="text-par-s text-lab-pr2 truncate max-w-40">{{ attachment.original_name }}</span>
				<button type="button" class="text-lab-sc hover:text-lab-pr2" v-on:click="removeAttachment(attachment.id)">×</button>
			</div>
		</div>
		<div class="block relative leading-none">
			<div class="absolute left-4 top-3">
				<div class="relative">
					<button v-on:click.stop="state.isEmojisPickerOpen = true" v-bind:disabled="state.isSubmitting" class="outline-hidden size-icon-normal text-brand-900 disabled:opacity-80 disabled:cursor-wait">
						<SvgIcon type="line" name="face-smile"></SvgIcon>
					</button>
					<template v-if="state.isEmojisPickerOpen">
						<div class="block absolute bottom-6 left-0 w-80 z-50">
							<EmojisPicker 
								v-on:pick="insertMessageEmoji"
							v-on:close="state.isEmojisPickerOpen = false"></EmojisPicker>
						</div>
					</template>
				</div>
			</div>
	
			<textarea ref="messageInputField" class="resize-none pl-12 pr-36 pt-2.5 pb-2 leading-normal text-lab-pr font-normal text-par-l bg-fill-qt w-full h-12 min-h-12 max-h-40 overflow-x-hidden overflow-y-auto rounded-3xl outline-hidden placeholder:text-par-l placeholder:text-lab-sc placeholder:font-normal"
				v-on:input.trim="messageInputHandler"
				v-on:keydown.enter="submitForm"
				v-model.trim="inputMessageText"
			v-bind:placeholder="isReplaying ? $t('chat.write_reply') : $t('chat.write_message')"></textarea>

			<div class="absolute left-12 right-20 top-full mt-2 z-20">
				<MentionsPicker
					v-on:select="selectMention"
					classes="w-full border border-bord-pr rounded-lg popup-background-tr"
				></MentionsPicker>
			</div>
	
			<div class="absolute right-4 top-3">
				<div class="flex gap-4">
					<input ref="attachmentsInput" type="file" class="hidden" multiple
						accept="image/*,video/*,audio/*,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.openxmlformats-officedocument.presentationml.presentation,text/plain,application/zip,application/x-7z-compressed,application/x-rar-compressed,application/x-tar,application/gzip"
						v-on:change="handleAttachmentChange"
					/>
					<button v-on:click="triggerAttachmentPicker" v-bind:disabled="state.isSubmitting || state.isUploading" class="outline-hidden size-icon-normal text-brand-900 disabled:opacity-60 disabled:cursor-default">
						<SvgIcon type="line" name="image-plus"></SvgIcon>
					</button>
					<button v-on:click="triggerAttachmentPicker" v-bind:disabled="state.isSubmitting || state.isUploading" class="outline-hidden size-icon-normal text-brand-900 disabled:opacity-60 disabled:cursor-default">
						<SvgIcon type="line" name="paperclip"></SvgIcon>
					</button>
					<button v-if="hasTyped" v-bind:disabled="state.isSubmitting" v-on:click="submitForm" class="outline-hidden size-icon-normal text-brand-900 disabled:opacity-60 disabled:cursor-wait">
						<SvgIcon type="solid" name="send-03"></SvgIcon>
					</button>
					<button v-else v-bind:disabled="true" class="outline-hidden size-icon-normal text-brand-900 disabled:opacity-60 disabled:cursor-wait">
						<SvgIcon type="line" name="microphone-01"></SvgIcon>
					</button>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	import { defineComponent, ref, computed, reactive, defineAsyncComponent, onMounted } from 'vue';
	import { useInputHandlers } from '@/kernel/vue/composables/input/index.js';
	import { useChatStore } from '@D/store/chats/chat.store.js';
	import { ZESTEXEventBus } from '@/kernel/events/bus/index.js';
	import { ZESTEXSounds } from '@/kernel/services/sounds/index.js';
	import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';
	
	import MessageReplyPreview from '@D/views/messenger/children/chat/parts/form/MessageReplyPreview.vue';
	import MentionsPicker from '@D/components/mentions/MentionsPicker.vue';

	export default defineComponent({
		emits: ['typing'],
		setup: function (props, context) {
			const repliedMessage = ref(null);
			const chatStore = useChatStore();
			const messageInputField = ref(null);
			const inputMessageText = ref('');
			const attachmentsInput = ref(null);
			const queuedAttachments = ref([]);
			const { autoResize, insertSymbolAtCaret, matchMention, completeText } = useInputHandlers();
			const state = reactive({
				isSubmitting: false,
				isEmojisPickerOpen: false,
				isUploading: false
			});

            const messageInputHandler = function() {
                autoResize(messageInputField.value);

				const mentionMatch = matchMention(messageInputField.value);
				if(mentionMatch) {
					ZESTEXEventBus.emit('editor:mention-input', mentionMatch.username);
				}

				context.emit('typing');
            }

			onMounted(() => {
				ZESTEXEventBus.on('messenger-message:reply', (event) => {
					repliedMessage.value = event.messageData;

					if(messageInputField.value) {
						messageInputField.value.focus();
					}
				});
			});

			const submitForm = async function(event) {
				if(! state.isSubmitting) {
					if (event.shiftKey) {
						messageInputHandler();
					}
					else{
						event.preventDefault();
						state.isEmojisPickerOpen = false;
	
						if(inputMessageText.value.length || queuedAttachments.value.length) {
							try {
								state.isSubmitting = true;

								let payload = {
									content: inputMessageText.value
								};

								if(queuedAttachments.value.length) {
									payload['attachments'] = queuedAttachments.value.map((item) => item.id);
								}

								if(repliedMessage.value) {
									payload['parent_id'] = repliedMessage.value.id;
								}

								await chatStore.sendMessage(payload);

								state.isSubmitting = false;
								ZESTEXSounds.chatMessageSent();
	
								inputMessageText.value = '';
								queuedAttachments.value = [];
								repliedMessage.value = null;
	
								messageInputHandler();
							} catch (error) {
								alert(error);
							}
						}
					}
				}
            }

			return {
				state: state,
				repliedMessage: repliedMessage,
				attachmentsInput: attachmentsInput,
				queuedAttachments: queuedAttachments,
				messageInputHandler: messageInputHandler,
				submitForm: submitForm,
				autoResize: autoResize,
                messageInputField: messageInputField,
                inputMessageText: inputMessageText,
				selectMention: (username) => {
					const mentionMatch = matchMention(messageInputField.value);
					if(mentionMatch) {
						inputMessageText.value = completeText(messageInputField.value, {
							completable: `@${username}`,
							start: mentionMatch.start,
							end: mentionMatch.end
						});

						messageInputField.value.focus();
					}
				},
				hasTyped: computed(() => {
					return inputMessageText.value.length > 0 || queuedAttachments.value.length > 0;
				}),
				insertMessageEmoji: (emojiSymbol) => {
                    inputMessageText.value = insertSymbolAtCaret(messageInputField.value, emojiSymbol);
                    messageInputField.value.focus();
                },
				isReplaying: computed(() => {
					return (repliedMessage.value !== null);
				}),
				cancelMessageReply: () => {
					repliedMessage.value = null;
				},
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
								alert(error.response.data.message);
							}
						}
					}

					state.isUploading = false;
					if (attachmentsInput.value) {
						attachmentsInput.value.value = '';
					}
				}
			};
		},
		components: {
			EmojisPicker: defineAsyncComponent(() => {
                return import('@D/components/emojis/EmojisPicker.vue');
            }),
			MentionsPicker: MentionsPicker,
			MessageReplyPreview: MessageReplyPreview
		}
	});
</script>
