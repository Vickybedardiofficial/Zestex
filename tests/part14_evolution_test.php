<?php

use App\Models\AiAgent;
use App\Models\User;
use App\Services\AI\Evolution\AgentEvolutionManager;
use App\Services\AI\Evolution\ReputationService;
use App\Services\AI\Content\ContextAwarePostGenerator;

// 1. Setup Newcomer Agent (Day 1)
function createMyAgent($name, $daysOld = 0, $rep = 0) {
    if ($user = User::where('name', $name)->first()) {
        $user->aiAgent()->delete();
        $user->posts()->delete();
        $user->delete();
    }
    
    $user = User::create(['name' => $name, 'email' => strtolower($name).'@test.com', 'password' => bcrypt('password')]);
    // Force created_at
    $user->created_at = now()->subDays($daysOld);
    $user->save();

    $agent = AiAgent::create([
        'user_id' => $user->id,
        'country' => 'India',
        'personality_type' => 'tech',
        'evolution_stage' => 'newcomer',
        'reputation_score' => $rep,
        'is_active' => true,
        'created_at' => now()->subDays($daysOld) // Set age
    ]);
    return $agent;
}

$newcomer = createMyAgent('Noob_Agent', 1, 10);
$influencer = createMyAgent('Pro_Agent', 200, 1500);

$evoManager = new AgentEvolutionManager();
$repService = new ReputationService();

// 2. Test Stage Upgrade Logic
echo "Newcomer Stage (Before): " . $newcomer->evolution_stage . "\n";
$evoManager->checkAndUpgradeStage($newcomer);
echo "Newcomer Stage (After Check - Should be newcomer): " . $newcomer->fresh()->evolution_stage . "\n";

echo "Influencer Stage (Before): " . $influencer->evolution_stage . "\n";
// Force stage to newcomer to test upgrade
$influencer->update(['evolution_stage' => 'newcomer']); 
$evoManager->checkAndUpgradeStage($influencer);
echo "Influencer Stage (After Check - Should be influencer): " . $influencer->fresh()->evolution_stage . "\n";

// 3. Test Prompt Context
echo "\n-- Prompt Context --\n";
echo "Newcomer Context: " . substr($evoManager->getEvolutionContext('newcomer'), 0, 50) . "...\n";
echo "Influencer Context: " . substr($evoManager->getEvolutionContext('influencer'), 0, 50) . "...\n";

// 4. Test Reputation Service
echo "\n-- Reputation Test --\n";
echo "Current Rep: " . $newcomer->reputation_score . "\n";
$repService->updateReputation($newcomer);
echo "Updated Rep (Calculated): " . $newcomer->fresh()->reputation_score . "\n";
