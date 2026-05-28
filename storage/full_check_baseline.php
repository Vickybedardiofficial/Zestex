<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$aiUsers = App\Models\User::where('type','ai_agent')->get();
$totalAiUsers = $aiUsers->count();
$withBio = $aiUsers->filter(fn($u)=>!empty(trim((string)$u->bio)))->count();
$withAvatar = $aiUsers->filter(fn($u)=>!empty((string)$u->avatar))->count();

$recentAiPosts = App\Models\Post::whereHas('user', fn($q)=>$q->where('type','ai_agent'))
    ->where('created_at','>=', now()->subHour())
    ->count();

$recentAiCommentsOnAiPosts = App\Models\Comment::query()
    ->join('users as commenter','comments.user_id','=','commenter.id')
    ->join('posts','comments.post_id','=','posts.id')
    ->join('users as owner','posts.user_id','=','owner.id')
    ->where('commenter.type','ai_agent')
    ->where('owner.type','ai_agent')
    ->where('comments.created_at','>=',now()->subHour())
    ->count();

$recentAiShares = App\Models\AiAgentActivityLog::where('action_type','post_shared')
    ->where('created_at','>=',now()->subHour())
    ->count();

$recentAiLikes = App\Models\AiAgentActivityLog::where('action_type','post_liked')
    ->where('created_at','>=',now()->subHour())
    ->count();

echo 'ai_users_total='.$totalAiUsers.PHP_EOL;
echo 'ai_users_with_bio='.$withBio.PHP_EOL;
echo 'ai_users_with_avatar='.$withAvatar.PHP_EOL;
echo 'recent_ai_posts_1h='.$recentAiPosts.PHP_EOL;
echo 'recent_ai_comments_on_ai_posts_1h='.$recentAiCommentsOnAiPosts.PHP_EOL;
echo 'recent_ai_likes_1h='.$recentAiLikes.PHP_EOL;
echo 'recent_ai_shares_1h='.$recentAiShares.PHP_EOL;
