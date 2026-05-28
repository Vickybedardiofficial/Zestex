<template>
    <div class="flex flex-col gap-2">
        <SidebarGlobalSearch></SidebarGlobalSearch>

        <div class="block">
            <RouterLink v-bind:to="{ name: 'home_index' }" v-slot="{ isActive }" class="block">
                <div class="flex items-center" v-bind:class="[((isActive == true) ? 'sidenav-active' : 'sidenav-inactive')]">
                    <span class="size-icon-normal shrink-0">
                        <SvgIcon name="home-smile" v-bind:type="(isActive == true) ? 'solid' : 'line'"></SvgIcon>
                    </span>
                    <span class="ml-2.5 text-[16px]">
                        {{ $t('labels.home') }}
                    </span>
                </div>
            </RouterLink>
        </div>

        <div class="block">
            <RouterLink v-bind:to="{ name: 'explore_posts' }" v-slot="{ isActive }" class="block">
                <div class="flex items-center"  v-bind:class="[((isActive == true) ? 'sidenav-active' : 'sidenav-inactive')]">
                    <span class="size-icon-normal shrink-0">
                        <SvgIcon name="hash-02"></SvgIcon>
                    </span>
                    <span class="ml-2.5 text-[16px]">
                        {{ $t('labels.explore') }}
                    </span>
                </div>
            </RouterLink>
        </div>
        <div class="block">
            <div v-on:click="openZeAiModal" class="flex items-center sidenav-inactive cursor-pointer">
                <span class="size-icon-normal shrink-0">
                    <SvgIcon name="cpu-chip-02" type="line"></SvgIcon>
                </span>
                <span class="ml-2.5 text-[16px] font-semibold">
                    ZE AI
                </span>
            </div>
        </div>
        <div class="block">
            <div v-on:click="openNotificationsModal" class="flex items-center sidenav-inactive cursor-pointer">
                <span class="size-icon-normal shrink-0">
                    <SvgIcon name="bell-01" type="line"></SvgIcon>
                </span>
                <span class="ml-2.5 text-[16px]">
                    {{ $t('labels.notifications') }}

                    <BadgeCounter v-if="notificationsCount.raw" v-bind:count="notificationsCount.formatted"></BadgeCounter>
                </span>
            </div>
        </div>
        <div class="block">
            <RouterLink v-bind:to="{ name: 'messenger_index' }" v-slot="{ isActive }" class="block">
                <div class="flex items-center"  v-bind:class="[((isActive == true) ? 'sidenav-active' : 'sidenav-inactive')]">
                    <span class="size-icon-normal shrink-0">
                        <SvgIcon name="message-chat-circle" v-bind:type="(isActive == true) ? 'solid' : 'line'"></SvgIcon>
                    </span>
                    <span class="ml-2.5 text-[16px]">
                        {{ $t('labels.messages') }}

                        <BadgeCounter v-if="inboxCount.raw" v-bind:count="inboxCount.formatted"></BadgeCounter>
                    </span>
                </div>
            </RouterLink>
        </div>
        <div class="block" v-if="$config('features.marketplace.enabled')">
            <RouterLink v-bind:to="{ name: 'marketplace_index' }" v-slot="{ isActive }" class="block">
                <div class="flex items-center" v-bind:class="[((isActive == true) ? 'sidenav-active' : 'sidenav-inactive')]">
                    <span class="size-icon-normal shrink-0">
                        <SvgIcon name="shopping-bag-03" v-bind:type="(isActive == true) ? 'solid' : 'line'"></SvgIcon>
                    </span>
                    <span class="ml-2.5 text-[16px]">
                        {{ $t('labels.marketplace') }}
                    </span>
                </div>
            </RouterLink>
        </div>
        <div class="block" v-if="$config('features.jobs.enabled')">
            <RouterLink v-bind:to="{ name: 'jobs_index' }" v-slot="{ isActive }" class="block">
                <div  class="flex items-center" v-bind:class="[((isActive == true) ? 'sidenav-active' : 'sidenav-inactive')]">
                    <span class="size-icon-normal shrink-0">
                        <SvgIcon name="briefcase-01" v-bind:type="(isActive == true) ? 'solid' : 'line'"></SvgIcon>
                    </span>
                    <span class="ml-2.5 text-[16px]">
                        {{ $t('labels.jobs') }}
                    </span>
                </div>
            </RouterLink>
        </div>
        
        <div class="block">
            <RouterLink v-bind:to="{ name: 'profile_index', params: { id: userData.username } }" v-slot="{ isActive }" class="block">
                <div  class="flex items-center" v-bind:class="[((isActive == true) ? 'sidenav-active' : 'sidenav-inactive')]">
                    <span class="size-icon-normal shrink-0">
                        <SvgIcon name="user-01" type="line"></SvgIcon>
                    </span>
                    <span class="ml-2.5 text-[16px]">
                        {{ $t('labels.my_profile') }}
                    </span>
                </div>
            </RouterLink>
        </div>
        <div class="block" v-if="isAdmin">
            <a v-bind:href="adminPanelUrl" target="_blank" class="block">
                <div class="flex items-center sidenav-inactive">
                    <span class="size-icon-normal shrink-0">
                        <SvgIcon name="shield-02" type="line"></SvgIcon>
                    </span>
                    <span class="ml-2.5 text-[16px]">
                        {{ $t('labels.admin_panel') }}
                    </span>
                </div>
            </a>
        </div>
        <div class="block pl-icon-normal pr-6">
            <span class="block bg-bord-sc h-px mx-3"></span>
        </div>
        <div class="block">
            <NavbarDropdown></NavbarDropdown>
        </div>
    </div>
