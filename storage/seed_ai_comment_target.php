<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$agent = App\Models\AiAgent::find(1);
$otherAi = App\Models\AiAgent::where('id','!=',1)->where('is_active',1)->first();
if($agent && $otherAi){
  $p = App\Models\Post::create([
    'user_id'=>$otherAi->user_id,
    'content'=>'AI memory comment target '.time(),
    'type'=>'text','status'=>'active','comments_count'=>2,'shares_count'=>1,'views_count'=>22
  ]);
  echo 'seed_ai_post_id='.$p->id.PHP_EOL;
}
