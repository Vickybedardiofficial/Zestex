<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\AiAgent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

echo "--- STARTING DIRECT AGENT CREATION DEBUG ---\n";

try {
    // 1. Create User
    echo "1. Creating User...\n";
    $username = 'debug_agent_' . time();
    $user = User::create([
        'name' => 'Debug Agent',
        'username' => $username,
        'email' => $username . '@debug.local',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
        'country' => 'US',
    ]);
    echo "User created: ID {$user->id}\n";

    // 2. Create Agent
    echo "2. Creating Agent Entry...\n";
    $agent = AiAgent::create([
        'user_id' => $user->id,
        'personality_type' => 'general',
        'country' => 'US',
        'language' => 'en',
        'posting_frequency' => 5,
        'is_active' => true,
        'auto_created' => true,
        'account_created_at' => now(),
        'warm_up_stage' => 'day1',
        // Identity fields
        'age' => 30,
        'city' => 'Debug City',
        'date_of_birth' => '1995-01-01',
        'topics' => ['Debugging', 'PHP'],
        'profession' => 'Debugger',
        'political_leaning' => 'Neutral',
        'writing_style' => 'Technical',
        'editorial_tone' => 'Direct',
    ]);
    echo "Agent created: ID {$agent->id}\n";
    echo "SUCCESS: Agent created manually.\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
} catch (\Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}