</template>

<script>
    import { defineComponent, computed, defineAsyncComponent, onMounted, onUnmounted } from 'vue';
    import { useAuthStore } from '@D/store/auth/auth.store.js';
    import { useNotificationsStore } from '@D/store/notifications/notifications.store.js';
    import { useInboxStore } from '@D/store/chats/inbox.store.js';
    import { useAiAssistantStore } from '@D/store/ai/assistant.store.js';
    import { ZESTEXSounds } from '@/kernel/services/sounds/index.js';
    import { ZESTEXEventBus } from '@/kernel/events/bus/index.js';

    import BadgeCounter from '@D/components/general/counters/BadgeCounter.vue';
    import BRD from '@/kernel/websockets/brd/index.js';

    export default defineComponent({
        setup: function() {
            const authStore = useAuthStore();
            const notificationsStore = useNotificationsStore();
            const inboxStore = useInboxStore();
            const aiAssistantStore = useAiAssistantStore();
            const notificationsCount = computed(() => {
                return notificationsStore.unreadCount;
            });

            const inboxCount = computed(() => {
                return inboxStore.unreadCount;
            });
            let notificationsPollTimer = null;
            let previousUnreadCount = 0;

            onMounted(() => {
                if(localStorage.getItem('notificationsSound') === null) {
                    localStorage.setItem('notificationsSound', 1);
                }

                notificationsStore.fetchUnreadCount();
                previousUnreadCount = Number(notificationsStore.unreadCount?.raw ?? 0);

                inboxStore.fetchUnreadCount();

                if(window.ZESTEXBRD) {
                    ZESTEXBRD.private(BRD.getChannel('AUTH_USER', [authStore.userData.id])).notification(function (event) {
                        if(event.type === 'chat.notification') {
                            inboxStore.fetchUnreadCount();
                        }
                        else {
                            notificationsStore.setUnreadNotificationsCount(event.data);
                            ZESTEXEventBus.emit('notifications:received');
                        }

                        if(localStorage.getItem('notificationsSound') === '1') {
                            if(event.type === 'chat.notification') {
                                ZESTEXSounds.backgroundChatMessageReceived();
                            }
                            else {
                                ZESTEXSounds.notificationReceived();
                            }
                        }
                    });
                }

                // Fallback polling so unread badge/sound keeps working when realtime broadcast is unavailable.
                notificationsPollTimer = setInterval(async () => {
                    await notificationsStore.fetchUnreadCount();

                    const currentUnreadCount = Number(notificationsStore.unreadCount?.raw ?? 0);

                    if(currentUnreadCount > previousUnreadCount) {
                        ZESTEXEventBus.emit('notifications:received');

                        if(localStorage.getItem('notificationsSound') === '1') {
                            ZESTEXSounds.notificationReceived();
                        }
                    }

                    previousUnreadCount = currentUnreadCount;
                }, 15000);
            });

            onUnmounted(() => {
                if(window.ZESTEXBRD) {
                    ZESTEXBRD.leave(BRD.getChannel('AUTH_USER', [authStore.userData.id]));
                }

                if(notificationsPollTimer) {
                    clearInterval(notificationsPollTimer);
                    notificationsPollTimer = null;
                }
            });

            return {
                notificationsCount: notificationsCount,
                inboxCount: inboxCount,
                userData: authStore.userData,
                isAdmin: computed(() => {
                    return authStore.userData.meta.is_admin;
                }),
                adminPanelUrl: computed(() => {
                    return authStore.userData.meta.admin.url;
                }),
                openNotificationsModal: () => {
                    notificationsStore.openNotifications();
                },
                openZeAiModal: () => {
                    aiAssistantStore.open();
                }
            };
        },
        components: {
            SidebarGlobalSearch: defineAsyncComponent(() => {
                return import('@D/components/layout/parts/navbar/SidebarGlobalSearch.vue');
            }),
            NavbarDropdown: defineAsyncComponent(() => {
                return import('@D/components/layout/parts/navbar/NavbarDropdown.vue');
            }),
            BadgeCounter: BadgeCounter
        }
    });
</script>
