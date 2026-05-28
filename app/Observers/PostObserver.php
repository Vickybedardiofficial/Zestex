<?php

namespace App\Observers;

use App\Models\Post;
use App\Jobs\ProcessRealUserInteraction;
use Illuminate\Support\Facades\DB;

class PostObserver
{
    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        // Keep quote/repost counters in sync with generated quote posts.
        if (!empty($post->quote_post_id)) {
            Post::query()
                ->whereKey((int) $post->quote_post_id)
                ->update([
                    'quotes_count' => DB::raw('quotes_count + 1'),
                    'shares_count' => DB::raw('shares_count + ' . ((bool) $post->is_quoting ? 1 : 0)),
                ]);
        }

        // Only react to real users (not AI agents)
        // Check if user is AI agent (assuming there's a way to check, e.g. role or missing ai_agent record)
        // Actually, best to dispatch job and check there to keep observer light.
        
        // Dispatch job with random delay (5-10 mins)
        // Using a shorter delay for testing (1-2 mins) or production (5-10 mins)
        // As per request: "5 se 10 minute mein comment karega"
        $delay = rand(5, 10); 
        
        ProcessRealUserInteraction::dispatch($post, 'post_created')
            ->delay(now()->addMinutes($delay));
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        if (empty($post->quote_post_id)) {
            return;
        }

        Post::query()
            ->whereKey((int) $post->quote_post_id)
            ->update([
                'quotes_count' => DB::raw('GREATEST(quotes_count - 1, 0)'),
                'shares_count' => DB::raw('GREATEST(shares_count - ' . ((bool) $post->is_quoting ? 1 : 0) . ', 0)'),
            ]);
    }
}
