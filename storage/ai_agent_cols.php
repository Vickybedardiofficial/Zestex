<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cols = Illuminate\Support\Facades\Schema::getColumnListing('ai_agents');
foreach($cols as $c){ if(in_array($c,['bio','avatar_url','avatar_source','name','username'])) echo $c.PHP_EOL; }
