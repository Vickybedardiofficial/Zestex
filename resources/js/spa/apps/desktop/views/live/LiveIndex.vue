<template>
    <div class="mt-top-offset block">
        <div class="mb-6">
            <PageTitle v-bind:hasBack="true" v-bind:titleText="$t('labels.create_live_stream')"></PageTitle>
        </div>
        <div class="min-w-content w-content">
            <div class="block rounded-2xl border border-bord-pr bg-edge-pr p-6">
                <div class="mb-6">
                    <h3 class="text-par-l font-semibold text-lab-pr2 mb-2">Start Live Stream</h3>
                    <p class="text-par-m text-lab-sc">
                        This starts your live session announcement instantly and publishes it to your timeline.
                    </p>
                </div>

                <form v-on:submit.prevent="startLive" class="block">
                    <div class="mb-5">
                        <TextInput
                            v-model="formData.title"
                            v-bind:textLength="120"
                            labelText="Live title"
                            placeholder="What are you streaming right now?"
                        ></TextInput>
                    </div>

                    <div class="mb-5">
                        <TextInput
                            v-model="formData.description"
                            v-bind:asText="true"
                            v-bind:textLength="1200"
                            labelText="Description"
                            placeholder="Tell people what they will get in this live session."
                        ></TextInput>
                    </div>

                    <div class="mb-7">
                        <TextInput
                            v-model="formData.hashtags"
                            v-bind:textLength="120"
                            labelText="Hashtags"
                            labelTextBrackets="optional"
                            placeholder="#Live #LiveNow #Streaming"
                        >
                            <template v-slot:feedbackInfo>
                                Add hashtags separated by spaces.
                            </template>
                        </TextInput>
                    </div>

                    <PrimaryPillButton
                        buttonType="submit"
                        v-bind:loading="state.isSubmitting"
                        v-bind:isDisabled="formData.title.trim().length < 4"
                        buttonText="Start Live Now"
                    ></PrimaryPillButton>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
    import { defineComponent, reactive } from 'vue';
    import { useRouter } from 'vue-router';
    
    import PageTitle from '@D/components/layout/PageTitle.vue';
    import TextInput from '@D/components/forms/TextInput.vue';
    import PrimaryPillButton from '@D/components/inter-ui/buttons/PrimaryPillButton.vue';
    import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';

    export default defineComponent({
        setup: function() {
            const router = useRouter();

            const state = reactive({
                isSubmitting: false
            });

            const formData = reactive({
                title: '',
                description: '',
                hashtags: '#Live #LiveNow #Streaming'
            });

            return {
                state: state,
                formData: formData,
                startLive: async () => {
                    if (state.isSubmitting || formData.title.trim().length < 4) {
                        return;
                    }

                    state.isSubmitting = true;

                    await ZESTEXAPI().userTimeline().with({
                        title: formData.title,
                        description: formData.description,
                        hashtags: formData.hashtags,
                    }).sendTo('live/start').then((response) => {
                        const postData = response?.data?.data;

                        toastSuccess(response?.data?.message || 'Live stream started.');

                        if (postData?.hash_id) {
                            router.push({
                                name: 'publication_index',
                                params: {
                                    hash_id: postData.hash_id
                                }
                            });
                        } else {
                            router.push({ name: 'home_index' });
                        }
                    }).catch((error) => {
                        toastError(error?.response?.data?.message || 'Unable to start live stream.');
                    });

                    state.isSubmitting = false;
                }
            };
        },
        components: {
            PageTitle: PageTitle,
            TextInput: TextInput,
            PrimaryPillButton: PrimaryPillButton
        }
    });
</script>
