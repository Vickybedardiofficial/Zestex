<template>
    <div class="px-4 py-6">
        <div class="max-w-3xl mx-auto">
            <div class="mb-5">
                <button type="button" class="text-lab-sc text-par-s hover:underline" @click="$router.back()">Back</button>
            </div>

            <template v-if="state.isLoading">
                <p class="text-lab-sc text-par-s">Loading news...</p>
            </template>

            <template v-else>
                <h1 class="text-lab-pr2 text-par-l font-bold mb-3">{{ article.title }}</h1>
                <p class="text-lab-sc text-par-s mb-5">{{ article.meta }}</p>

                <div v-if="article.image_url" class="rounded-2xl border border-bord-pr bg-bg-pr mb-5 overflow-hidden">
                    <img :src="article.image_url" alt="News image" class="w-full h-auto" @error="hideBrokenImage">
                </div>

                <div class="rounded-2xl border border-bord-pr p-4 bg-bg-pr mb-5">
                    <p class="text-lab-pr2 text-par-m leading-relaxed">{{ article.body }}</p>
                </div>

                <div class="flex flex-wrap gap-2 mb-6" v-if="article.tags.length">
                    <span v-for="tag in article.tags" :key="tag" class="px-3 py-1 rounded-full text-cap-s bg-fill-pr text-lab-pr2">#{{ tag }}</span>
                </div>

                <div class="rounded-2xl border border-bord-pr p-4 bg-bg-pr" v-if="article.url">
                    <a :href="article.url" target="_blank" rel="noopener noreferrer" class="text-brand-900 hover:underline text-par-s">
                        Source: {{ article.source || 'Open article' }}
                    </a>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
import { defineComponent, reactive } from 'vue';
import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';

export default defineComponent({
    name: 'NewsShow',
    data() {
        return {
            state: reactive({
                isLoading: true,
            }),
            article: {
                title: 'News Detail',
                meta: 'Latest update',
                body: 'This news item is not available right now.',
                tags: ['news'],
                source: '',
                url: '',
                image_url: '',
            },
        };
    },
    async mounted() {
        await this.loadNews();
    },
    watch: {
        '$route.params.slug': {
            async handler() {
                await this.loadNews();
            },
        },
    },
    methods: {
        async loadNews() {
            this.state.isLoading = true;

            try {
                const slug = this.$route?.params?.slug || '';
                const response = await ZESTEXAPI().explore().with({ slug }).sendTo('news/show');
                const row = response?.data?.data || null;

                if (!row) {
                    this.article = {
                        title: 'News Detail',
                        meta: 'Latest update',
                        body: 'This news item is not available right now.',
                        tags: ['news'],
                        source: '',
                        url: '',
                        image_url: '',
                    };
                    return;
                }

                const title = row.title || 'News Detail';
                const description = row.description || 'No description available.';
                const source = row.source || 'Unknown';

                this.article = {
                    title,
                    meta: row.meta || 'Latest update',
                    body: description,
                    tags: this.buildTags(title, source),
                    source,
                    url: row.url || '',
                    image_url: row.image_url || '',
                };
            } catch (_) {
                this.article = {
                    title: 'News Detail',
                    meta: 'Latest update',
                    body: 'This news item is not available right now.',
                    tags: ['news'],
                    source: '',
                    url: '',
                    image_url: '',
                };
            } finally {
                this.state.isLoading = false;
            }
        },
        buildTags(title, source) {
            const tokens = `${title} ${source}`
                .toLowerCase()
                .replace(/[^a-z0-9\s]/g, ' ')
                .split(/\s+/)
                .filter((token) => token.length >= 4);

            const unique = [];
            for (const token of tokens) {
                if (!unique.includes(token)) {
                    unique.push(token);
                }
                if (unique.length === 4) {
                    break;
                }
            }

            return unique.length ? unique : ['news'];
        },
        hideBrokenImage(event) {
            if (event?.target) {
                event.target.style.display = 'none';
            }
        },
    },
});
</script>
