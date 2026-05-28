<?php
/*
|--------------------------------------------------------------------------
| Zestex - Social Network Platform.
|--------------------------------------------------------------------------
| Based on: Zestex - The Social Network Web Application.
|--------------------------------------------------------------------------
| Author: Vicky Bedardi Yadav
|--------------------------------------------------------------------------
| Branded by: Vicky Bedardi Yadav
| E-mail: vicktbedardi9@gmail.com
|--------------------------------------------------------------------------
| Copyright (c) Flip Basket Pvt Ltd. All rights reserved. 
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers\Api\User\Timeline;

use Exception;
use App\Models\Post;
use App\Support\Num;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\Post\PostStatus;
use App\Enums\Post\PostType;
use App\Http\Controllers\Controller;
use App\Actions\Post\DeletePostAction;
use App\Services\Text\LinkPreviewService;
use App\Services\Reaction\ReactionService;
use App\Traits\Http\Api\SupportsApiResponses;
use App\Events\User\Timeline\PostCreatedEvent;
use App\Http\Resources\User\Timeline\QuoteResource;
use App\Http\Resources\User\Timeline\TimelineResource;
use App\Http\Resources\User\Morph\LinkSnapshotResource;
use App\Http\Resources\User\Timeline\ReactionCollection;
use App\Notifications\User\Post\PostReactedNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Resources\User\Timeline\Editor\DraftPostResource;
use App\Traits\Http\Controllers\Api\User\Timeline\ValidatesPollData;
use App\Traits\Http\Controllers\Api\User\Timeline\ValidatesPostData;
use App\Traits\Http\Controllers\Api\User\Timeline\InteractsWithDraftPost;

class PostController extends Controller
{
    use SupportsApiResponses,
        AuthorizesRequests,
        InteractsWithDraftPost,
        ValidatesPollData,
        ValidatesPostData;

    public function createPost(Request $request)
    {
        $this->initializePostAndValidateData($request);

        $this->defineAndSetPostStatus();

        if($this->draftPost->content) {
            $this->draftPost->text_language = $this->draftPost->getContentLanguage();
        }

        $quotedPostId = $request->integer('quoted_post_id', null);

        if($quotedPostId) {
            $quotedPost = Post::activeById($quotedPostId)->first();

            if($quotedPost) {
                if (me()->isBlockedWith($quotedPost->user)) {
                    return $this->responseError([
                        'message' => 'Quote/Repost is not allowed for this post.',
                        'errors' => [
                            'quoted_post_id' => ['Quote/Repost is not allowed for this post.']
                        ]
                    ], 403);
                }

                if ((me()->isAiAgent() && ! $quotedPost->user->isAiAgent()) || (! me()->isAiAgent() && $quotedPost->user->isAiAgent())) {
                    return $this->responseError([
                        'message' => 'Quote/Repost is not allowed for this account type on selected post.',
                        'errors' => [
                            'quoted_post_id' => ['Quote/Repost is not allowed for this account type on selected post.']
                        ]
                    ]);
                }

                $this->draftPost->quote_post_id = $quotedPost->id;
                $this->draftPost->is_quoting = true;

                $quotedPost->increment('quotes_count', 1);
            }
        } else {
            $this->draftPost->quote_post_id = null;
            $this->draftPost->is_quoting = false;
        }

        $postMarks = $request->array('marks', []);

        if(! empty($postMarks['is_ai_generated'])) {
            $this->draftPost->is_ai_generated = true;
        }

        if(! empty($postMarks['is_sensitive'])) {
            $this->draftPost->is_sensitive = true;
        }

        $this->draftPost->save();

        $finalPost = $this->getFinialPost();

        $finalPost->user->increment('publications_count', 1);

        event(new PostCreatedEvent($finalPost));

        // AI Reply Trigger on post mention (e.g. @ze in post content)
        $botHandle = config('constants.BOT_HANDLE');
        $botUsername = ltrim((string) $botHandle, '@');
        $postContent = (string) ($finalPost->content ?? '');

        if (
            $this->containsBotHandle($postContent, $botHandle) &&
            strtolower((string) me()->username) !== strtolower($botUsername)
        ) {
            \App\Jobs\ProcessAiReply::dispatch($postContent, me()->id, $finalPost->id, null);
        }

        return $this->responseSuccess([
            'data' => TimelineResource::make($finalPost)
        ]);
    }

    public function bookmarkPost(Request $request)
    {
        $postId = $request->integer('id');

        $postData = Post::activeById($postId)->first();

        if($postData) {
            $bookmarkedStatus = $postData->isBookmarkedBy(me()->id);

            if($bookmarkedStatus) {
                $postData->removeBookmark(me()->id);
            }
            else {
                $postData->addBookmark(me()->id);
            }

            return $this->responseSuccess([
                'data' => [
                    'bookmarked' => (! $bookmarkedStatus)
                ]
            ]);
        }
        else {
            return $this->responseResourceNotFoundError('Post', $postId);
        }
    }

    public function toggleRepost(Request $request)
    {
        $request->validate([
            'post_id' => ['required', 'integer']
        ]);

        $postId = $request->integer('post_id');

        return DB::transaction(function () use ($postId) {
            $originalPost = Post::activeById($postId)->lockForUpdate()->first();

            if (! $originalPost) {
                return $this->responseResourceNotFoundError('Post', $postId);
            }

            if (me()->isBlockedWith($originalPost->user)) {
                return $this->responseError([
                    'message' => 'Repost is not allowed for this post.',
                    'errors' => [
                        'post_id' => ['Repost is not allowed for this post.']
                    ]
                ], 403);
            }

            if ((me()->isAiAgent() && ! $originalPost->user->isAiAgent()) || (! me()->isAiAgent() && $originalPost->user->isAiAgent())) {
                return $this->responseError([
                    'message' => 'Repost is not allowed for this account type on selected post.',
                    'errors' => [
                        'post_id' => ['Repost is not allowed for this account type on selected post.']
                    ]
                ]);
            }

            // Repost is implemented as a quote post with empty content created by the current user.
            $existingRepost = Post::active()
                ->where('user_id', me()->id)
                ->where('quote_post_id', $originalPost->id)
                ->where('is_quoting', true)
                ->where('content', '')
                ->lockForUpdate()
                ->first();

            if ($existingRepost) {
                (new DeletePostAction($existingRepost))->execute();

                me()->decrementValue('publications_count', 1);

                $originalPost->update([
                    'quotes_count' => max(0, ($originalPost->quotes_count - 1))
                ]);

                $reposted = false;
            }
            else {
                Post::create([
                    'user_id' => me()->id,
                    'type' => PostType::TEXT,
                    'status' => PostStatus::ACTIVE->value,
                    'content' => '',
                    'quote_post_id' => $originalPost->id,
                    'is_quoting' => true
                ]);

                me()->increment('publications_count', 1);
                $originalPost->increment('quotes_count', 1);

                $reposted = true;
            }

            $originalPost->refresh();

            return $this->responseSuccess([
                'data' => [
                    'post_id' => $originalPost->id,
                    'reposted' => $reposted,
                    'quotes_count' => [
                        'raw' => $originalPost->quotes_count,
                        'formatted' => Num::abbreviate($originalPost->quotes_count)
                    ]
                ]
            ]);
        });
    }

    public function getDraftPost(Request $request)
    {
        $this->fetchOrInitializeDraftPost();

        $quotedPostId = $request->integer('quoted_post_id', null);

        $responseData = [
            'data' => [
                'draft' => null
            ]
        ];

        if ($this->draftPost->exists) {
            $responseData['data']['draft'] = DraftPostResource::make($this->draftPost);
        }

        if($quotedPostId) {
            $quotedPost = Post::activeById($quotedPostId)->with('user')->first();

            if($quotedPost) {
                $responseData['data']['quoted_post'] = QuoteResource::make($quotedPost);
            }
        }

        return $this->responseSuccess($responseData);
    }

    private function defineAndSetPostStatus()
    {
        $this->draftPost->status = PostStatus::ACTIVE->value;

        if($this->draftPost->type->isVideo()) {
            $this->draftPost->status = PostStatus::PROCESSING_VIDEO->value;
        }
    }

    private function initializePostAndValidateData(Request $request)
    {
        $this->fetchOrInitializeDraftPost();

        $this->validatePostData([
            'content' => $request->get('content', null)
        ]);

        if($request->filled('content')) {
            $this->draftPost->content = normalize_nls($request->get('content', ''));
        }

        if($this->draftPost->type->isPoll()) {
            $this->validatePollData([
                'poll_options' => $request->get('poll_options', [])
            ]);

            if(! $this->draftPost->exists) {
                $this->draftPost->save();
            }

            $this->draftPost->poll->update([
                'choices' => $request->get('poll_options')
            ]);
        }
    }

    public function deletePost(Request $request)
    {
        $postId = $request->integer('id');

        $postData = Post::findOrFail($postId);

        $this->authorize('delete', $postData);

        (new DeletePostAction($postData))->execute();

        $postData->user->decrementValue('publications_count', 1);

        return $this->responseSuccess([
            'data' => null
        ]);
    }

    private function getFinialPost()
    {
        return $this->draftPost->refresh();
    }

    private function containsBotHandle(string $text, ?string $botHandle): bool
    {
        $handle = trim((string) $botHandle);
        if ($handle === '' || $text === '') {
            return false;
        }

        $escaped = preg_quote($handle, '/');
        return (bool) preg_match('/(?<![A-Za-z0-9_])' . $escaped . '(?![A-Za-z0-9_])/iu', $text);
    }

    public function addReaction(Request $request, ReactionService $reactionService)
    {
        $request->validate([
            'post_id' => ['required', 'integer'],
            'unified_id' => ['required', 'string', 'min:4', 'max:32']
        ]);

        $reactionUnifiedId = $request->get('unified_id');
        $postId = $request->get('post_id');

        try {
            $postData = Post::activeById($postId)->firstOrFail();

            if (me()->isBlockedWith($postData->user)) {
                return $this->responseError([
                    'message' => 'Reaction is not allowed for this post.',
                    'errors' => [
                        'post_id' => ['Reaction is not allowed for this post.']
                    ]
                ], 403);
            }

            $isReactionAdded = $reactionService
                ->setUserId(me()->id)
                ->setReactable($postData)
                ->setUnifiable(strtolower($reactionUnifiedId))
                ->handleReaction();

            if (! $postData->is_owner && $isReactionAdded) {
                $postData->user->notify(new PostReactedNotification($postData, strtolower($reactionUnifiedId)));
            }

            return $this->responseSuccess([
                'data' => ReactionCollection::make($postData->reactions)
            ]);
        }

        catch (Exception $e) {
            return $this->responseError([
                'message' => $e->getMessage(),
                'errors' => [
                    $e->getMessage()
                ]
            ]);
        }
    }

    public function previewLink(Request $request)
    {
        $request->validate([
            'url' => ['required', 'string', 'url']
        ]);

        $this->fetchOrInitializeDraftPost();

        $this->draftPost->linkSnapshot()->delete();

        $url = $request->get('url');

        $linkPreviewService = app(LinkPreviewService::class);

        $linkPreview = $linkPreviewService->previewLink($url);

        // Save the draft post first to ensure it has an ID
        $this->draftPost->content = $url;
        $this->draftPost->save();

        $linkSnapshotData = $this->draftPost->linkSnapshot()->create([
            'title' => Str::limit($linkPreview['title'], 250),
            'description' => Str::limit($linkPreview['description'], 250),
            'url' => Str::limit($linkPreview['url'], 250),
            'metadata' => [
                'is_fallback' => isset($linkPreview['is_fallback']) ? $linkPreview['is_fallback'] : false,
                'preview_image_base64' => $linkPreview['preview_image_base64']
            ]
        ]);

        return $this->responseSuccess([
            'data' => LinkSnapshotResource::make($linkSnapshotData)
        ]);
    }

    public function deleteLinkSnapshot()
    {
        $this->fetchOrInitializeDraftPost();

        $this->draftPost->linkSnapshot()->delete();

        $this->draftPost->content = '';
        $this->draftPost->save();

        return $this->responseSuccess([
            'data' => null
        ]);
    }
}
