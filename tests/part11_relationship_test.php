<?php

use App\Models\AiAgent;
use App\Models\User;
use App\Models\Post;
use App\Services\AI\Social\AgentRelationshipManager;
use App\Services\AI\Social\ChainReactionManager;
use App\Services\AI\Content\CommentGenerator;

// 1. Setup Agents
function createTestAgent($name, $leaning) {
    if ($user = User::where('name', $name)->first()) {
        $user->aiAgent()->delete();
        $user->posts()->delete();
        $user->comments()->delete();
        $user->delete();
    }
    
    $user = User::create(['name' => $name, 'email' => strtolower($name).'@test.com', 'password' => bcrypt('password')]);
    $agent = AiAgent::create([
        'user_id' => $user->id,
        'country' => 'India',
        'personality_type' => 'political',
        'political_leaning' => $leaning,
        'is_active' => true,
        'topics' => ['politics'],
        'language' => 'english'
    ]);
    return $agent;
}

$agentLeft = createTestAgent('Agent_Left', 'left');
$agentRight = createTestAgent('Agent_Right', 'right');
$agentLeft2 = createTestAgent('Agent_Left_Ally', 'left');

$manager = new AgentRelationshipManager();
echo "Relationship Left-Right: " . $manager->getRelationship($agentLeft, $agentRight) . "\n";
echo "Relationship Left-Left: " . $manager->getRelationship($agentLeft, $agentLeft2) . "\n";

// 2. Create Post
$post = Post::create([
    'user_id' => $agentLeft->user_id,
    'body' => 'We need more government spending to fix inequality.',
    'type' => 'post',
    'status' => 'published'
]);

// 3. Test Comment Generation Logic Directly
$commentGen = new CommentGenerator();
$reactionManager = new ChainReactionManager();

echo "Phase: " . $reactionManager->getReactionPhase($post) . "\n";

echo "\n--- Rival Comment (Agent Right) ---\n";
$rivalStyle = $commentGen->determineCommentStyle($agentRight, $post, 'rival');
echo "Style: $rivalStyle\n";
echo "Prompt: " . substr($commentGen->generateComment($agentRight, $post, 'rival'), 0, 100) . "...\n";

echo "\n--- Ally Comment (Agent Left Ally) ---\n";
$allyStyle = $commentGen->determineCommentStyle($agentLeft2, $post, 'ally');
echo "Style: $allyStyle\n";
echo "Prompt: " . substr($commentGen->generateComment($agentLeft2, $post, 'ally'), 0, 100) . "...\n";
