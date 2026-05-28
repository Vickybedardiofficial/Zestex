import { defineStore } from "pinia";
import { ZESTEXAPI } from "@/kernel/services/api-client/native/index.js";
import { PostType } from "@/kernel/enums/post/post.type.js";

const usePostEditorStore = defineStore("mobile_post_editor_store", {
    state: function () {
        return {
            draftPost: {},
            quotedPost: null,
            quotePostId: null,
            mentionName: null,
            initialType: PostType.TEXT,
        };
    },
    getters: {
        pollChoices: (state) => {
            return state.draftPost.relations.poll.choices;
        },
    },
    actions: {
        fetchDraftPost: async function () {
            let state = this;

            await ZESTEXAPI()
                .postEditor()
                .params({
                    quoted_post_id: this.quotePostId,
                })
                .getFrom("draft")
                .then((response) => {
                    if (response.data.data.draft) {
                        state.draftPost = response.data.data.draft;
                    } else {
                        state.draftPost = this.getDraftPostDefaultValue();
                    }

                    if (response.data.data.quoted_post) {
                        state.quotedPost = response.data.data.quoted_post;
                        state.draftPost.is_quoting = true;
                        state.draftPost.quote_post_id = state.quotedPost.id;
                    }
                })
                .catch((response) => {
                    state.draftPost = this.getDraftPostDefaultValue();
                });
        },
        pollHasChoices: function () {
            return this.draftPost?.relations?.poll?.choices?.length > 0;
        },
        setPollChoices: function (choicesArr) {
            this.draftPost.relations.poll.choices = choicesArr;
        },
        resetDraftPost: function () {
            this.draftPost = this.getDraftPostDefaultValue();
        },
        setDraftPost: function (postData) {
            this.draftPost = postData;
        },
        setQuotePostId: function (postId) {
            this.quotePostId = postId;
        },
        removeQuote: function () {
            this.quotePostId = null;
            this.quotedPost = null;

            if (this.draftPost) {
                this.draftPost.is_quoting = false;
                this.draftPost.quote_post_id = null;
            }
        },
        finishEditing: function () {
            this.initialType = PostType.TEXT;
            this.mentionName = null;
            this.quotePostId = null;
            this.quotedPost = null;
            this.resetDraftPost();
        },
        getDraftPostDefaultValue: function () {
            let content = "";

            if (this.mentionName) {
                content = `@${this.mentionName} `;
            }

            return {
                content: content,
                type: PostType.TEXT,
                relations: {},
            };
        },
    },
});

export { usePostEditorStore };
