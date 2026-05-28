<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
foreach (['ai_runtime_enabled','ai_engagement_enabled','auto_agent_creation_enabled'] as $k) {
    $v = Illuminate\Support\Facades\DB::table('admin_settings')->where('key', $k)->value('value');
    echo $k . '=' . ($v === null ? 'NULL' : $v) . PHP_EOL;
}
