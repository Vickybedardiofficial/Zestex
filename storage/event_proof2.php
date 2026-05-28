<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$e = App\Models\SpecialEvent::where('type','election')->where('country','IN')->where('status','active')->latest('id')->first();
if(!$e){ echo "no_event\n"; exit(0);} 
echo 'event_id='.$e->id.PHP_EOL;
echo 'event_title='.$e->title.PHP_EOL;
echo 'boost='.$e->boost_factor.PHP_EOL;
echo 'active_agents=' . App\Models\AiAgent::where('is_active',1)->count() . PHP_EOL;
