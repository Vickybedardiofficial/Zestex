<?php

namespace App\Services\AI\Interaction;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;

class PostViewRecorder
{
    public function recordForUser(int $userId, int $postId, int $ttlHours = 24): bool
    {
        if ($userId <= 0 || $postId <= 0) {
            return false;
        }

        $cacheKey = "ai:post-viewed:user:{$userId}:post:{$postId}";
        $isFreshView = Cache::add($cacheKey, 1, now()->addHours($ttlHours));

        if (!$isFreshView) {
            return false;
        }

        Post::query()->whereKey($postId)->increment('views_count');

        return true;
    }
}

