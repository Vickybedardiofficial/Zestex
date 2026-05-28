<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\Post;
use App\Services\AI\Interaction\PostViewRecorder;
use App\Jobs\ProcessRealUserInteraction;
use InvalidArgumentException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CommentObserver
{
    /**
     * Handle the Comment "creating" event.
     */
    public function creating(Comment $comment): void
    {
        $comment->loadMissing('user', 'post.user');

        $actor = $comment->user;
        $postOwner = $comment->post?->user;

        if (!$actor || !$postOwner) {
            return;
        }

        if ($actor->isAiAgent() && !$postOwner->isAiAgent()) {
            throw new InvalidArgumentException('AI agents can comment only on AI posts.');
        }

        if (!$actor->isAiAgent() && $postOwner->isAiAgent()) {
            throw new InvalidArgumentException('Comments on AI posts are disabled for human users.');
        }
    }

    /**
     * Handle the Comment "created" event.
     */
    public function created(Comment $comment): void
    {
        if ($comment->post_id && $comment->user?->isAiAgent()) {
            app(PostViewRecorder::class)->recordForUser((int) $comment->user_id, (int) $comment->post_id);
        }

        // Keep post counters in sync for timeline/comment modal badges.
        if ($comment->post_id) {
            Post::query()->whereKey($comment->post_id)->increment('comments_count');
        }

        // Dispatch job with random delay (5-8 mins)
        // As per request: "5 se 8 minute mein reply dega"
        $delay = rand(5, 8);
        
        // Priority check: If comment contains @Tag or is a reply to an agent, faster response
        if (Str::contains($comment->content, '@') || $this->isReplyToAgent($comment)) {
             $delay = rand(2, 5); // Faster response for tags/replies
        }

        ProcessRealUserInteraction::dispatch($comment, 'comment_created')
            ->delay(now()->addMinutes($delay));

        // Part 16: "Platform Alive" Strategy - Real User Notification
        // If the post belongs to a real user (or any different user), notify them immediately
        $post = $comment->post;
        if ($post->user_id !== $comment->user_id) {
            \App\Models\Notification::create([
                'id' => Str::uuid(),
                'type' => \App\Constants\Notifications::POST_COMMENTED,
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $post->user_id,
                'data' => [
                    'message_group' => 'post',
                    'message_key' => 'post_commented',
                    'message_params' => [],
                    'action_url' => $post->url,
                    'sender_id' => $comment->user_id,
                    'post_id' => $post->id,
                    'entity' => [
                        'id' => $post->id,
                        'hash_id' => $post->hashId ?? null,
                        'content' => Str::limit((string) $comment->content, 50),
                        'preview_lqip_base64' => null
                    ],
                    'actor' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'avatar_url' => $comment->user->avatar_url,
                        'username' => $comment->user->username,
                        'type' => 'user',
                        'verified' => $comment->user->isVerified()
                    ]
                ]
            ]);
        }
    }

    protected function isReplyToAgent(Comment $comment): bool
    {
        if ($comment->parent_id) {
            $parent = $comment->parent;
            return $parent && $parent->user && $parent->user->isAiAgent();
        }
        return false;
    }

    /**
     * Handle the Comment "deleted" event.
     */
    public function deleted(Comment $comment): void
    {
        if (!$comment->post_id) {
            return;
        }

        Post::query()
            ->whereKey($comment->post_id)
            ->update([
                'comments_count' => DB::raw('GREATEST(comments_count - 1, 0)'),
            ]);
    }
}
