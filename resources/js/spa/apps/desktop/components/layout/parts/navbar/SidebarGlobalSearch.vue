<template>
    <div class="block mb-1 relative" v-outside-click="closeDropdown">
        <div class="relative">
            <span class="absolute top-0 bottom-0 left-3 inline-flex-center text-lab-tr">
                <SvgIcon name="search-lg" classes="size-icon-small"></SvgIcon>
            </span>

            <input
                ref="searchInput"
                v-model.trim="query"
                v-on:focus="handleFocus"
                v-on:keydown.down.prevent="handleArrowDown"
                v-on:keydown.up.prevent="handleArrowUp"
                v-on:keydown.enter.prevent="handleEnter"
                v-on:keydown.esc="closeDropdown"
                type="text"
                v-bind:placeholder="inputPlaceholder"
                class="w-full h-11 rounded-2xl border border-bord-card bg-fill-pr/95 pl-10 pr-10 text-par-m text-lab-pr2 outline-hidden placeholder:text-lab-tr shadow-[0_2px_10px_rgba(0,0,0,0.06)] focus:border-brand-900 focus:ring-2 focus:ring-brand-900/10 transition-all"
            >

            <button
                v-if="query.length"
                v-on:click="clearSearch"
                type="button"
                class="absolute top-1.5 right-2 inline-flex-center h-8 px-2 rounded-full bg-fill-qt text-par-s text-lab-tr hover:text-lab-pr2 transition-colors"
            >
                Clear
            </button>
        </div>

        <div
            v-if="isDropdownVisible"
            class="absolute left-0 right-0 mt-2 z-[140] rounded-2xl border border-bord-card/80 bg-fill-pr/98 shadow-[0_20px_45px_rgba(0,0,0,0.18)] backdrop-blur-sm overflow-hidden"
        >
            <div class="px-4 py-2.5 border-b border-bord-card/70 bg-fill-qt/70 flex items-center justify-between">
                <div class="text-[11px] uppercase tracking-wide text-lab-sc">Search Results</div>
                <div class="text-par-s text-lab-tr">{{ totalResults }} found</div>
            </div>

            <div v-if="query.trim().length < 2" class="px-4 py-3">
                <p class="text-par-s text-lab-sc">Type at least 2 characters.</p>
                <div v-if="recentSearches.length" class="mt-3">
                    <div class="text-[11px] uppercase tracking-wide text-lab-sc mb-2">Recent</div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="(item, index) in recentSearches"
                            v-bind:key="`recent-${index}`"
                            v-on:click="applyRecentSearch(item)"
                            type="button"
                            class="px-2.5 py-1 rounded-full bg-fill-qt text-par-s text-lab-pr2 hover:bg-fill-sc transition-colors"
                        >
                            {{ item }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-else-if="isLoading" class="px-4 py-4 text-par-s text-lab-sc">
                Searching...
            </div>

            <div v-else-if="hasResults" class="max-h-[500px] overflow-y-auto search-scroll">
                <div v-if="results.users.length" class="py-2">
                    <div class="px-4 pb-1 text-[11px] uppercase tracking-wide text-lab-sc">{{ $t('labels.people') }}</div>
                    <a
                        v-for="item in results.users"
                        v-bind:key="`user-${item.id}`"
                        v-bind:href="item.url"
                        v-on:mouseenter="highlightByType('users', item.id)"
                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-fill-qt/90 transition-colors"
                        v-bind:class="{ 'bg-fill-qt/90': isHighlighted('users', item.id) }"
                    >
                        <img v-bind:src="item.avatar_url" alt="" class="size-small-avatar rounded-full object-cover shrink-0">
                        <div class="min-w-0">
                            <p class="text-par-s font-semibold text-lab-pr2 truncate">{{ item.name }}</p>
                            <p class="text-par-s text-lab-sc truncate">@{{ item.username }}</p>
                        </div>
                    </a>
                </div>

                <div v-if="results.posts.length" class="py-2 border-t border-bord-card">
                    <div class="px-4 pb-1 text-[11px] uppercase tracking-wide text-lab-sc">{{ $t('labels.posts') }}</div>
                    <a
                        v-for="item in results.posts"
                        v-bind:key="`post-${item.id}`"
                        v-bind:href="item.url"
                        v-on:mouseenter="highlightByType('posts', item.id)"
                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-fill-qt/90 transition-colors"
                        v-bind:class="{ 'bg-fill-qt/90': isHighlighted('posts', item.id) }"
                    >
                        <img
                            v-if="item.avatar_url"
                            v-bind:src="item.avatar_url"
                            alt=""
                            class="size-small-avatar rounded-full object-cover shrink-0"
                        >
                        <div class="min-w-0">
                            <p class="text-par-s font-semibold text-lab-pr2 truncate">{{ item.title }}</p>
                            <p v-if="item.username" class="text-par-s text-lab-sc truncate">@{{ item.username }}</p>
                        </div>
                    </a>
                </div>

                <div v-if="results.products.length" class="py-2 border-t border-bord-card">
                    <div class="px-4 pb-1 text-[11px] uppercase tracking-wide text-lab-sc">{{ $t('labels.marketplace') }}</div>
                    <a
                        v-for="item in results.products"
                        v-bind:key="`product-${item.id}`"
                        v-bind:href="item.url"
                        v-on:mouseenter="highlightByType('products', item.id)"
                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-fill-qt/90 transition-colors"
                        v-bind:class="{ 'bg-fill-qt/90': isHighlighted('products', item.id) }"
                    >
                        <img v-bind:src="item.preview_image_url" alt="" class="size-small-avatar rounded-lg object-cover shrink-0">
                        <div class="min-w-0">
                            <p class="text-par-s font-semibold text-lab-pr2 truncate">{{ item.title }}</p>
                            <p class="text-par-s text-lab-sc truncate">{{ item.price }}</p>
                        </div>
                    </a>
                </div>

                <div v-if="results.jobs.length" class="py-2 border-t border-bord-card">
                    <div class="px-4 pb-1 text-[11px] uppercase tracking-wide text-lab-sc">{{ $t('labels.jobs') }}</div>
                    <a
                        v-for="item in results.jobs"
                        v-bind:key="`job-${item.id}`"
                        v-bind:href="item.url"
                        v-on:mouseenter="highlightByType('jobs', item.id)"
                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-fill-qt/90 transition-colors"
                        v-bind:class="{ 'bg-fill-qt/90': isHighlighted('jobs', item.id) }"
                    >
                        <span class="inline-flex-center size-small-avatar rounded-lg bg-fill-qt text-lab-sc shrink-0">
                            <SvgIcon name="briefcase-01" classes="size-icon-small"></SvgIcon>
                        </span>
                        <div class="min-w-0">
                            <p class="text-par-s font-semibold text-lab-pr2 truncate">{{ item.title }}</p>
                            <p class="text-par-s text-lab-sc truncate">{{ item.income }}</p>
                        </div>
                    </a>
                </div>
            </div>

            <div v-else class="px-4 py-4 text-par-s text-lab-sc">
                No results found.
            </div>
        </div>
    </div>
</template>

<script>
    import { defineComponent, reactive, computed, watch, ref } from 'vue';
    import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';

    export default defineComponent({
        setup() {
            const RECENT_SEARCHES_KEY = 'zestex_recent_searches_v1';
            const state = reactive({
                query: '',
                isLoading: false,
                isOpen: false,
                debounceTimer: null,
                highlightedIndex: -1,
                recentSearches: [],
                results: {
                    users: [],
                    posts: [],
                    products: [],
                    jobs: []
                }
            });
            const searchInput = ref(null);
            const flatResults = computed(() => {
                return [
                    ...state.results.users.map((item) => ({ type: 'users', id: item.id, url: item.url })),
                    ...state.results.posts.map((item) => ({ type: 'posts', id: item.id, url: item.url })),
                    ...state.results.products.map((item) => ({ type: 'products', id: item.id, url: item.url })),
                    ...state.results.jobs.map((item) => ({ type: 'jobs', id: item.id, url: item.url }))
                ];
            });

            const resetResults = () => {
                state.results = {
                    users: [],
                    posts: [],
                    products: [],
                    jobs: []
                };
                state.highlightedIndex = -1;
            };

            const loadRecentSearches = () => {
                try {
                    const parsed = JSON.parse(localStorage.getItem(RECENT_SEARCHES_KEY) ?? '[]');
                    state.recentSearches = Array.isArray(parsed) ? parsed.slice(0, 6) : [];
                }
                catch (error) {
                    state.recentSearches = [];
                }
            };

            const saveRecentSearch = (term) => {
                const value = term.trim();

                if (!value.length) {
                    return;
                }

                const next = [value, ...state.recentSearches.filter((item) => item.toLowerCase() !== value.toLowerCase())]
                    .slice(0, 6);

                state.recentSearches = next;
                localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(next));
            };

            const navigateToSearchPage = () => {
                const term = state.query.trim();

                if (!term.length) {
                    return;
                }

                saveRecentSearch(term);
                const encoded = encodeURIComponent(term);
                window.location.href = `/search?q=${encoded}&src=typed_query`;
            };

            const search = async () => {
                const query = state.query.trim();

                if (query.length < 2) {
                    resetResults();
                    state.isLoading = false;
                    return;
                }

                state.isLoading = true;

                try {
                    const response = await ZESTEXAPI()
                        .autocompletes()
                        .with({ query: query })
                        .sendTo('global');

                    state.results = response?.data?.data ?? {
                        users: [],
                        posts: [],
                        products: [],
                        jobs: []
                    };
                    state.highlightedIndex = flatResults.value.length ? 0 : -1;
                }
                catch (error) {
                    resetResults();
                }
                finally {
                    state.isLoading = false;
                }
            };

            watch(() => state.query, () => {
                if (state.debounceTimer) {
                    clearTimeout(state.debounceTimer);
                }

                if (!state.query.trim().length) {
                    resetResults();
                    state.isOpen = false;
                    return;
                }

                state.isOpen = true;
                state.debounceTimer = setTimeout(search, 280);
            });

            loadRecentSearches();

            return {
                query: computed({
                    get() {
                        return state.query;
                    },
                    set(value) {
                        state.query = value;
                    }
                }),
                results: computed(() => state.results),
                inputPlaceholder: computed(() => {
                    return 'Search people, posts, marketplace, jobs';
                }),
                isLoading: computed(() => state.isLoading),
                hasResults: computed(() => {
                    return (
                        state.results.users.length > 0
                        || state.results.posts.length > 0
                        || state.results.products.length > 0
                        || state.results.jobs.length > 0
                    );
                }),
                totalResults: computed(() => {
                    return state.results.users.length
                        + state.results.posts.length
                        + state.results.products.length
                        + state.results.jobs.length;
                }),
                recentSearches: computed(() => state.recentSearches),
                flatResults,
                isDropdownVisible: computed(() => {
                    return state.isOpen && state.query.trim().length > 0;
                }),
                isHighlighted: (type, id) => {
                    const target = flatResults.value[state.highlightedIndex];
                    return target?.type === type && target?.id === id;
                },
                highlightByType: (type, id) => {
                    const index = flatResults.value.findIndex((item) => item.type === type && item.id === id);

                    if (index >= 0) {
                        state.highlightedIndex = index;
                    }
                },
                handleArrowDown: () => {
                    if (!flatResults.value.length) {
                        return;
                    }

                    if (!state.isOpen) {
                        state.isOpen = true;
                    }

                    state.highlightedIndex = (state.highlightedIndex + 1) % flatResults.value.length;
                },
                handleArrowUp: () => {
                    if (!flatResults.value.length) {
                        return;
                    }

                    if (!state.isOpen) {
                        state.isOpen = true;
                    }

                    const nextIndex = state.highlightedIndex - 1;
                    state.highlightedIndex = nextIndex < 0 ? flatResults.value.length - 1 : nextIndex;
                },
                handleEnter: () => {
                    navigateToSearchPage();
                },
                applyRecentSearch: (value) => {
                    state.query = value;
                    state.isOpen = true;
                    searchInput.value?.focus();
                },
                handleFocus: () => {
                    if (state.query.trim().length) {
                        state.isOpen = true;
                    }
                    else {
                        state.isOpen = true;
                    }
                },
                clearSearch: () => {
                    state.query = '';
                    resetResults();
                    state.isOpen = false;
                },
                closeDropdown: () => {
                    state.isOpen = false;
                },
                navigateToSearchPage,
                searchInput
            };
        }
    });
</script>

<style scoped>
    .search-scroll::-webkit-scrollbar {
        width: 8px;
    }

    .search-scroll::-webkit-scrollbar-thumb {
        background: rgba(100, 116, 139, 0.35);
        border-radius: 999px;
    }

    .search-scroll::-webkit-scrollbar-thumb:hover {
        background: rgba(100, 116, 139, 0.5);
    }
</style>
