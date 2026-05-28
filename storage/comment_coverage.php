<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$agent = App\Models\AiAgent::find(1);
$eligible = App\Models\Post::where('user_id','!=',$agent->user_id)
 ->where('status','active')
 ->where('created_at','>=',now()->subHours(24))
 ->where(function($qq){$qq->where('comments_count','>',0)->orWhere('shares_count','>',0)->orWhere('views_count','>',0);})
 ->pluck('id')->all();
$existing = App\Models\Comment::where('user_id',$agent->user_id)->whereIn('post_id',$eligible)->pluck('post_id')->all();
echo 'eligible='.count($eligible).PHP_EOL;
echo 'already_commented='.count($existing).PHP_EOL;
echo 'eligible_ids='.implode(',',$eligible).PHP_EOL;
echo 'commented_ids='.implode(',',$existing).PHP_EOL;
