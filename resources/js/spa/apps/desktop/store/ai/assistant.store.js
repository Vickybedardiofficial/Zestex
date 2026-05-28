import { defineStore } from 'pinia';
import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';

const INITIAL_MESSAGE = {
    role: 'assistant',
    content: 'Namaste, main ZE AI hoon. Aap jo puchhna chahte hain, seedha likhiye.'
};

const STORAGE_KEY = 'ze_ai_chat_history_v1';

const sanitizeAssistantReply = function(text) {
    return String(text || '')
        .replace(/\*\*(.*?)\*\*/g, '$1')
        .replace(/__(.*?)__/g, '$1')
        .replace(/`{1,3}/g, '')
        .replace(/^#{1,6}\s*/gm, '')
        .trim();
};

const getInitialMessages = function() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return [INITIAL_MESSAGE];
        }

        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed) || parsed.length < 1) {
            return [INITIAL_MESSAGE];
        }

        return parsed
            .filter((item) => item && typeof item.content === 'string' && typeof item.role === 'string')
            .map((item) => ({
                role: item.role === 'user' ? 'user' : 'assistant',
                content: String(item.content).trim()
            }))
            .filter((item) => item.content.length > 0)
            .slice(-80);
    } catch {
        return [INITIAL_MESSAGE];
    }
};

const useAiAssistantStore = defineStore('desktop_ai_assistant_store', {
    state: function() {
        return {
            isOpen: false,
            isLoading: false,
            messages: getInitialMessages()
        };
    },
    actions: {
        persistMessages: function() {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(this.messages.slice(-80)));
            } catch {}
        },
        open: function() {
            this.isOpen = true;
        },
        close: function() {
            this.isOpen = false;
        },
        clearChat: function() {
            this.messages = [
                {
                    role: 'assistant',
                    content: 'Chat clear ho gaya. Naya sawaal bhejiye.'
                }
            ];
            this.persistMessages();
        },
        sendPrompt: async function(promptText) {
            const prompt = (promptText || '').trim();
            if (!prompt || this.isLoading) {
                return;
            }

            this.messages.push({
                role: 'user',
                content: prompt
            });
            this.persistMessages();

            this.isLoading = true;

            const history = this.messages.slice(-14).map((item) => ({
                role: item.role,
                content: item.content
            }));

            await ZESTEXAPI()
                .aiAssistant()
                .with({
                    prompt: prompt,
                    history: history
                })
                .sendTo('assistant/chat')
                .then((response) => {
                    const reply = sanitizeAssistantReply(response?.data?.data?.reply || 'ZE AI se reply nahi mila. Dobara try karein.');
                    this.messages.push({
                        role: 'assistant',
                        content: reply
                    });
                    this.persistMessages();
                })
                .catch(() => {
                    this.messages.push({
                        role: 'assistant',
                        content: 'Request fail ho gaya. Internet/API config check karke phir try karein.'
                    });
                    this.persistMessages();
                });

            this.isLoading = false;
        }
    }
});

export { useAiAssistantStore };
