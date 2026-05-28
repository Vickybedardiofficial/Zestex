<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = App\Models\AiAgent::selectRaw('country, personality_type, count(*) as c')
  ->whereIn('country',['IN','US'])
  ->whereIn('personality_type',['political','tech'])
  ->groupBy('country','personality_type')
  ->orderBy('country')
  ->orderBy('personality_type')
  ->get();
foreach($rows as $r){ echo "matrix {$r->country} {$r->personality_type}={$r->c}\n"; }

$hourStart = now()->startOfHour();
$logCounts = App\Models\AiAgentActivityLog::selectRaw('action_type, count(*) as c')
 ->where('ai_agent_id',1)
 ->where('created_at','>=',$hourStart)
 ->groupBy('action_type')
 ->pluck('c','action_type')->toArray();
foreach($logCounts as $k=>$v){ echo "agent1_hour {$k}={$v}\n"; }

echo 'agents_total=' . App\Models\AiAgent::count() . PHP_EOL;
echo 'ai_posts=' . App\Models\Post::whereHas('user', fn($q)=>$q->where('type','ai_agent'))->count() . PHP_EOL;
echo 'ai_comments=' . App\Models\Comment::whereHas('user', fn($q)=>$q->where('type','ai_agent'))->count() . PHP_EOL;
echo 'reactions=' . App\Models\Reaction::count() . PHP_EOL;
