<template>
    <div class="relative border-b border-b-bord-tr last:border-none">
        <div class="absolute top-4 left-4 z-40" v-on:mouseenter="onUserMouseEnter" v-on:mouseleave="onUserMouseLeave">
            <AvatarSmall v-bind:avatarSrc="postData.relations.user.avatar_url" ></AvatarSmall>
        </div>
        <div class="px-4 pt-4 max-w-full">
            <div class="ml-small-avatar pl-2">
                <div class="mb-1">
                    <div class="flex items-center">
                        <div class="leading-4 flex-1 relative" v-on:mouseenter="onUserMouseEnter" v-on:mouseleave="onUserMouseLeave">
                            <RouterLink v-bind:to="{ name: 'profile_index', params: { id: postData.relations.user.username } }" class="flex cursor-pointer gap-1 relative">
                                <h3 class="text-par-m text-lab-pr2 truncate">
                                    <span class="flex items-center gap-1">
                                        <span class="shrink-0 font-semibold">
                                            {{ postData.relations.user.name }}
                                        </span>
                                        <VerificationBadge v-if="postData.relations.user.verified"></VerificationBadge>
                                        <span v-if="showAgentBadge" title="AI Agent" class="size-icon-x-small inline-block text-amber-500">
                                            <SvgIcon name="ai-icon"></SvgIcon>
                                        </span>
                                    </span>
                                </h3>
                                <span class="text-par-n text-lab-sc truncate">
                                    {{ postUserCaption }} - {{ postData.date.time_ago }}
                                </span>
                                <span v-if="isPromoted" class="text-[11px] uppercase tracking-wide font-semibold text-brand-900">
                                    {{ promotedLabel }}
                                </span>
                            </RouterLink>

                            <PrimaryTransition>
                                <div v-if="state.isUserHovered" class="absolute top-full left-0 mt-2 z-50 shadow-2xl" v-on:mouseenter="onCardMouseEnter" v-on:mouseleave="onCardMouseLeave">
                                    <UserHoverCard v-bind:userData="postData.relations.user"></UserHoverCard>
                                </div>
                            </PrimaryTransition>
                        </div>
                    </div>
                </div>
                <div class="max-w-full">
                    <template v-if="postHasContent">
                        <div class="overflow-hidden mb-4">
                            <div v-if="postData.meta.is_translatable" class="leading-none mb-1">
                                <TextTranslateButton
                                    v-if="state.isTranslated"
                                    v-on:click="cancelTranslation"
                                v-bind:buttonText="$t('labels.show_untranslated')"></TextTranslateButton>

                                <TextTranslateButton
                                    v-else
                                    v-on:click="translate"
                                v-bind:buttonText="state.isTranslating ? $t('labels.translating') : $t('labels.translate_to', { language_name: userLocaleName })"></TextTranslateButton>
                            </div>
                            <div v-if="isLiveStreamPost" class="mb-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-bold uppercase tracking-wide text-red-700">Live</span>
                                    <RouterLink
                                        class="text-par-s font-medium text-red-700 underline"
                                        v-bind:to="{ name: 'profile_index', params: { id: postData.relations.user.username } }"
                                    >
                                        Open host profile
                                    </RouterLink>
                                </div>
                                <p class="text-par-s text-red-900 mt-1 truncate">{{ liveStreamTitle }}</p>
                            </div>
                            <PublicationText v-bind:postContent="postContent"></PublicationText>

                            <div v-if="state.isTranslated" class="mt-2">
								<TranslationService></TranslationService>
							</div>
                            <div v-if="isPromoted && promotedTargetUrl" class="mt-3">
                                <a :href="promotedTargetUrl" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-md bg-brand-900 px-3 py-1.5 text-par-s font-semibold text-white hover:opacity-90">
                                    {{ promotedCTA }}
                                </a>
                            </div>
                        </div>
                    </template>
                    <div class="overflow-hidden mb-2" v-if="postHasMedia">
                        <template v-if="PostTypeUtils.isImage(postData.type)">
                            <div v-on:click="lightboxImages" class="block cursor-pointer rounded-2xl overflow-hidden border border-bord-card">
                                <PublicationImage v-bind:key="postData.hash_id" v-bind:isSensitive="isSensitive" v-bind:postMedia="postMedia"></PublicationImage>
                            </div>
                        </template>
                        <template v-if="PostTypeUtils.isGif(postData.type)">
                            <PublicationGif v-on:click="lightboxImages" v-bind:postMedia="postMedia"></PublicationGif>
                        </template>
                        <template v-else-if="PostTypeUtils.isVideo(postData.type)">
                            <PublicationVideo v-bind:postMedia="postMedia"></PublicationVideo>
                        </template>
                        <template v-else-if="PostTypeUtils.isDocument(postData.type)">
                            <PublicationDocument v-bind:postMedia="postMedia"></PublicationDocument>
                        </template>
                        <template v-else-if="PostTypeUtils.isAudio(postData.type)">
                            <PublicationAudio v-bind:postMedia="postMedia" v-bind:key="postData.id"></PublicationAudio>
                        </template>
                    </div>
                    <div class="overflow-hidden mb-4" v-else-if="postHasPoll">
                        <PublicationPoll v-bind:postPoll="postPoll"></PublicationPoll>
                    </div>
                    <div v-else-if="postData.meta.is_quoting" class="overflow-hidden mb-2">
                        <PublicationQuote v-if="quotedPost" v-bind:quotedPost="quotedPost" v-bind:key="postData.id"></PublicationQuote>
                        <PublicationQuotePlaceholder v-else></PublicationQuotePlaceholder>
                    </div>
                    <div v-else-if="postLinkSnapshot" class="overflow-hidden mb-2">
                        <a v-bind:href="postLinkSnapshot.url" target="_blank">
                            <LinkSnapshot v-bind:linkSnapshot="postLinkSnapshot"></LinkSnapshot>
                        </a>
                    </div>
                    <div class="block" v-if="!isPromoted && postReactions.length">
                        <ReactionsViewer v-on:add="addReaction" v-bind:reactions="postReactions"></ReactionsViewer>
                    </div>
                    <div v-if="!isPromoted" class="block mb-3 -ml-1">
                        <div class="flex items-center">
                            <div class="shrink-0 relative leading-zero">
                                <PrimaryIconButton iconSize="icon-normal" v-on:click.stop="openReactionsPicker" iconName="heart-rounded" iconType="line"></PrimaryIconButton>
                                <PrimaryTransition v-if="state.isReactionPickerOpen">
                                    <div class="absolute left-0 bottom-8 origin-top-left z-20">
                                        <ReactionsPicker
                                            v-on:add="addReaction"
                                        v-outside-click="closeReactionsPicker"></ReactionsPicker>
                                    </div>
                                </PrimaryTransition>
                            </div>
                            
                            <div class="shrink-0 leading-zero relative">
                                <PrimaryIconButton v-on:click.stop="sharePost" iconSize="icon-normal" iconName="share-06" iconType="line"></PrimaryIconButton>
								<PrimaryTransition v-if="state.isSharePostOpen">
									<div class="absolute left-0 bottom-8 origin-top-left z-20">
										<PublicationShare v-outside-click="cancelSharePost" v-on:click.stop="cancelSharePost" v-bind:postLink="postLink"></PublicationShare>
									</div>
								</PrimaryTransition>
							</div>

                            <div class="shrink-0 leading-zero relative">
                                <div class="inline-flex items-center">
                                    <PrimaryIconButton
                                        v-on:click.stop="openRepostMenu"
                                        iconSize="icon-normal"
                                        iconName="repost-01"
                                        iconType="line"
                                        v-bind:buttonColor="postData.meta.activity.reposted ? 'text-brand-900' : 'text-lab-pr2'"
                                    ></PrimaryIconButton>

                                    <span v-if="postData.quotes_count && postData.quotes_count.raw" class="text-lab-sc text-par-s font-mono">
                                        {{ postData.quotes_count.formatted }}
                                    </span>
                                </div>

                                <PrimaryTransition v-if="state.isRepostMenuOpen">
                                    <div class="absolute left-0 bottom-8 origin-top-left z-20">
                                        <DropdownMenu v-outside-click="closeRepostMenu">
                                            <DropdownMenuItem
                                                v-on:click.stop="toggleRepost"
                                                iconName="repost-01"
                                            v-bind:textLabel="postData.meta.activity.reposted ? $t('dd.post.undo_repost') : $t('dd.post.repost')"></DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-on:click.stop="quotePostFromRepostMenu"
                                                iconName="pencil-line"
                                            v-bind:textLabel="$t('dd.post.quote_post')"></DropdownMenuItem>
                                        </DropdownMenu>
                                    </div>
                                </PrimaryTransition>
                            </div>
                            <div v-if="! postData.relations.comments.length && ! isOnPostPage" class="shrink-0">
                                <RouterLink v-bind:to="{ name: 'publication_index', params: { hash_id: postData.hash_id }}">
                                    <PrimaryIconButton iconSize="icon-normal" iconName="message-circle-02" iconType="line"></PrimaryIconButton>
                                </RouterLink>
                            </div>

                            <template v-if="isOnPostPage">
                                <PrimaryIconButton v-bind:disabled="true" iconSize="icon-normal" iconName="message-circle-02" iconType="line"></PrimaryIconButton>
                            </template>
                            
                            <div class="flex-1 overflow-hidden">
                                <div v-if="! isOnPostPage" class="flex items-center h-x-small-avatar">
                                    <div v-if="postData.relations.comments.length" class="flex ml-1">
                                        <div v-for="comment in postData.relations.comments" v-bind:key="comment.id" class="-ml-2 first:ml-0 border rounded-full border-fill-pr">
                                            <AvatarExtraSmall v-bind:avatarSrc="comment.user.avatar_url"></AvatarExtraSmall>
                                        </div>
                                    </div>
                                    
                                    <div class="flex-1 overflow-hidden ml-2">
                                        <RouterLink v-bind:to="{ name: 'publication_index', params: { hash_id: postData.hash_id }}" class="text-par-s text-lab-sc truncate block hover:text-brand-900">
                                            <template v-if="postData.relations.comments.length">
                                                {{ $t('labels.show_all_comments') }} ({{ postData.comments_count.formatted }})
                                            </template>
                                            <template v-else>
                                                {{ $t('labels.leave_comment') }}
                                            </template>
                                        </RouterLink>
                                    </div>
                                </div>
                            </div>
                            <div class="shrink-0 self-end">
                                <ViewsCounter v-bind:counterValue="postData.views_count.formatted"></ViewsCounter>
                            </div>
                        </div>
                    </div>
                    <div v-else class="block mb-3 -ml-1">
                        <div class="flex items-center">
                            <div class="flex-1"></div>
                            <div class="shrink-0 self-end">
                                <ViewsCounter v-bind:counterValue="postData.views_count.formatted"></ViewsCounter>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="absolute top-2 right-2.5">
            <div class="relative leading-none inline-flex items-center gap-1">
                <span
                    v-if="showAgentBadge"
                    class="text-[11px] uppercase tracking-wide font-semibold text-amber-600"
                >
                    Agent
                </span>
                <span v-if="isPromoted" class="text-[11px] uppercase tracking-wide font-semibold text-brand-900 mr-1">
                    {{ promotedLabel }}
                </span>
                <div v-if="!isPromoted" class="opacity-30 hover:opacity-100">
                    <DropdownButton v-on:click.stop="toggleMenu"></DropdownButton>
                </div>
                
                <div class="absolute top-full right-0 z-50" v-if="!isPromoted && state.isMenuOpen">
                    <DropdownMenu v-outside-click="toggleMenu" v-on:click="toggleMenu">
                        <DropdownReactions v-on:add="addReaction"></DropdownReactions>
                        <DropdownMenuItem v-on:click="openReactionsPicker" iconName="heart-rounded" v-bind:textLabel="$t('dd.add_reaction')"></DropdownMenuItem>
                        
                        <template v-if="postHasContent && isTranslatable">
                            <DropdownMenuItem
                                v-if="state.isTranslated"
                                v-on:click="cancelTranslation"
                                iconName="translate-01"
                            v-bind:textLabel="$t('labels.show_untranslated')"></DropdownMenuItem>

                            <DropdownMenuItem
                                v-else
                                v-on:click="translate"
                                iconName="translate-01"
                            v-bind:textLabel="$t('dd.translate')"></DropdownMenuItem>
                        </template>
                        <Border/>
                        <template v-if="! isOnPostPage">
                            <RouterLink v-bind:to="{ name: 'publication_index', params: { hash_id: postData.hash_id }}">
                                <DropdownMenuItem iconName="arrow-up-right" v-bind:textLabel="$t('dd.post.open_post')"></DropdownMenuItem>
                            </RouterLink>
                        </template>

                        <DropdownMenuItem v-on:click="quotePost" iconName="pencil-line" v-bind:textLabel="$t('dd.post.quote_post')"></DropdownMenuItem>
                        <DropdownMenuItem v-on:click="mentionAuthor" iconName="at-sign" v-bind:textLabel="$t('dd.post.mention_author', { name: `@${postData.relations.user.name}`})"></DropdownMenuItem>
                        <Border/>
                        <DropdownMenuItem
                            v-on:click="bookmarkPost"
                            v-bind:iconName="postData.meta.activity.bookmarked ? 'bookmark-minus' : 'bookmark'"
                        v-bind:textLabel="postData.meta.activity.bookmarked ? $t('dd.post.unbookmark') : $t('dd.post.bookmark')"></DropdownMenuItem>

                        <DropdownMenuItem v-on:click="sharePost" iconName="share-06" v-bind:textLabel="$t('dd.post.share')"></DropdownMenuItem>
                        <DropdownMenuItem v-on:click="copyLink" iconName="copy-06" v-bind:textLabel="$t('dd.post.copy_link')"></DropdownMenuItem>
                        <DropdownMenuItem v-if="postHasContent" v-on:click="copyContent" iconName="type-01" v-bind:textLabel="$t('dd.copy_text')"></DropdownMenuItem>
                        <Border/>
                        <DropdownMenuItem v-if="canReportPost" v-on:click="reportPost" itemColor="text-red-900" iconName="annotation-alert" v-bind:textLabel="$t('dd.post.report_post')"></DropdownMenuItem>
                        <template v-if="canDeletePost">
                            <DropdownMenuItem v-on:click="$emit('delete', postData)" itemColor="text-red-900" iconName="trash-04" v-bind:textLabel="$t('dd.post.delete_post')"></DropdownMenuItem>
                        </template>
                    </DropdownMenu>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { defineComponent, defineAsyncComponent, reactive, computed, ref } from 'vue';
    import { useRoute } from 'vue-router';
    import { PostTypeUtils } from '@/kernel/enums/post/post.type.js';
    import { ZESTEXEventBus } from '@/kernel/events/bus/index.js';
    import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';
    import { useLightboxStore } from '@D/store/lightbox/lightbox.store.js';
    import { ZESTEXTranslator } from '@/kernel/services/translator/index.js';

    import AvatarSmall from '@D/components/general/avatars/AvatarSmall.vue';
    import DropdownButton from '@D/components/general/dropdowns/parts/DropdownButton.vue';
    import DropdownMenu from '@D/components/general/dropdowns/parts/DropdownMenu.vue';
    import DropdownMenuItem from '@D/components/general/dropdowns/parts/DropdownMenuItem.vue';
    import DropdownReactions from '@D/components/general/dropdowns/parts/DropdownReactions.vue';
    import ViewsCounter from '@/kernel/vue/components/general/counters/ViewsCounter.vue';
    import PrimaryIconButton from '@D/components/inter-ui/buttons/PrimaryIconButton.vue';
    import TextTranslateButton from '@D/components/inter-ui/buttons/TextTranslateButton.vue';
    import TranslationService from '@D/components/general/TranslationService.vue';
    import PublicationQuote from '@D/components/timeline/feed/parts/quote/PublicationQuote.vue';
    import UserHoverCard from '@D/components/general/UserHoverCard.vue';

    // This component is used to display a publication in the timeline feed.
    // It is used in the BookmarksPostsPage component.
    // Changes to this component will affect the timeline feed and the bookmarks page.

    export default defineComponent({
        props: {
            postData: {
                type: Object,
                default: {}
            }
        },
        setup: function(props) {
            const route = useRoute();
            const state = reactive({
                isMenuOpen: false,
                isReactionPickerOpen: false,
                isTranslating: false,
                isTranslated: false,
                isSharePostOpen: false,
                isRepostMenuOpen: false,
                isUserHovered: false
            });

            let hoverTimeout = null;
            
            const postTranslatedContent = ref('');
            const postData = computed(() => {
                return props.postData;
            });

            const lightboxStore = useLightboxStore();

            const openReactionsPicker = function() {
                state.isMenuOpen = false;
                state.isReactionPickerOpen = true;
            }

            const closeReactionsPicker = function() {
                state.isReactionPickerOpen = false;
            }

            const postContent = computed(() => {
                return state.isTranslated ? postTranslatedContent.value : postData.value.content;
            });

            const postLink = computed(() => {
                return base_url(`publication/${postData.value.hash_id}`);
            });

            const onUserMouseEnter = () => {
                clearTimeout(hoverTimeout);
                hoverTimeout = setTimeout(() => {
                    state.isUserHovered = true;
                }, 200);
            };

            const onUserMouseLeave = () => {
                clearTimeout(hoverTimeout);
                hoverTimeout = setTimeout(() => {
                    state.isUserHovered = false;
                }, 300);
            };

            const onCardMouseEnter = () => {
                clearTimeout(hoverTimeout);
            };

            const onCardMouseLeave = () => {
                onUserMouseLeave();
            };

            return {
                toggleMenu: () => {
                    state.isMenuOpen = !state.isMenuOpen;
                },
                postContent: postContent,
                openReactionsPicker: openReactionsPicker,
                PostTypeUtils: PostTypeUtils,
                closeReactionsPicker: closeReactionsPicker,
                onUserMouseEnter: onUserMouseEnter,
                onUserMouseLeave: onUserMouseLeave,
                onCardMouseEnter: onCardMouseEnter,
                onCardMouseLeave: onCardMouseLeave,
                postData: postData,
                state: state,
                isSensitive: computed(() => {
                    return postData.value.meta.is_sensitive;
                }),
                showAgentBadge: computed(() => {
                    const value = postData.value?.relations?.user?.is_ai_agent;
                    return value === true || value === 1 || value === '1';
                }),
                isPromoted: computed(() => {
                    return !!postData.value?.meta?.is_promoted;
                }),
                promotedLabel: computed(() => {
                    return postData.value?.meta?.promoted_label || 'Ad';
                }),
                promotedCTA: computed(() => {
                    return postData.value?.meta?.cta || 'Learn more';
                }),
                promotedTargetUrl: computed(() => {
                    return postData.value?.meta?.target_url || null;
                }),
                isLiveStreamPost: computed(() => {
                    return !!postData.value.meta?.is_live_stream;
                }),
                liveStreamTitle: computed(() => {
                    const content = (postContent.value || '').trim();
                    const firstLine = content.split('\n')[0] || 'Live stream is now active';
                    return firstLine.replace(/^(?:🔴\s*)?LIVE NOW:\s*/i, '').trim() || 'Live stream is now active';
                }),
                userLocaleName: embedder('locale_name'),
                quickLike: () => {
                    return ZESTEXAPI().userTimeline().with({
                        unified_id: '1f44d',
                        post_id: postData.value.id
                    }).sendTo('post/reaction/add').then((response) => {
                        postData.value.relations.reactions = response.data.data;
                    }).catch((error) => {
                        if (error.response) {
                            toastError(error.response.data.message);
                        }
                    });
                },
                addReaction: (reactionId) => {
                    closeReactionsPicker();

                    ZESTEXAPI().userTimeline().with({
                        unified_id: reactionId,
                        post_id: postData.value.id
                    }).sendTo('post/reaction/add').then((response) => {
                        postData.value.relations.reactions = response.data.data;
                    }).catch((error) => {
                        if (error.response) {
                            toastError(error.response.data.message);
                        }
                    });
                },
                postHasMedia: computed(() => {
                    return postData.value.relations.media?.length;
                }),
                postHasPoll: computed(() => {
                    return postData.value.relations.poll;
                }),
                postHasContent: computed(() => {
                    return postData.value.content.length;
                }),
                postLinkSnapshot: computed(() => {
                    return postData.value.relations.link_snapshot;
                }),
                quotedPost: computed(() => {
                    return postData.value.relations.quoted_post;
                }),
                isTranslatable: computed(() => {
                    return postData.value.meta.is_translatable;
                }),
                postMedia: computed(() => {
                    return postData.value.relations.media;
                }),
                postPoll: computed(() => {
                    return postData.value.relations.poll;
                }),
				postLink: postLink,
                postReactions: computed(() => {
                    return postData.value.relations.reactions;
                }),
                lightboxImages: () => {
                    lightboxStore.add({
                        albumName: `${postData.value.relations.user.name} ${postData.value.relations.user.caption}`,
                        date: postData.value.date.iso,
                        images: postData.value.relations.media.map((item) => {
                            return item.source_url;
                        })
                    });
                },
                postUserCaption: computed(() => {
                    return postData.value.relations.user.caption;
                }),
                canDeletePost: computed(() => {
                    return postData.value.meta.permissions.can_delete;
                }),
                canReportPost: computed(() => {
                    return postData.value.meta.permissions.can_report;
                }),
                mentionAuthor: () => {
                    ZESTEXEventBus.emit('post-editor:open', {
                        mentionName: postData.value.relations.user.username
                    });
                },
                bookmarkPost: () => {
                    ZESTEXAPI().userTimeline().with({
                        id: postData.value.id
                    }).sendTo('post/bookmarks/add').then((response) => {
                        postData.value.meta.activity.bookmarked = response.data.data.bookmarked;

                        if(response.data.data.bookmarked) {
                            toastSuccess(__t('toast.post.bookmarked'));
                        }
                        else {
                            toastSuccess(__t('toast.post.unbookmarked'));
                        }
                    }).catch((error) => {
                        if (error.response) {
                            toastError(error.response.data.message);
                        }
                    });
                },
                translate: async () => {
                    if (state.isTranslating || state.isTranslated) {
                        return false;
                    }

                    state.isTranslating = true;
                    const translatedText = await ZESTEXTranslator().translate(postContent.value);

                    if (translatedText) {
                        postTranslatedContent.value = translatedText;
                        state.isTranslated = true;
                    }
                    
                    state.isTranslating = false;
                },
                cancelTranslation: () => {
                    state.isTranslated = false;
                    postTranslatedContent.value = '';
                },
                copyContent: () => {
                    navigator.clipboard.writeText(postContent.value).then(() => {
                        toastSuccess(__t('toast.post.content_copied'), 1000);
                    });
                },
                copyLink: () => {
                    navigator.clipboard.writeText(postLink.value).then(() => {
                        toastSuccess(__t('toast.post.link_copied'), 1000);
                    });
                },
                reportPost: () => {
                    ZESTEXEventBus.emit('report:open', {
                        type: 'post',
                        reportableId: postData.value.id
                    });
                },
				quotePost: () => {
					ZESTEXEventBus.emit('post-editor:open', {
						quotePostId: postData.value.id
					});
				},
                sharePost: async () => {
					debounce(() => {
						state.isSharePostOpen = true;
					}, 50);
                },
                cancelSharePost: () => {
					state.isSharePostOpen = false;
				},
                openRepostMenu: () => {
                    debounce(() => {
                        state.isRepostMenuOpen = true;
                    }, 50);
                },
                closeRepostMenu: () => {
                    state.isRepostMenuOpen = false;
                },
                toggleRepost: () => {
                    state.isRepostMenuOpen = false;

                    ZESTEXAPI().userTimeline().with({
                        post_id: postData.value.id
                    }).sendTo('post/repost/toggle').then((response) => {
                        postData.value.meta.activity.reposted = response.data.data.reposted;
                        postData.value.quotes_count = response.data.data.quotes_count;
                    }).catch((error) => {
                        if (error.response) {
                            toastError(error.response.data.message);
                        }
                    });
                },
                quotePostFromRepostMenu: () => {
                    state.isRepostMenuOpen = false;
                    ZESTEXEventBus.emit('post-editor:open', {
                        quotePostId: postData.value.id
                    });
                },
                isOnPostPage: computed(() => {
                    return route.name === 'publication_index';
                })
            }
        },
        components: {
            AvatarSmall: AvatarSmall,
            DropdownButton: DropdownButton,
            DropdownMenu: DropdownMenu,
            DropdownMenuItem: DropdownMenuItem,
            DropdownReactions: DropdownReactions,
            PrimaryIconButton: PrimaryIconButton,
            TextTranslateButton: TextTranslateButton,
            PublicationQuote: PublicationQuote,
            UserHoverCard: UserHoverCard,
            ReactionsViewer: defineAsyncComponent(() => {
                return import('@/kernel/vue/components/reactions/ReactionsViewer.vue');
            }),
            ViewsCounter: ViewsCounter,
            TranslationService: TranslationService,
            ReactionsPicker: defineAsyncComponent(() => {
                return import('@D/components/reactions/ReactionsPicker.vue');
            }),
            PublicationImage: defineAsyncComponent(() => {
                return import('@D/components/timeline/feed/parts/media/PublicationImage.vue');
            }),
            PublicationGif: defineAsyncComponent(() => {
                return import('@D/components/timeline/feed/parts/media/PublicationGif.vue');
            }),
            PublicationVideo: defineAsyncComponent(() => {
                return import('@D/components/timeline/feed/parts/media/PublicationVideo.vue');
            }),
            PublicationDocument: defineAsyncComponent(() => {
                return import('@D/components/timeline/feed/parts/media/PublicationDocument.vue');
            }),
            PublicationAudio: defineAsyncComponent(() => {
                return import('@D/components/timeline/feed/parts/media/PublicationAudio.vue');
            }),
            PublicationPoll: defineAsyncComponent(() => {
                return import('@D/components/timeline/feed/parts/poll/PublicationPoll.vue');
            }),
            PublicationText: defineAsyncComponent(() => {
                return import('@D/components/timeline/feed/parts/text/PublicationText.vue');
            }),
            AvatarExtraSmall: defineAsyncComponent(() => {
                return import('@D/components/general/avatars/AvatarExtraSmall.vue');
            }),
			PublicationShare: defineAsyncComponent(() => {
				return import('@D/components/timeline/feed/parts/share/PublicationShare.vue');
			}),
            PublicationQuotePlaceholder: defineAsyncComponent(() => {
                return import('@D/components/timeline/feed/parts/quote/PublicationQuotePlaceholder.vue');
            }),
            LinkSnapshot: defineAsyncComponent(() => {
                return import('@D/components/media/links/LinkSnapshot.vue');
            })
        }
    });
</script>


