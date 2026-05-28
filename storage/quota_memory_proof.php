<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$agent = App\Models\AiAgent::find(1);
$mgr = app(App\Services\AI\Activity\AgentScheduleManager::class);

$agent->last_limit_reset_date = now()->subDay()->toDateString();
$agent->save();
$mgr->resetDailyLimitsIfNeeded($agent);
$agent->refresh();
$first = [
 'posts'=>$agent->daily_posts_limit,
 'comments'=>$agent->daily_comments_limit,
 'likes'=>$agent->daily_likes_limit,
 'shares'=>$agent->daily_shares_limit,
];

$agent->last_limit_reset_date = now()->subDay()->toDateString();
$agent->save();
$mgr->resetDailyLimitsIfNeeded($agent);
$agent->refresh();
$second = [
 'posts'=>$agent->daily_posts_limit,
 'comments'=>$agent->daily_comments_limit,
 'likes'=>$agent->daily_likes_limit,
 'shares'=>$agent->daily_shares_limit,
];

echo 'quota_first='.json_encode($first).PHP_EOL;
echo 'quota_second='.json_encode($second).PHP_EOL;
echo 'quota_same=' . (($first==$second)?'yes':'no') . PHP_EOL;

echo 'mem_short=' . App\Models\AiAgentMemory::where('ai_agent_id',$agent->id)->where('type','short')->count() . PHP_EOL;
echo 'mem_medium=' . App\Models\AiAgentMemory::where('ai_agent_id',$agent->id)->where('type','medium')->count() . PHP_EOL;
echo 'mem_long=' . App\Models\AiAgentMemory::where('ai_agent_id',$agent->id)->where('type','long')->count() . PHP_EOL;

$latest = App\Models\AiAgentMemory::where('ai_agent_id',$agent->id)->latest('id')->limit(6)->get();
foreach($latest as $m){
  echo "mem#{$m->id} | {$m->type} | {$m->key}".PHP_EOL;
}
