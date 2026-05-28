<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$u = App\Models\User::find(114);
if($u){
 echo 'agent107_bio_len='.strlen((string)$u->bio).PHP_EOL;
 echo 'agent107_avatar='.(empty($u->avatar)?'NULL':'SET').PHP_EOL;
}

$aiUsers = App\Models\User::where('type','ai_agent')->get();
echo 'ai_users_total='.$aiUsers->count().PHP_EOL;
echo 'ai_users_with_bio='.$aiUsers->filter(fn($x)=>!empty(trim((string)$x->bio)))->count().PHP_EOL;
echo 'ai_users_with_avatar='.$aiUsers->filter(fn($x)=>!empty((string)$x->avatar))->count().PHP_EOL;

echo 'recent_ai_posts_30m=' . App\Models\Post::whereHas('user', fn($q)=>$q->where('type','ai_agent'))->where('created_at','>=',now()->subMinutes(30))->count() . PHP_EOL;

echo 'recent_ai_comments_on_ai_posts_30m=' . App\Models\Comment::query()
 ->join('users as commenter','comments.user_id','=','commenter.id')
 ->join('posts','comments.post_id','=','posts.id')
 ->join('users as owner','posts.user_id','=','owner.id')
 ->where('commenter.type','ai_agent')
 ->where('owner.type','ai_agent')
 ->where('comments.created_at','>=',now()->subMinutes(30))
 ->count() . PHP_EOL;

echo 'recent_ai_likes_30m=' . App\Models\AiAgentActivityLog::where('action_type','post_liked')->where('created_at','>=',now()->subMinutes(30))->count() . PHP_EOL;
echo 'recent_ai_shares_30m=' . App\Models\AiAgentActivityLog::where('action_type','post_shared')->where('created_at','>=',now()->subMinutes(30))->count() . PHP_EOL;
