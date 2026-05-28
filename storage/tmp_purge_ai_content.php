<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$aiUserIds = App\Models\AiAgent::pluck('user_id')->filter()->values();
if ($aiUserIds->isEmpty()) {
    echo "ai_users=0\n";
    exit(0);
}

$aiPostIds = App\Models\Post::whereIn('user_id', $aiUserIds)->pluck('id')->values();
$aiCommentIds = App\Models\Comment::whereIn('user_id', $aiUserIds)->pluck('id')->values();

echo 'ai_users=' . $aiUserIds->count() . PHP_EOL;
echo 'ai_posts_before=' . $aiPostIds->count() . PHP_EOL;
echo 'ai_comments_before=' . $aiCommentIds->count() . PHP_EOL;
echo 'ai_polls_before=' . App\Models\PostPoll::whereIn('post_id', $aiPostIds)->count() . PHP_EOL;

DB::transaction(function () use ($aiUserIds, $aiPostIds, $aiCommentIds) {
    if ($aiPostIds->isNotEmpty()) {
        DB::table('posts')
            ->whereIn('quote_post_id', $aiPostIds)
            ->update([
                'quote_post_id' => null,
                'is_quoting' => false,
                'updated_at' => now(),
            ]);

        DB::table('post_polls')->whereIn('post_id', $aiPostIds)->delete();

        DB::table('reactions')
            ->where('reactable_type', App\Models\Post::class)
            ->whereIn('reactable_id', $aiPostIds)
            ->delete();
    }

    if ($aiCommentIds->isNotEmpty()) {
        DB::table('reactions')
            ->where('reactable_type', App\Models\Comment::class)
            ->whereIn('reactable_id', $aiCommentIds)
            ->delete();
    }

    DB::table('comments')->whereIn('user_id', $aiUserIds)->delete();
    if ($aiPostIds->isNotEmpty()) {
        DB::table('comments')->whereIn('post_id', $aiPostIds)->delete();
    }

    DB::table('posts')->whereIn('user_id', $aiUserIds)->delete();
});

echo 'ai_posts_after=' . App\Models\Post::whereIn('user_id', $aiUserIds)->count() . PHP_EOL;
echo 'ai_comments_after=' . App\Models\Comment::whereIn('user_id', $aiUserIds)->count() . PHP_EOL;
echo 'ai_polls_after=' . App\Models\PostPoll::whereIn('post_id', $aiPostIds)->count() . PHP_EOL;
