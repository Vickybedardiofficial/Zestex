<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$hourStart = now()->startOfHour();
$counts = App\Models\AiAgentActivityLog::selectRaw('action_type, count(*) as c')
 ->where('ai_agent_id',1)
 ->whereNotNull('created_at')
 ->where('created_at','>=',$hourStart)
 ->groupBy('action_type')
 ->pluck('c','action_type')->toArray();
if(empty($counts)){ echo "no_hour_logs\n"; }
foreach($counts as $k=>$v){ echo "agent1_hour {$k}={$v}\n"; }
