<?php

namespace App\Http\Controllers\Api\User\Timeline;

use App\Enums\Post\PostStatus;
use App\Enums\Post\PostType;
use App\Events\User\Timeline\PostCreatedEvent;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\Timeline\TimelineResource;
use App\Models\Post;
use App\Traits\Http\Api\SupportsApiResponses;
use Illuminate\Http\Request;

class LiveStreamController extends Controller
{
    use SupportsApiResponses;

    public function start(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'min:4', 'max:120'],
            'description' => ['nullable', 'string', 'max:1200'],
            'hashtags' => ['nullable', 'string', 'max:120'],
        ]);

        $title = trim($validated['title']);
        $description = trim((string) ($validated['description'] ?? ''));
        $hashtags = trim((string) ($validated['hashtags'] ?? '#Live #LiveNow #Streaming'));

        $content = "🔴 LIVE NOW: {$title}\n\n";

        if ($description !== '') {
            $content .= "{$description}\n\n";
        }

        $content .= "Join now and drop your questions in comments 👇\n{$hashtags}";

        $post = Post::create([
            'user_id' => me()->id,
            'type' => PostType::TEXT,
            'status' => PostStatus::ACTIVE->value,
            'content' => $content,
            'text_language' => (new Post(['content' => $content]))->getContentLanguage(),
            'is_ai_generated' => false,
            'is_sensitive' => false,
            'is_quoting' => false,
            'quote_post_id' => null,
        ]);

        me()->increment('publications_count', 1);

        $post->load(['user', 'reactions', 'comments.user', 'quotedPost', 'linkSnapshot']);

        event(new PostCreatedEvent($post));

        return $this->responseSuccess([
            'data' => TimelineResource::make($post),
            'message' => 'Live stream started successfully.',
        ]);
    }
}
