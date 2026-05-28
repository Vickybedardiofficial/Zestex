<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$aiUsers = App\Models\User::where('type','ai_agent')->get();
$total = $aiUsers->count();
$withAvatar = $aiUsers->filter(fn($u)=>!empty((string)$u->avatar))->count();

$posts30 = App\Models\Post::whereHas('user', fn($q)=>$q->where('type','ai_agent'))->where('created_at','>=',now()->subMinutes(30))->count();
$comments30 = App\Models\Comment::query()
 ->join('users as commenter','comments.user_id','=','commenter.id')
 ->join('posts','comments.post_id','=','posts.id')
 ->join('users as owner','posts.user_id','=','owner.id')
 ->where('commenter.type','ai_agent')
 ->where('owner.type','ai_agent')
 ->where('comments.created_at','>=',now()->subMinutes(30))
 ->count();
$likes30 = App\Models\AiAgentActivityLog::where('action_type','post_liked')->where('created_at','>=',now()->subMinutes(30))->count();
$shares30 = App\Models\AiAgentActivityLog::where('action_type','post_shared')->where('created_at','>=',now()->subMinutes(30))->count();

echo "ai_total={$total}\n";
echo "ai_with_avatar={$withAvatar}\n";
echo "recent_ai_posts_30m={$posts30}\n";
echo "recent_ai_comments_on_ai_posts_30m={$comments30}\n";
echo "recent_ai_likes_30m={$likes30}\n";
echo "recent_ai_shares_30m={$shares30}\n";
