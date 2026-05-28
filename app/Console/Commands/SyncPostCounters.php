<?php

namespace App\Console\Commands;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Console\Command;

class SyncPostCounters extends Command
{
    protected $signature = 'ai-agents:sync-post-counters {--chunk=200 : Chunk size}';
    protected $description = 'Recalculate posts.comments_count, posts.quotes_count and posts.shares_count';

    public function handle(): int
    {
        $chunk = max(50, (int) ($this->option('chunk') ?: 200));
        $this->info("Syncing post counters with chunk={$chunk}...");

        $updated = 0;

        Post::query()
            ->select('id')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use (&$updated) {
                foreach ($rows as $row) {
                    $postId = (int) $row->id;

                    $comments = Comment::query()
                        ->where('post_id', $postId)
                        ->count();

                    $quotes = Post::query()
                        ->where('quote_post_id', $postId)
                        ->count();

                    $shares = Post::query()
                        ->where('quote_post_id', $postId)
                        ->where('is_quoting', true)
                        ->count();

                    Post::query()
                        ->whereKey($postId)
                        ->update([
                            'comments_count' => $comments,
                            'quotes_count' => $quotes,
                            'shares_count' => $shares,
                        ]);

                    $updated++;
                }
            });

        $this->info("Done. Updated {$updated} post counters.");
        return 0;
    }
}

