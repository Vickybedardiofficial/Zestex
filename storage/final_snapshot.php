<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'auto_setting=' . (Illuminate\Support\Facades\DB::table('admin_settings')->where('key','auto_agent_creation_enabled')->value('value') ?? 'NULL') . PHP_EOL;
echo 'ai_agents_total=' . App\Models\AiAgent::count() . PHP_EOL;
echo 'ai_agents_active=' . App\Models\AiAgent::where('is_active',1)->count() . PHP_EOL;
echo 'ai_posts=' . App\Models\Post::whereHas('user', fn($q)=>$q->where('type','ai_agent'))->count() . PHP_EOL;
echo 'ai_comments=' . App\Models\Comment::whereHas('user', fn($q)=>$q->where('type','ai_agent'))->count() . PHP_EOL;
echo 'poll_posts=' . App\Models\Post::where('type','poll')->count() . PHP_EOL;
echo 'reactions=' . App\Models\Reaction::count() . PHP_EOL;
echo 'follows=' . Illuminate\Support\Facades\DB::table('follows')->count() . PHP_EOL;

$lastComment = App\Models\Comment::whereHas('user', fn($q)=>$q->where('type','ai_agent'))->latest('id')->first();
if($lastComment){
  echo 'last_ai_comment_id='.$lastComment->id.PHP_EOL;
  echo 'last_ai_comment_post_id='.$lastComment->post_id.PHP_EOL;
  echo 'last_ai_comment_user_id='.$lastComment->user_id.PHP_EOL;
}

$agents = App\Models\AiAgent::with('user')->orderBy('id')->get();
foreach($agents as $a){
  $nm = trim(($a->user->first_name ?? '').' '.($a->user->last_name ?? ''));
  echo "agent={$a->id} | {$nm} | active={$a->is_active} | warmup=".($a->warm_up_stage ?? 'NA')." | avatar=".(empty($a->avatar_url)?'NULL':'SET')." | bio_len=".strlen((string)$a->bio).PHP_EOL;
}
