<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$u = App\Models\User::where('type','!=','ai_agent')->first();
if(!$u){ echo "no_human_user\n"; exit(0);} 
$p = App\Models\Post::create([
  'user_id'=>$u->id,
  'content'=>'Verification seed post for AI comment trigger '.now(),
  'type'=>'text',
  'status'=>'active',
  'comments_count'=>1,
  'shares_count'=>0,
  'views_count'=>10,
]);
echo 'seed_user_id='.$u->id.PHP_EOL;
echo 'seed_post_id='.$p->id.PHP_EOL;
