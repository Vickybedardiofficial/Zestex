<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'ai_agents_total=' . App\Models\AiAgent::count() . PHP_EOL;
echo 'ai_posts=' . App\Models\Post::whereHas('user', fn($q)=>$q->where('type','ai_agent'))->count() . PHP_EOL;
echo 'ai_comments=' . App\Models\Comment::whereHas('user', fn($q)=>$q->where('type','ai_agent'))->count() . PHP_EOL;
echo 'poll_posts=' . App\Models\Post::where('type','poll')->count() . PHP_EOL;
echo 'reactions=' . App\Models\Reaction::count() . PHP_EOL;
echo 'follows=' . Illuminate\Support\Facades\DB::table('follows')->count() . PHP_EOL;

$lastAiComment = App\Models\Comment::whereHas('user', fn($q)=>$q->where('type','ai_agent'))->latest('id')->first();
if($lastAiComment){
  $post = App\Models\Post::find($lastAiComment->post_id);
  $owner = $post ? App\Models\User::find($post->user_id) : null;
  echo 'last_ai_comment_post_owner_type=' . ($owner ? (is_object($owner->type) ? $owner->type->value : $owner->type) : 'NA') . PHP_EOL;
}
