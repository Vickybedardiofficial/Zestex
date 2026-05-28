<?php

use App\Models\AiAgent;
use App\Models\User;
use App\Services\AI\Content\TrollManager;
use App\Services\AI\Content\ContextAwarePostGenerator;

// 1. Setup Troll Agent
function createTrollAgent($name) {
    if ($user = User::where('name', $name)->first()) {
        $user->aiAgent()->delete();
        $user->posts()->delete();
        $user->delete();
    }
    
    $user = User::create(['name' => $name, 'email' => strtolower($name).'@test.com', 'password' => bcrypt('password')]);
    $agent = AiAgent::create([
        'user_id' => $user->id,
        'country' => 'India',
        'personality_type' => 'troll', // Crucial
        'is_active' => true,
        'language' => 'english'
    ]);
    return $agent;
}

$troll = createTrollAgent('Troll_Master');
$manager = new TrollManager();

// 2. Test Detection Mechanisms
$news1 = ['title' => 'Leader makes U-Turn on Tax Policy', 'summary' => 'Reverses stance completely.'];
$news2 = ['title' => 'Climate Summit leaders arrive in Private Jets', 'summary' => 'Irony overload.'];
$news3 = ['title' => 'Normal news about weather', 'summary' => 'Nothing special.'];

echo "News 1 Trigger: " . ($manager->detectTrigger($news1) ?? 'None') . "\n";
echo "News 2 Trigger: " . ($manager->detectTrigger($news2) ?? 'None') . "\n";
echo "News 3 Trigger: " . ($manager->detectTrigger($news3) ?? 'None') . "\n";

// 3. Test Prompt Generation
$trigger = $manager->detectTrigger($news2);
if ($trigger) {
    $prompt = $manager->generateTrollPrompt($troll, $news2, $trigger);
    echo "\nGenerated Prompt (Snippet):\n" . substr($prompt, 0, 150) . "...\n";
}

// 4. Test Safety Filter
$unsafeText = "You are ugly and your family is stupid.";
$safeText = "The hypocrisy is hilarious. #Irony";

echo "\nUnsafe Text Safe? " . ($manager->isSafe($unsafeText) ? 'Yes' : 'No') . "\n";
echo "Safe Text Safe? " . ($manager->isSafe($safeText) ? 'Yes' : 'No') . "\n";
