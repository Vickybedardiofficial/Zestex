<?php

use App\Models\SpecialEvent;
use App\Services\AI\Events\SpecialEventsManager;
use App\Services\Analytics\AnalyticsService;
use App\Models\AiAgent;
use App\Models\User;

// 1. Setup Data
if ($user = User::where('name', 'Event_Tester')->first()) {
    $user->aiAgent()->delete();
    $user->delete();
}
$user = User::create(['name' => 'Event_Tester', 'email' => 'event@test.com', 'password' => 'pass']);
$agent = AiAgent::create(['user_id' => $user->id, 'country' => 'USA', 'is_active' => true]);

// 2. Create Active Event
SpecialEvent::where('type', 'election')->delete();
SpecialEvent::create([
    'title' => 'US Election Mock',
    'type' => 'election',
    'country' => 'USA',
    'start_date' => now(),
    'status' => 'active',
    'boost_factor' => 1.5,
    'context_prompt' => 'Analyze the candidates.'
]);

// 3. Test SpecialEventsManager
echo "-- Events Test --\n";
$eventManager = new SpecialEventsManager();
$boost = $eventManager->getActivityBoostFactor($agent);
echo "Boost Factor (Should be 1.5): " . $boost . "\n";

$context = $eventManager->getEventContext($agent);
echo "Event Context (Should contain 'US Election Mock'):\n" . $context . "\n";

// 4. Test AnalyticsService
echo "\n-- Analytics Test --\n";
$analytics = new AnalyticsService();
$realTime = $analytics->getRealTimeStats();
echo "Active Agents (1h): " . $realTime['active_agents_1h'] . "\n";
echo "Posts Today: " . $realTime['posts_today'] . "\n";

$performance = $analytics->getAgentPerformance($agent);
echo "Agent Reputation: " . $performance['reputation_score'] . "\n";

// Cleanup
$agent->delete();
$user->delete();
