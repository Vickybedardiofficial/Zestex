<template>
    <div v-if="isVisible" class="tn-card mb-4" aria-label="Today's News panel">
        <div class="tn-head">
            <h2 class="tn-title">Today's News</h2>
            <button type="button" class="tn-close" aria-label="Close" @click="isVisible = false">
                <svg viewBox="0 0 24 24" aria-hidden="true" style="width: 1.25rem; height: 1.25rem; fill: currentColor; display: block;">
                    <path d="M19.707 5.707a1 1 0 0 0-1.414-1.414L12 10.586 5.707 4.293a1 1 0 0 0-1.414 1.414L10.586 12l-6.293 6.293a1 1 0 1 0 1.414 1.414L12 13.414l6.293 6.293a1 1 0 0 0 1.414-1.414L13.414 12l6.293-6.293z"></path>
                </svg>
            </button>
        </div>

        <div v-if="state.isLoading" class="tn-loading">
            Loading latest news...
        </div>

        <div v-else-if="headlines.length" class="tn-list">
            <article
                v-for="item in headlines"
                :key="item.id"
                class="tn-item"
                role="link"
                tabindex="0"
                @click="openNews(item)"
                @keydown.enter.prevent="openNews(item)"
            >
                <a
                    class="tn-item-link"
                    :href="newsHref(item)"
                    @click.prevent="openNews(item)"
                >
                    <h3 class="tn-item-title">{{ item.title }}</h3>
                </a>

                <div class="tn-meta-row">
                    <div class="tn-avatars" v-if="item.avatars && item.avatars.length">
                        <a
                            v-for="(avatar, idx) in item.avatars"
                            :key="item.id + '-' + idx"
                            class="tn-avatar-btn"
                            :href="profileHref(avatar.username)"
                            :style="{ marginLeft: idx === 0 ? '0px' : '-8px', zIndex: 10 - idx }"
                            :aria-label="'Open profile ' + avatar.username"
                            @click.stop.prevent="openProfile(avatar.username)"
                        >
                            <img
                                :src="avatar.avatar_url"
                                alt=""
                                class="tn-avatar"
                                @error="onAvatarError"
                            >
                        </a>
                    </div>

                    <p class="tn-meta">{{ item.meta }}</p>
                </div>
            </article>
        </div>

        <div v-else class="tn-empty">
            No fresh news yet.
        </div>
    </div>
</template>

<script>
import { defineComponent } from 'vue';
import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';

const fallbackAvatar = "data:image/svg+xml;utf8," + encodeURIComponent(
    "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'><rect width='48' height='48' rx='24' fill='#cbd5e1'/><circle cx='24' cy='18' r='8' fill='#ffffff'/><path d='M10 40c2-7 8-11 14-11s12 4 14 11' fill='#ffffff'/></svg>"
);

export default defineComponent({
    name: 'TodaysNews',
    data() {
        return {
            isVisible: true,
            state: {
                isLoading: true,
            },
            headlines: [],
            fallbackAvatar,
        };
    },
    async mounted() {
        await this.fetchNews();
    },
    methods: {
        async fetchNews() {
            this.state.isLoading = true;

            try {
                const response = await ZESTEXAPI().explore().with({
                    limit: 3,
                }).sendTo('news');

                const rows = response?.data?.data ?? [];
                this.headlines = Array.isArray(rows) ? rows : [];
            } catch (_) {
                this.headlines = [];
            } finally {
                this.state.isLoading = false;
            }
        },
        onAvatarError(event) {
            const target = event?.target;
            if (target) {
                target.src = this.fallbackAvatar;
            }
        },
        profileHref(username) {
            if (!username) {
                return '/explore/people';
            }
            return '/@' + encodeURIComponent(username);
        },
        newsHref(item) {
            return '/news/' + encodeURIComponent(item.slug);
        },
        goTo(routeConfig, fallbackUrl) {
            try {
                if (this.$router) {
                    this.$router.push(routeConfig);
                    return;
                }
            } catch (_) {
                // fallback below
            }

            window.location.assign(fallbackUrl);
        },
        openProfile(username) {
            if (!username) {
                this.goTo({ name: 'explore_people' }, '/explore/people');
                return;
            }

            this.goTo(
                { name: 'profile_index', params: { id: username } },
                this.profileHref(username)
            );
        },
        openNews(item) {
            this.goTo(
                { name: 'news_show', params: { slug: item.slug } },
                this.newsHref(item)
            );
        },
    },
});
</script>

<style scoped>
.tn-card {
    background: #ffffff;
    border: 1px solid #d7dee3;
    border-radius: 16px;
    padding: 12px 14px;
}

.tn-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.tn-title {
    margin: 0;
    color: #0f1419;
    font-size: 32px;
    font-weight: 800;
    line-height: 1.06;
}

.tn-close {
    border: 0;
    background: transparent;
    color: #0f1419;
    font-size: 26px;
    line-height: 1;
    cursor: pointer;
    padding: 0 2px;
}

.tn-loading,
.tn-empty {
    color: #536471;
    font-size: 14px;
    padding: 8px 0;
}

.tn-list {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.tn-item {
    outline: none;
    cursor: pointer;
}

.tn-item:hover .tn-item-title {
    text-decoration: underline;
}

.tn-item-link {
    color: inherit;
    text-decoration: none;
}

.tn-item-title {
    margin: 0 0 9px;
    color: #0f1419;
    font-size: 16px;
    font-weight: 800;
    line-height: 1.32;
}

.tn-meta-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.tn-avatars {
    display: flex;
    align-items: center;
}

.tn-avatar-btn {
    border: 0;
    background: transparent;
    padding: 0;
    cursor: pointer;
    display: inline-flex;
    position: relative;
}

.tn-avatar {
    width: 24px;
    height: 24px;
    border-radius: 9999px;
    border: 2px solid #ffffff;
    object-fit: cover;
    background: #d1d5db;
}

.tn-meta {
    margin: 0;
    color: #536471;
    font-size: 13px;
    font-weight: 400;
    line-height: 1.2;
}
</style>
