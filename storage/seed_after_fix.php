<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$p = App\Models\Post::create([
  'user_id'=>1,
  'content'=>'Fresh target after comment query fix '.time(),
  'type'=>'text',
  'status'=>'active',
  'comments_count'=>3,
  'shares_count'=>0,
  'views_count'=>35,
]);
echo 'fresh_post_id='.$p->id.PHP_EOL;
