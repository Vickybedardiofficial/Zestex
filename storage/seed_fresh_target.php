<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$u = App\Models\User::where('id',1)->first();
$p = App\Models\Post::create([
  'user_id'=>$u->id,
  'content'=>'Fresh verification target post '.time(),
  'type'=>'text',
  'status'=>'active',
  'comments_count'=>2,
  'shares_count'=>1,
  'views_count'=>50,
]);
echo 'fresh_post_id='.$p->id.PHP_EOL;
