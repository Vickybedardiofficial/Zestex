<template>
    <ContentModal contentWidth="w-[680px]" v-on:close="closeModal">
        <div class="border border-bord-pr rounded-2xl overflow-hidden">
            <div class="px-5 py-4 bg-fill-fv border-b border-bord-pr">
                <div class="flex items-center">
                    <div class="size-9 rounded-xl bg-brand-900/15 text-brand-900 inline-flex-center">
                        <SvgIcon name="cpu-chip-02" type="line" classes="size-icon-normal"></SvgIcon>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-par-l font-semibold text-lab-pr2 leading-none">ZE AI</h3>
                        <p class="text-cap-l text-lab-sc mt-1">Advanced assistant</p>
                    </div>
                    <button
                        type="button"
                        class="ml-auto text-par-s text-brand-900 hover:underline"
                        v-on:click="clearChat"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <div ref="messagesRef" class="h-[420px] overflow-y-auto px-5 py-4 bg-bg-pr">
                <div
                    v-for="(item, idx) in messages"
                    :key="idx"
                    class="mb-3 flex"
                    :class="[item.role === 'user' ? 'justify-end' : 'justify-start']"
                >
                    <div
                        class="max-w-[88%] rounded-2xl px-4 py-3 text-par-m leading-6 whitespace-pre-wrap break-words"
                        :class="[
                            item.role === 'user'
                                ? 'bg-brand-900 text-white'
                                : 'bg-fill-fv text-lab-pr2 border border-bord-pr'
                        ]"
                    >
                        {{ item.content }}
                    </div>
                </div>

                <div v-if="isLoading" class="mb-3 flex justify-start">
                    <div class="rounded-2xl px-4 py-3 text-par-m bg-fill-fv text-lab-sc border border-bord-pr">
                        ZE AI is typing...
                    </div>
                </div>
            </div>

            <form v-on:submit.prevent="sendMessage" class="px-5 py-4 border-t border-bord-pr bg-bg-pr">
                <div class="flex items-end gap-3">
                    <textarea
                        ref="inputRef"
                        v-model.trim="prompt"
                        class="w-full min-h-12 max-h-40 resize-none rounded-xl border border-bord-pr bg-fill-qt px-4 py-3 text-par-m text-lab-pr2 outline-hidden"
                        placeholder="Ask ZE AI anything..."
                        v-on:keydown.enter.exact.prevent="sendMessage"
                    ></textarea>
                    <button
                        type="submit"
                        class="h-12 px-4 rounded-xl bg-brand-900 text-white text-par-m font-medium disabled:opacity-60"
                        :disabled="isLoading || !prompt.length"
                    >
                        Send
                    </button>
                </div>
            </form>
        </div>
    </ContentModal>
</template>

<script>
    import { defineComponent, computed, nextTick, ref, watch } from 'vue';
    import { useAiAssistantStore } from '@D/store/ai/assistant.store.js';
    import ContentModal from '@D/components/general/modals/ContentModal.vue';

    export default defineComponent({
        setup: function() {
            const aiAssistantStore = useAiAssistantStore();
            const prompt = ref('');
            const messagesRef = ref(null);
            const inputRef = ref(null);

            const scrollToBottom = () => {
                nextTick(() => {
                    if (messagesRef.value) {
                        messagesRef.value.scrollTop = messagesRef.value.scrollHeight;
                    }
                });
            };

            watch(
                () => aiAssistantStore.messages.length,
                () => {
                    scrollToBottom();
                }
            );

            watch(
                () => aiAssistantStore.isOpen,
                (isOpen) => {
                    if (isOpen) {
                        nextTick(() => {
                            if (inputRef.value) {
                                inputRef.value.focus();
                            }
                            scrollToBottom();
                        });
                    }
                }
            );

            return {
                prompt: prompt,
                messagesRef: messagesRef,
                inputRef: inputRef,
                messages: computed(() => aiAssistantStore.messages),
                isLoading: computed(() => aiAssistantStore.isLoading),
                closeModal: () => {
                    aiAssistantStore.close();
                },
                clearChat: () => {
                    aiAssistantStore.clearChat();
                    scrollToBottom();
                },
                sendMessage: async () => {
                    const text = prompt.value.trim();
                    if (!text) {
                        return;
                    }

                    prompt.value = '';
                    await aiAssistantStore.sendPrompt(text);
                    scrollToBottom();
                }
            };
        },
        components: {
            ContentModal: ContentModal
        }
    });
</script>

