<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$agent = App\Models\AiAgent::whereHas('user', fn($q)=>$q->where('type','ai_agent')->where(function($qq){$qq->whereNull('avatar')->orWhere('avatar','');}))->with('user')->first();
if(!$agent){ echo "no_missing_avatar_agent\n"; exit(0);} 
echo 'test_agent_id='.$agent->id.PHP_EOL;
echo 'test_user_id='.$agent->user_id.PHP_EOL;
echo 'test_name='.$agent->user->name.PHP_EOL;
