<template>
    <SidedContentLayout>
        <template v-slot:content>
            <TimelineContainer>
                <div class="sticky top-0 popup-background-tr z-10">
                    <div class="p-4 pb-8">
                        <div class="mb-3">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    class="shrink-0 inline-flex-center size-small-avatar rounded-full text-lab-pr2 hover:bg-fill-qt transition-colors"
                                    @click="$router.back()"
                                >
                                    <SvgIcon name="arrow-left" classes="size-icon-normal"></SvgIcon>
                                </button>

                                <div class="flex-1">
                                    <SearchBar v-model.trim="state.localQuery" v-bind:placeholder="$t('labels.search')"></SearchBar>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-end mb-2">
                            <h4 class="text-par-l text-lab-pr2 font-semibold truncate">
                                Search Results
                            </h4>
                        </div>

                        <nav class="flex items-center gap-1 overflow-x-auto search-tabs-scroll">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                type="button"
                                class="px-4 py-3 text-par-s font-semibold rounded-t-xl whitespace-nowrap border-b-2 transition-colors"
                                :class="activeFilter === tab.key ? 'text-lab-pr2 border-brand-900' : 'text-lab-sc border-transparent hover:text-lab-pr2'"
                                @click="setFilter(tab.key)"
                            >
                                {{ tab.label }}
                            </button>
                        </nav>
                    </div>
                </div>

                <div class="px-4 pb-4 -mt-3">
                    <div v-if="state.isLoading" class="py-8 text-center text-lab-sc text-par-m">Searching...</div>
                    <div v-else-if="showLists" class="mb-5 rounded-2xl border border-bord-card bg-fill-pr overflow-hidden">
                        <div class="px-4 py-3 border-b border-bord-card text-par-s font-semibold text-lab-pr2">Lists</div>
                        <div class="px-4 py-6 text-par-m text-lab-sc">
                            No list results found for "{{ activeQuery }}".
                        </div>
                    </div>
                    <div v-else-if="!hasAnyResults" class="py-8 text-center text-lab-sc text-par-m">No results found for "{{ activeQuery }}".</div>

                    <template v-else>
                        <section v-if="showPeople && results.users.length" class="mb-5">
                            <div class="py-2 flex items-center justify-between gap-3">
                                <span class="text-par-s font-semibold text-lab-pr2">
                                    {{ activeFilter === 'top' ? 'Popular profiles' : 'People' }}
                                </span>
                                <button
                                    v-if="activeFilter === 'top' && results.users.length"
                                    type="button"
                                    class="text-par-s font-semibold text-brand-900 hover:underline"
                                    @click="setFilter('people')"
                                >
                                    See all
                                </button>
                            </div>
                            <div
                                v-for="item in results.users"
                                :key="`u-${item.id}`"
                                class="relative py-3 border-b border-bord-tr last:border-b-0"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div
                                        class="min-w-0 flex items-center gap-3"
                                        @mouseenter="onUserMouseEnter(item)"
                                        @mouseleave="onUserMouseLeave"
                                    >
                                        <RouterLink :to="{ name: 'profile_index', params: { id: item.username } }" class="shrink-0">
                                            <img :src="item.avatar_url" alt="" class="size-small-avatar rounded-full object-cover">
                                        </RouterLink>
                                        <div class="min-w-0">
                                            <RouterLink :to="{ name: 'profile_index', params: { id: item.username } }" class="text-par-m text-lab-pr2 truncate font-semibold hover:underline block">
                                                {{ item.name }}
                                            </RouterLink>
                                            <RouterLink :to="{ name: 'profile_index', params: { id: item.username } }" class="text-par-s text-lab-sc truncate hover:underline block">
                                                @{{ item.username }}
                                            </RouterLink>
                                        </div>
                                    </div>

                                    <div v-if="!item.is_me" class="shrink-0">
                                        <FollowPillButton
                                            :followableId="item.id"
                                            :relationship="item.meta?.relationship?.follow ?? {}"
                                        ></FollowPillButton>
                                    </div>
                                </div>

                                <div
                                    v-if="state.hoveredUser && state.hoveredUser.id === item.id"
                                    class="absolute top-full left-0 mt-2 z-40 shadow-2xl"
                                    @mouseenter="onCardMouseEnter"
                                    @mouseleave="onCardMouseLeave"
                                >
                                    <UserHoverCard :userData="state.hoveredUser"></UserHoverCard>
                                </div>
                            </div>
                        </section>

                        <section v-if="showPosts && results.posts.length" class="mb-5 rounded-2xl border border-bord-card bg-fill-pr overflow-hidden">
                            <div class="px-4 py-3 border-b border-bord-card text-par-s font-semibold text-lab-pr2">{{ activeFilter === 'latest' ? 'Latest posts' : 'Top posts' }}</div>
                            <a v-for="item in results.posts" :key="`p-${item.id}`" :href="item.url" class="flex gap-3 px-4 py-3 hover:bg-fill-qt">
                                <img v-if="item.avatar_url" :src="item.avatar_url" alt="" class="size-small-avatar rounded-full object-cover shrink-0">
                                <div class="min-w-0">
                                    <p class="text-par-m text-lab-pr2 line-clamp-2">{{ item.title }}</p>
                                    <p class="text-par-s text-lab-sc mt-1">
                                        <span v-if="item.username">@{{ item.username }}</span>
                                        <span v-if="item.created_at"> | {{ item.created_at }}</span>
                                        <span v-if="item.reactions_count !== undefined"> | {{ item.reactions_count }} likes</span>
                                        <span v-if="item.comments_count !== undefined"> | {{ item.comments_count }} comments</span>
                                    </p>
                                </div>
                            </a>
                            <div v-if="canLoadMorePosts" class="px-4 py-3 border-t border-bord-card">
                                <button
                                    type="button"
                                    class="w-full py-2 rounded-xl text-par-s font-semibold border border-bord-card text-lab-pr2 hover:bg-fill-qt disabled:opacity-50"
                                    :disabled="state.isLoadingMore"
                                    @click="loadMoreResults"
                                >
                                    {{ state.isLoadingMore ? 'Loading...' : 'See more posts' }}
                                </button>
                            </div>
                        </section>

                        <section v-if="showMedia && results.media.length" class="mb-5 rounded-2xl border border-bord-card bg-fill-pr overflow-hidden">
                            <div class="px-4 py-3 border-b border-bord-card text-par-s font-semibold text-lab-pr2">Media</div>
                            <a v-for="item in results.media" :key="`m-${item.id}`" :href="item.url" class="flex gap-3 px-4 py-3 hover:bg-fill-qt">
                                <img
                                    v-if="item.preview_image_url"
                                    :src="item.preview_image_url"
                                    alt=""
                                    class="h-14 w-14 rounded-lg object-cover shrink-0 border border-bord-card"
                                >
                                <div class="min-w-0">
                                    <p class="text-par-m text-lab-pr2 line-clamp-2">{{ item.title }}</p>
                                    <p class="text-par-s text-lab-sc mt-1">
                                        <span v-if="item.username">@{{ item.username }}</span>
                                        <span v-if="item.created_at"> | {{ item.created_at }}</span>
                                    </p>
                                </div>
                            </a>
                        </section>

                <section v-if="showMarketplace && results.products.length" class="mb-5 rounded-2xl border border-bord-card bg-fill-pr overflow-hidden">
                    <div class="px-4 py-3 border-b border-bord-card text-par-s font-semibold text-lab-pr2">Marketplace</div>
                    <a v-for="item in results.products" :key="`pr-${item.id}`" :href="item.url" class="flex gap-3 px-4 py-3 hover:bg-fill-qt">
                        <img :src="item.preview_image_url" alt="" class="h-14 w-14 rounded-lg object-cover shrink-0 border border-bord-card">
                        <div class="min-w-0">
                            <p class="text-par-m text-lab-pr2 line-clamp-2">{{ item.title }}</p>
                            <p class="text-par-s text-lab-sc mt-1">{{ item.price }}</p>
                        </div>
                    </a>
                </section>

                <section v-if="showJobs && results.jobs.length" class="mb-5 rounded-2xl border border-bord-card bg-fill-pr overflow-hidden">
                    <div class="px-4 py-3 border-b border-bord-card text-par-s font-semibold text-lab-pr2">Jobs</div>
                    <a v-for="item in results.jobs" :key="`j-${item.id}`" :href="item.url" class="flex gap-3 px-4 py-3 hover:bg-fill-qt">
                        <span class="inline-flex-center h-14 w-14 rounded-lg border border-bord-card bg-fill-qt text-lab-sc shrink-0">
                            <SvgIcon name="briefcase-01" classes="size-icon-small"></SvgIcon>
                        </span>
                        <div class="min-w-0">
                            <p class="text-par-m text-lab-pr2 line-clamp-2">{{ item.title }}</p>
                            <p class="text-par-s text-lab-sc mt-1">{{ item.income }}</p>
                        </div>
                    </a>
                </section>
                    </template>
                </div>
            </TimelineContainer>
        </template>

        <template v-slot:sidebar>
            <div class="mb-4 rounded-2xl border border-bord-card bg-fill-pr overflow-hidden">
                <div class="px-4 py-3 border-b border-bord-card">
                    <h5 class="text-lab-pr2 font-semibold text-par-l">Refine Search</h5>
                </div>

                <div class="px-4 py-3 border-b border-bord-card">
                    <p class="text-par-s font-semibold text-lab-pr2 mb-2">People Source</p>
                    <label class="flex items-center justify-between gap-3 py-1.5 cursor-pointer">
                        <span class="text-par-m text-lab-pr2">From everyone</span>
                        <input
                            type="radio"
                            name="people-scope"
                            value="anyone"
                            :checked="peopleScope === 'anyone'"
                            @change="updateSidebarFilter('people_scope', 'anyone')"
                        >
                    </label>
                    <label class="flex items-center justify-between gap-3 py-1.5 cursor-pointer">
                        <span class="text-par-m text-lab-pr2">From followed accounts</span>
                        <input
                            type="radio"
                            name="people-scope"
                            value="following"
                            :checked="peopleScope === 'following'"
                            @change="updateSidebarFilter('people_scope', 'following')"
                        >
                    </label>
                </div>

                <div class="px-4 py-3 border-b border-bord-card">
                    <p class="text-par-s font-semibold text-lab-pr2 mb-2">Location Scope</p>
                    <label class="flex items-center justify-between gap-3 py-1.5 cursor-pointer">
                        <span class="text-par-m text-lab-pr2">Anywhere</span>
                        <input
                            type="radio"
                            name="location-scope"
                            value="anywhere"
                            :checked="locationScope === 'anywhere'"
                            @change="updateSidebarFilter('location_scope', 'anywhere')"
                        >
                    </label>
                    <label class="flex items-center justify-between gap-3 py-1.5 cursor-pointer">
                        <span class="text-par-m text-lab-pr2">Near me</span>
                        <input
                            type="radio"
                            name="location-scope"
                            value="nearby"
                            :checked="locationScope === 'nearby'"
                            @change="updateSidebarFilter('location_scope', 'nearby')"
                        >
                    </label>
                </div>

                <div class="px-4 py-3">
                    <a href="/search-advanced" class="text-par-s text-brand-900 hover:underline">Advanced search</a>
                </div>
            </div>

            <div class="mb-4">
                <div class="mb-2">
                    <h5 class="text-lab-pr2 font-semibold text-par-l">Suggested Profiles</h5>
                </div>
                <div v-if="state.isSuggestionsLoading" class="py-3 text-par-s text-lab-sc">Loading suggestions...</div>
                <template v-else-if="followSuggestions.length">
                    <FollowListItem
                        v-for="userData in followSuggestions"
                        :key="`s-${userData.id}`"
                        :userData="userData"
                    ></FollowListItem>
                    <RouterLink :to="{ name: 'explore_people' }" class="text-par-s hover:underline text-lab-sc cursor-pointer">
                        More suggestions
                    </RouterLink>
                </template>
                <p v-else class="text-par-s text-lab-sc">No suggestions yet.</p>
            </div>

            <AdGridItem></AdGridItem>
        </template>
    </SidedContentLayout>

    <ScrollTopButton></ScrollTopButton>
