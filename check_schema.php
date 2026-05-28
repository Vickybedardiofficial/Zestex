<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "--- CHECKING AI AGENTS SCHEMA ---\n";

try {
    if (Schema::hasTable('ai_agents')) {
        echo "Table 'ai_agents' exists.\n";
        
        $columns = Schema::getColumnListing('ai_agents');
        echo "Columns: " . implode(', ', $columns) . "\n";
        
        if (in_array('is_active', $columns)) {
            echo "SUCCESS: 'is_active' column FOUND.\n";
        } else {
            echo "FAILURE: 'is_active' column MISSING.\n";
        }

        // Check migration batch
        $migrations = DB::table('migrations')->where('migration', 'like', '%ai_agents%')->get();
        echo "\nMigrations Run:\n";
        foreach ($migrations as $m) {
            echo "- {$m->migration} (Batch: {$m->batch})\n";
        }

    } else {
        echo "FAILURE: Table 'ai_agents' DOES NOT EXIST.\n";
    }

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
