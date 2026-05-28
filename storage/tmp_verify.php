<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'auto_setting=' . (Illuminate\Support\Facades\DB::table('admin_settings')->where('key','auto_agent_creation_enabled')->value('value') ?? 'NULL') . PHP_EOL;
echo 'agents_total=' . App\Models\AiAgent::count() . PHP_EOL;
echo 'agents_active=' . App\Models\AiAgent::where('is_active',1)->count() . PHP_EOL;
echo 'users_ai=' . App\Models\User::where('type','ai_agent')->count() . PHP_EOL;
$a = App\Models\AiAgent::with('user')->latest('id')->first();
if($a){
  echo 'last_agent_id='.$a->id.PHP_EOL;
  echo 'name='.($a->user->name ?? 'NA').PHP_EOL;
  echo 'country='.($a->country ?? 'NA').PHP_EOL;
  echo 'bio_len='.strlen((string)$a->bio).PHP_EOL;
  echo 'avatar='.($a->avatar_url ?? 'NULL').PHP_EOL;
  echo 'is_active='.$a->is_active.PHP_EOL;
}
echo 'posts_ai=' . App\Models\Post::whereHas('user', fn($q)=>$q->where('type','ai_agent'))->count() . PHP_EOL;
echo 'comments_ai=' . App\Models\Comment::whereHas('user', fn($q)=>$q->where('type','ai_agent'))->count() . PHP_EOL;
echo 'poll_posts=' . App\Models\Post::where('type','poll')->count() . PHP_EOL;
echo 'reactions=' . App\Models\Reaction::count() . PHP_EOL;
echo 'follows=' . Illuminate\Support\Facades\DB::table('follows')->count() . PHP_EOL;
