<?php

use App\Models\AiAgent;
use App\Models\User;
use App\Services\AI\Language\LanguageManager;
use App\Services\AI\Content\ContextAwarePostGenerator;

// 1. Setup Indian Agent (Hindi Native)
function createHindiAgent($name) {
    if ($user = User::where('name', $name)->first()) {
        $user->aiAgent()->delete();
        $user->posts()->delete();
        $user->delete();
    }
    
    $user = User::create(['name' => $name, 'email' => strtolower($name).'@test.com', 'password' => bcrypt('password')]);
    $agent = AiAgent::create([
        'user_id' => $user->id,
        'country' => 'India',
        'personality_type' => 'political',
        'language' => 'Hindi', // Native
        'is_active' => true
    ]);
    return $agent;
}

$agent = createHindiAgent('Hindi_Speaker');
$langManager = new LanguageManager();

// 2. Test Context Switching
$localContext = ['global_impact_news' => []];
$globalContext = ['global_impact_news' => ['title' => 'Global War Declared']];

echo "Native Language: " . $agent->language . "\n";
echo "Output Lang (Local Context): " . $langManager->determineOutputLanguage($agent, $localContext) . "\n";
echo "Output Lang (Global Context - 70% chance): " . $langManager->determineOutputLanguage($agent, $globalContext) . "\n";

// 3. Test Translation Prompt
$postContent = "Breaking News: Aliens have landed in Delhi!";
$targetLang = "English";
echo "\nTranslation Prompt:\n" . $langManager->getTranslationPrompt($postContent, $targetLang) . "\n";

// 4. Test Generator Integration
$generator = new ContextAwarePostGenerator();
// Simulate internal call logic check
echo "\n-- Generator Test --\n";
// We can't easily check the *internal* prompt without reflection or modifying the class to expose it.
// But we can check if it runs without error.
try {
    $post = $generator->generatePost($agent);
    echo "Generated Post (Should be Hindi/Hinglish): " . substr($post, 0, 100) . "...\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
