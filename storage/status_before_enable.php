<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$aiUsers = App\Models\User::where('type','ai_agent')->get();
$total = $aiUsers->count();
$withAvatar = $aiUsers->filter(fn($u)=>!empty((string)$u->avatar))->count();
$withoutAvatar = $total - $withAvatar;
$posts24 = App\Models\Post::whereHas('user', fn($q)=>$q->where('type','ai_agent'))->where('created_at','>=',now()->subDay())->count();
$activeAgents = App\Models\AiAgent::where('is_active',1)->count();
$warmupNonActive = App\Models\AiAgent::where('is_active',1)->where('warm_up_stage','!=','active')->count();
echo "ai_total={$total}\n";
echo "ai_with_avatar={$withAvatar}\n";
echo "ai_without_avatar={$withoutAvatar}\n";
echo "ai_posts_24h={$posts24}\n";
echo "active_agents={$activeAgents}\n";
echo "warmup_not_active={$warmupNonActive}\n";