</template>

<script>
    import { computed, defineAsyncComponent, defineComponent, onMounted, reactive, watch } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';
    import { useRecommendStore } from '@D/store/recommend/recommend.store.js';
    import SidedContentLayout from '@D/components/layout/SidedContentLayout.vue';
    import TimelineContainer from '@D/components/layout/TimelineContainer.vue';
    import SearchBar from '@D/components/general/search/SearchBar.vue';
    import AdGridItem from '@D/components/ads/AdGridItem.vue';
    import ScrollTopButton from '@D/components/inter-ui/buttons/ScrollTopButton.vue';

    export default defineComponent({
        setup() {
            const route = useRoute();
            const router = useRouter();

            const state = reactive({
                isLoading: false,
                isLoadingMore: false,
                isSuggestionsLoading: true,
                localQuery: '',
                queryTimer: null,
                hoverTimer: null,
                hoveredUser: null,
                currentPage: 1,
                hasMorePosts: false,
                hasMoreMedia: false,
                hasMoreProducts: false,
                hasMoreJobs: false,
                peopleScope: 'anyone',
                locationScope: 'anywhere',
                results: {
                    users: [],
                    posts: [],
                    media: [],
                    products: [],
                    jobs: [],
                    lists: [],
                }
            });

            const recommendStore = useRecommendStore();

            const tabs = [
                { key: 'top', label: 'Top' },
                { key: 'latest', label: 'Latest' },
                { key: 'people', label: 'People' },
                { key: 'media', label: 'Media' },
                { key: 'lists', label: 'Lists' },
                { key: 'marketplace', label: 'Marketplace' },
                { key: 'jobs', label: 'Jobs' },
            ];

            const activeQuery = computed(() => String(route.query.q ?? '').trim());
            const activeFilter = computed(() => String(route.query.f ?? 'top'));
            const peopleScope = computed(() => String(route.query.people_scope ?? state.peopleScope));
            const locationScope = computed(() => String(route.query.location_scope ?? state.locationScope));

            const showPeople = computed(() => ['top', 'people'].includes(activeFilter.value));
            const showPosts = computed(() => ['top', 'latest'].includes(activeFilter.value));
            const showMedia = computed(() => ['top', 'media'].includes(activeFilter.value));
            const showLists = computed(() => activeFilter.value === 'lists');
            const showMarketplace = computed(() => ['top', 'marketplace'].includes(activeFilter.value));
            const showJobs = computed(() => ['top', 'jobs'].includes(activeFilter.value));

            const hasAnyResults = computed(() => {
                return state.results.users.length
                    || state.results.posts.length
                    || state.results.media.length
                    || state.results.products.length
                    || state.results.jobs.length
                    || state.results.lists.length;
            });

            const resetResults = () => {
                state.results = {
                    users: [],
                    posts: [],
                    media: [],
                    products: [],
                    jobs: [],
                    lists: [],
                };
                state.currentPage = 1;
                state.hasMorePosts = false;
                state.hasMoreMedia = false;
                state.hasMoreProducts = false;
                state.hasMoreJobs = false;
                state.hoveredUser = null;
            };

            const loadResults = async (append = false) => {
                const q = activeQuery.value;

                if (!q.length) {
                    resetResults();
                    return;
                }

                if (append) {
                    state.isLoadingMore = true;
                }
                else {
                    state.isLoading = true;
                }

                try {
                    const response = await ZESTEXAPI()
                        .autocompletes()
                        .with({
                            query: q,
                            filter: activeFilter.value,
                            page: state.currentPage,
                            limit: 20
                        })
                        .sendTo('search');

                    const payload = response?.data?.data ?? {};
                    const nextResults = payload?.results ?? {
                        users: [],
                        posts: [],
                        media: [],
                        products: [],
                        jobs: [],
                        lists: [],
                    };
                    const pagination = payload?.pagination ?? {};

                    if (append) {
                        if (showPosts.value) {
                            state.results.posts = [...state.results.posts, ...(nextResults.posts ?? [])];
                        }
                        if (showMedia.value) {
                            state.results.media = [...state.results.media, ...(nextResults.media ?? [])];
                        }
                        if (showMarketplace.value) {
                            state.results.products = [...state.results.products, ...(nextResults.products ?? [])];
                        }
                        if (showJobs.value) {
                            state.results.jobs = [...state.results.jobs, ...(nextResults.jobs ?? [])];
                        }
                    }
                    else {
                        state.results = nextResults;
                    }

                    state.hasMorePosts = Boolean(pagination.has_more_posts);
                    state.hasMoreMedia = Boolean(pagination.has_more_media);
                    state.hasMoreProducts = Boolean(pagination.has_more_products);
                    state.hasMoreJobs = Boolean(pagination.has_more_jobs);
                }
                catch (error) {
                    if (!append) {
                        resetResults();
                    }
                }
                finally {
                    if (append) {
                        state.isLoadingMore = false;
                    }
                    else {
                        state.isLoading = false;
                    }
                }
            };

            const canLoadMorePosts = computed(() => {
                if (activeFilter.value === 'top' || activeFilter.value === 'latest') {
                    return state.hasMorePosts;
                }

                return false;
            });

            const loadMoreResults = async () => {
                if (state.isLoadingMore) {
                    return;
                }

                state.currentPage += 1;
                await loadResults(true);
            };

            const setFilter = (filterKey) => {
                router.push({
                    path: '/search',
                    query: {
                        q: activeQuery.value,
                        src: 'typed_query',
                        f: filterKey,
                        people_scope: peopleScope.value,
                        location_scope: locationScope.value
                    }
                });
            };

            const submitSearch = () => {
                const term = state.localQuery.trim();

                if (!term.length) {
                    return;
                }

                router.push({
                    path: '/search',
                    query: {
                        q: term,
                        src: 'typed_query',
                        f: activeFilter.value || 'top',
                        people_scope: peopleScope.value,
                        location_scope: locationScope.value
                    }
                });
            };

            const updateSidebarFilter = (key, value) => {
                router.push({
                    path: '/search',
                    query: {
                        q: activeQuery.value,
                        src: 'typed_query',
                        f: activeFilter.value,
                        people_scope: key === 'people_scope' ? value : peopleScope.value,
                        location_scope: key === 'location_scope' ? value : locationScope.value
                    }
                });
            };

            const onUserMouseEnter = (user) => {
                if (state.hoverTimer) {
                    clearTimeout(state.hoverTimer);
                }

                state.hoverTimer = setTimeout(() => {
                    state.hoveredUser = user;
                }, 150);
            };

            const onUserMouseLeave = () => {
                if (state.hoverTimer) {
                    clearTimeout(state.hoverTimer);
                }

                state.hoverTimer = setTimeout(() => {
                    state.hoveredUser = null;
                }, 220);
            };

            const onCardMouseEnter = () => {
                if (state.hoverTimer) {
                    clearTimeout(state.hoverTimer);
                }
            };

            const onCardMouseLeave = () => {
                onUserMouseLeave();
            };

            watch(
                () => state.localQuery,
                () => {
                    if (state.queryTimer) {
                        clearTimeout(state.queryTimer);
                    }

                    state.queryTimer = setTimeout(() => {
                        const term = state.localQuery.trim();

                        if (!term.length || term === activeQuery.value) {
                            return;
                        }

                        submitSearch();
                    }, 300);
                }
            );

            watch(
                () => route.fullPath,
                () => {
                    state.localQuery = activeQuery.value;
                    state.peopleScope = peopleScope.value || 'anyone';
                    state.locationScope = locationScope.value || 'anywhere';
                    state.currentPage = 1;
                    loadResults();
                }
            );

            onMounted(() => {
                state.localQuery = activeQuery.value;
                state.peopleScope = peopleScope.value || 'anyone';
                state.locationScope = locationScope.value || 'anywhere';
                loadResults();
                recommendStore.fetchFollowRecommendations().finally(() => {
                    state.isSuggestionsLoading = false;
                });
            });

            return {
                state,
                tabs,
                results: computed(() => state.results),
                hasAnyResults,
                activeQuery,
                activeFilter,
                peopleScope,
                locationScope,
                showPeople,
                showPosts,
                showMedia,
                showLists,
                showMarketplace,
                showJobs,
                canLoadMorePosts,
                setFilter,
                submitSearch,
                loadMoreResults,
                updateSidebarFilter,
                onUserMouseEnter,
                onUserMouseLeave,
                onCardMouseEnter,
                onCardMouseLeave,
                followSuggestions: computed(() => recommendStore.followRecommendations)
            };
        },
        components: {
            SidedContentLayout,
            TimelineContainer,
            SearchBar,
            UserHoverCard: defineAsyncComponent(() => import('@D/components/general/UserHoverCard.vue')),
            FollowPillButton: defineAsyncComponent(() => import('@D/components/inter-ui/buttons/follows/FollowPillButton.vue')),
            FollowListItem: defineAsyncComponent(() => import('@D/components/recommend/follow/list/FollowListItem.vue')),
            AdGridItem,
            ScrollTopButton
        }
    });
</script>

<style scoped>
    .search-tabs-scroll::-webkit-scrollbar {
        height: 5px;
    }

    .search-tabs-scroll::-webkit-scrollbar-thumb {
        border-radius: 999px;
        background: rgba(100, 116, 139, 0.35);
    }
</style>
