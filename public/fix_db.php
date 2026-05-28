<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "<h1>Fixing Migrations via DB...</h1>";

$migrationsToSkip = [
    '2026_02_16_000001_create_ai_agents_table',
    '2026_02_15_183516_create_ai_agents_table',
    '2026_02_16_000002_create_ai_agent_activity_logs_table',
    '2026_02_15_183516_create_ai_agent_activity_logs_table',
    '2026_02_16_000004_create_news_cache_table',
    '2026_02_15_192109_create_news_cache_table',
];

$batch = \Illuminate\Support\Facades\DB::table('migrations')->max('batch') + 1;

foreach ($migrationsToSkip as $migration) {
    // Check if exists
    $exists = \Illuminate\Support\Facades\DB::table('migrations')->where('migration', $migration)->exists();
    if (!$exists) {
        \Illuminate\Support\Facades\DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => $batch
        ]);
        echo "Marked as run: " . $migration . "<br>";
    } else {
        echo "Already marked as run: " . $migration . "<br>";
    }
}

echo "<hr>";
echo "Finished marking. Please run migration next.<br>";

/*
try {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo "Migration Success!<br>";
    echo "<pre>" . \Illuminate\Support\Facades\Artisan::output() . "</pre>";
} catch (\Throwable $e) {
    echo "Migration Failed: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
*/
