<?php

namespace App\Events\User\Timeline;

use App\Models\Comment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Comment $comment,
        public int $receiverUserId
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("App.Models.User.{$this->receiverUserId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'timeline.comment.created';
    }

    public function broadcastWith(): array
    {
        $createdAt = $this->comment->created_at;
        $createdAtValue = is_object($createdAt) && method_exists($createdAt, 'getIso')
            ? (string) $createdAt->getIso()
            : (string) $this->comment->getRawOriginal('created_at');
        $this->comment->loadMissing('user', 'post');

        return [
            'id' => (int) $this->comment->id,
            'post_id' => (int) $this->comment->post_id,
            'post_hash_id' => (string) ($this->comment->post?->hashId ?? ''),
            'user_id' => (int) $this->comment->user_id,
            'content' => (string) $this->comment->content,
            'created_at' => $createdAtValue,
            'user' => [
                'id' => (int) ($this->comment->user?->id ?? 0),
                'name' => (string) ($this->comment->user?->name ?? ''),
                'username' => (string) ($this->comment->user?->username ?? ''),
                'avatar_url' => (string) ($this->comment->user?->avatar_url ?? ''),
            ],
        ];
    }
}
