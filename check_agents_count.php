<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $count = \App\Models\AiAgent::count();
    echo "Total Agents: " . $count . "\n";
    
    if ($count > 0) {
        echo "Recent Agents:\n";
        $agents = \App\Models\AiAgent::latest()->take(5)->with('user')->get();
        foreach ($agents as $agent) {
            echo "- " . ($agent->user->name ?? 'Unknown') . " (" . $agent->country . ") - Created " . $agent->created_at->diffForHumans() . "\n";
        }
    } else {
        echo "No agents found yet.\n";
    }

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
