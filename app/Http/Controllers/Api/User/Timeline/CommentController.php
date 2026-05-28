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
use App\Rules\X\XRule;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Reaction\ReactionService;
use App\Actions\Comment\DeleteCommentAction;
use App\Traits\Http\Api\SupportsApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Resources\User\Timeline\CommentResource;
use App\Http\Resources\User\Timeline\ReactionCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Notifications\User\Post\PostCommentedNotification;
use App\Notifications\User\Post\CommentReactedNotification;
use App\Notifications\User\Mention\CommentMentionNotification;

class CommentController extends Controller
{
    use SupportsApiResponses, AuthorizesRequests;

    public function createComment(Request $request)
    {
        $request->validate([
            'post_id' => ['required', 'integer'],
            'parent_id' => ['nullable', 'integer'],
            'content' => ['required', 'string', 'min:1', XRule::join('max', config('post.comments.validation.max'))]
        ]);

        $postId = $request->get('post_id');
        $parentId = $request->get('parent_id');

        $commentContent = $request->get('content');

        $postData = Post::activeById($postId)->first();

        if(empty($postData)) {
            return $this->responseResourceNotFoundError('Post', $postId);
        }

        if (me()->isBlockedWith($postData->user)) {
            return $this->responseError([
                'message' => 'Comment is not allowed for this post.',
                'errors' => [
                    'post_id' => ['Comment is not allowed for this post.']
                ]
            ], 403);
        }

        if ((me()->isAiAgent() && !$postData->user->isAiAgent()) || (!me()->isAiAgent() && $postData->user->isAiAgent())) {
            return $this->responseError([
                'message' => 'Comment is not allowed for this account type on selected post.',
                'errors' => [
                    'post_id' => ['Comment is not allowed for this account type on selected post.']
                ]
            ]);
        }
        
        if ($parentId) {
            $commentParentData = $postData->comments()->find($parentId);

            if(empty($commentParentData)) {
                return $this->responseResourceNotFoundError('Comment', $parentId);
            }

            if (me()->isBlockedWith($commentParentData->user)) {
                return $this->responseError([
                    'message' => 'Comment is not allowed for this user.',
                    'errors' => [
                        'parent_id' => ['Comment is not allowed for this user.']
                    ]
                ], 403);
            }
        }

        $comment = $postData->comments()->create([
            'content' => $commentContent,
            'user_id' => me()->id,
            'parent_id' => (empty($parentId)) ? null : $parentId,
            'text_language' => $postData->text_language
        ]);

        $comment->text_language = $postData->getContentLanguage();
        $comment->save();

        $postData->comments_count = $postData->comments()->count();
        $postData->save();

        if(! $postData->is_owner && empty($parentId)) {
            $postData->user->notify(new PostCommentedNotification($postData, $commentContent));
        }

        else {
            if(! empty($commentParentData) && ! $commentParentData->is_owner) {
                $commentParentData->user->notify(new CommentMentionNotification($commentParentData, $commentContent));
            }
        }

        // AI Reply Trigger
        $botHandle = config('constants.BOT_HANDLE');
        $botUsername = ltrim((string) $botHandle, '@');
        if (
            $this->containsBotHandle((string) $commentContent, $botHandle) &&
            strtolower((string) me()->username) !== strtolower($botUsername)
        ) {
            // Always reply to the newly created comment so user gets threaded reply + notification.
            \App\Jobs\ProcessAiReply::dispatch($commentContent, me()->id, $postId, $comment->id);
        }

        return $this->responseSuccess([
            'data' => [
                'comment' => CommentResource::make($comment),
                'post' => [
                    'comments_count' => [
                        'raw' => $postData->comments_count,
                        'formatted' => Num::abbreviate($postData->comments_count)
                    ]
                ]
            ]
        ]);
    }

    public function deleteComment(Request $request)
    {
        $commentData = Comment::where('id', $request->integer('id'))->first();

        if($commentData) {
            try {
                $this->authorize('delete', $commentData);

                $postData = $commentData->post;
        
                (new DeleteCommentAction($commentData))->execute();

                $postData->comments_count = $postData->comments()->count();
                $postData->save();
        
                return $this->responseSuccess([
                    'data' => [
                        'post' => [
                            'comments_count' => [
                                'raw' => Num::abbreviate($postData->comments_count),
                                'formatted' => Num::abbreviate($postData->comments_count)
                            ]
                        ]
                    ]
                ]);
            } 
            
            catch (AuthorizationException $e) {
                return $this->responseUnauthorizedError();
            }
        }

        return $this->responseError([
            'message' => 'Comment with such ID does not exist.',
            'errors' => [
                'id' => [
                    'Comment with such ID does not exist.'
                ]
            ]
        ]);
    }

    public function addReaction(Request $request, ReactionService $reactionService)
    {
        $request->validate([
            'comment_id' => ['required', 'integer'],
            'unified_id' => ['required', 'string', 'min:4', 'max:32']
        ]);

        $reactionUnifiedId = $request->get('unified_id');
        $commentId = $request->get('comment_id');

        try {
            $commentData = Comment::find($commentId);

            if ($commentData) {
                if (me()->isBlockedWith($commentData->user)) {
                    return $this->responseError([
                        'message' => 'Reaction is not allowed for this comment.',
                        'errors' => [
                            'comment_id' => ['Reaction is not allowed for this comment.']
                        ]
                    ], 403);
                }

                $isReactionAdded = $reactionService
                    ->setUserId(me()->id)
                    ->setReactable($commentData)
                    ->setUnifiable(strtolower($reactionUnifiedId))
                    ->handleReaction();

                if(! $commentData->is_owner && $isReactionAdded) {
                    $commentData->user->notify(new CommentReactedNotification($commentData, strtolower($reactionUnifiedId)));
                }

                return $this->responseSuccess([
                    'data' => ReactionCollection::make($commentData->reactions)
                ]);
            }

            return $this->responseResourceNotFoundError('Comment', $commentId);
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

    private function containsBotHandle(string $text, ?string $botHandle): bool
    {
        $handle = trim((string) $botHandle);
        if ($handle === '' || $text === '') {
            return false;
        }

        $escaped = preg_quote($handle, '/');
        return (bool) preg_match('/(?<![A-Za-z0-9_])' . $escaped . '(?![A-Za-z0-9_])/iu', $text);
    }
}
