<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$agent = App\Models\AiAgent::with('user')->find(1);
$q = App\Models\Post::where('user_id','!=',$agent->user_id)
 ->where('status','active')
 ->where('created_at','>=',now()->subHours(24))
 ->where(function($qq){$qq->where('comments_count','>',0)->orWhere('shares_count','>',0)->orWhere('views_count','>',0);});
$cnt = $q->count();
echo 'eligible_posts='.$cnt.PHP_EOL;
foreach($q->latest('id')->limit(5)->get() as $p){ echo "post_id={$p->id} user_id={$p->user_id} c={$p->comments_count} s={$p->shares_count} v={$p->views_count} created={$p->created_at}\n"; }
