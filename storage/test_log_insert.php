<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$agent=App\Models\AiAgent::find(1);
$l=$agent->logActivity('debug_test',['x'=>1]);
$f=App\Models\AiAgentActivityLog::find($l->id);
echo 'log_id='.$f->id.PHP_EOL;
echo 'created_at=' . ($f->created_at ? $f->created_at->toDateTimeString() : 'NULL') . PHP_EOL;
echo 'action_type=' . $f->action_type . PHP_EOL;
