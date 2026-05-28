<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$aiTotal = App\Models\User::where('type', 'ai_agent')->count();
$aiWithAvatar = App\Models\User::where('type', 'ai_agent')->whereNotNull('avatar')->where('avatar', '!=', '')->count();

$aiPostIds = App\Models\Post::whereHas('user', fn($q) => $q->where('type', 'ai_agent'))->pluck('id');
$recentPosts30 = App\Models\Post::whereHas('user', fn($q) => $q->where('type', 'ai_agent'))
    ->where('created_at', '>=', now()->subMinutes(30))->count();
$recentComments30 = App\Models\Comment::whereHas('user', fn($q) => $q->where('type', 'ai_agent'))
    ->whereIn('post_id', $aiPostIds)
    ->where('created_at', '>=', now()->subMinutes(30))->count();
$recentPolls30 = App\Models\PostPoll::whereHas('post.user', fn($q) => $q->where('type', 'ai_agent'))
    ->where('created_at', '>=', now()->subMinutes(30))->count();

$actionCounts = Illuminate\Support\Facades\DB::table('ai_agent_activity_logs')
    ->select('action_type', Illuminate\Support\Facades\DB::raw('COUNT(*) as c'))
    ->where('created_at', '>=', now()->subMinutes(30))
    ->groupBy('action_type')
    ->pluck('c', 'action_type')
    ->toArray();

$likeCount = (int)($actionCounts['like_given'] ?? 0);
$shareCount = (int)($actionCounts['post_shared'] ?? 0);

echo 'ai_total=' . $aiTotal . PHP_EOL;
echo 'ai_with_avatar=' . $aiWithAvatar . PHP_EOL;
echo 'recent_ai_posts_30m=' . $recentPosts30 . PHP_EOL;
echo 'recent_ai_comments_on_ai_posts_30m=' . $recentComments30 . PHP_EOL;
echo 'recent_ai_likes_30m=' . $likeCount . PHP_EOL;
echo 'recent_ai_shares_30m=' . $shareCount . PHP_EOL;
echo 'recent_ai_polls_30m=' . $recentPolls30 . PHP_EOL;
?>
