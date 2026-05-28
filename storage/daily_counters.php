<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$agents = App\Models\AiAgent::with('user')->orderBy('id')->get();
foreach($agents as $a){
  $nm=trim(($a->user->first_name ?? '').' '.($a->user->last_name ?? ''));
  echo "agent={$a->id} {$nm} | posts={$a->daily_posts_count}/{$a->daily_posts_limit} | comments={$a->daily_comments_count}/{$a->daily_comments_limit} | likes={$a->daily_likes_count}/{$a->daily_likes_limit} | shares={$a->daily_shares_count}/{$a->daily_shares_limit} | stage=".($a->warm_up_stage??'NA').PHP_EOL;
}
