<template>
    <div class="w-[300px] bg-bg-pr border border-bord-pr rounded-2xl shadow-xl overflow-hidden pointer-events-auto transition-all duration-200">
        <!-- Cover Photo -->
        <div class="h-20 bg-bord-pr relative overflow-hidden">
            <img v-if="userData.cover_url" v-bind:src="userData.cover_url" class="w-full h-full object-cover opacity-80" alt="Cover">
        </div>

        <div class="px-4 pb-4 -mt-8 relative">
            <div class="flex justify-between items-end mb-3">
                <!-- Avatar -->
                <div class="p-1 bg-bg-pr rounded-full inline-block">
                    <AvatarNormal v-bind:avatarSrc="userData.avatar_url" v-bind:hasBorder="false"></AvatarNormal>
                </div>
                
                <!-- Follow Button -->
                <div v-if="! userData.is_me">
                    <FollowPillButton v-bind:followableId="userData.id" v-bind:relationship="userData.meta.relationship.follow"></FollowPillButton>
                </div>
            </div>

            <!-- Identity -->
            <div class="mb-3">
                <h3 class="text-par-l font-bold text-lab-pr2 leading-tight flex items-center gap-1">
                    {{ userData.name }}
                    <VerificationBadge v-if="userData.verified" size="xs"></VerificationBadge>
                </h3>
                <p class="text-par-n text-lab-sc">
                    {{ userData.caption }}
                </p>
            </div>

            <!-- Bio -->
            <div v-if="userData.description" class="mb-3 text-par-m text-lab-pr2 line-clamp-3 markdown-text" v-html="$mdInline(userData.description)"></div>

            <!-- Stats -->
            <div class="flex items-center gap-4 text-par-n">
                <div class="flex items-center gap-1">
                    <span class="font-bold text-lab-pr2 underline-offset-2 hover:underline cursor-pointer">
                        {{ userData.followers_count.formatted }}
                    </span>
                    <span class="text-lab-sc">{{ $t('labels.followers_count', userData.followers_count.raw) }}</span>
                </div>
                <div v-if="userData.following_count" class="flex items-center gap-1">
                    <span class="font-bold text-lab-pr2 underline-offset-2 hover:underline cursor-pointer">
                        {{ userData.following_count.formatted }}
                    </span>
                    <span class="text-lab-sc">{{ $t('labels.following_count') }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { defineComponent } from 'vue';
    import AvatarNormal from '@D/components/general/avatars/AvatarNormal.vue';
    import FollowPillButton from '@D/components/inter-ui/buttons/follows/FollowPillButton.vue';

    export default defineComponent({
        props: {
            userData: {
                type: Object,
                required: true
            }
        },
        components: {
            AvatarNormal: AvatarNormal,
            FollowPillButton: FollowPillButton
        }
    });
</script>

<style scoped>
    .markdown-text :deep(a) {
        color: var(--color-brand-900);
    }
</style>
