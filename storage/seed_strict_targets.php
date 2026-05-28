<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$aiUser = App\Models\User::where('type','ai_agent')->where('id','!=',8)->first();
$human = App\Models\User::where('type','!=','ai_agent')->first();
$aiPost = App\Models\Post::create([
  'user_id'=>$aiUser->id,
  'content'=>'AI target post for strict-comment test '.time(),
  'type'=>'text','status'=>'active','comments_count'=>1,'shares_count'=>0,'views_count'=>20
]);
$humanPost = App\Models\Post::create([
  'user_id'=>$human->id,
  'content'=>'Human target post that AI must ignore '.time(),
  'type'=>'text','status'=>'active','comments_count'=>2,'shares_count'=>1,'views_count'=>40
]);
echo 'ai_post_id='.$aiPost->id.PHP_EOL;
echo 'human_post_id='.$humanPost->id.PHP_EOL;
