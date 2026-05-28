<?php

use App\Services\Safety\ContentModerationService;
use App\Services\AI\Activity\AgentScheduleManager;
use App\Models\AiAgent;
use App\Models\User;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Str;

// 1. Test Content Moderation
echo "-- Moderation Test --\n";
$moderator = new ContentModerationService();

$safe = $moderator->analyze("Hello world, this is a great day!");
echo "Safe Content: " . $safe['action'] . "\n";

$flag = $moderator->analyze("This is a stupid idea.");
echo "Flagged Content: " . $flag['action'] . " (" . $flag['reason'] . ")\n";

$block = $moderator->analyze("I want to kill someone.");
echo "Blocked Content: " . $block['action'] . " (" . $block['reason'] . ")\n";

// 2. Test Scaled Limits
echo "\n-- Limits Test --\n";
if ($user = User::where('name', 'Limit_Tester')->first()) {
    $user->aiAgent()->delete();
    $user->delete();
}
$user = User::create(['name' => 'Limit_Tester', 'email' => 'limit@test.com', 'password' => 'pass']);
$agent = AiAgent::create(['user_id' => $user->id, 'country' => 'India', 'is_active' => true]);

$scheduleManager = new AgentScheduleManager();
// Force reset
$agent->last_limit_reset_date = null;
$agent->save();

$scheduleManager->resetDailyLimitsIfNeeded($agent);
$agent->refresh();

echo "Daily Posts Limit (Should be ~8-12): " . $agent->daily_posts_limit . "\n";
echo "Daily Comments Limit (Should be ~60-100): " . $agent->daily_comments_limit . "\n";

// 3. Test Notification Generation (CommentObserver)
echo "\n-- Notification Test --\n";
// Create a "Real User" and a Post
$realUser = User::create(['name' => 'Real_User', 'email' => 'real@test.com', 'password' => 'pass']);
$post = Post::create(['user_id' => $realUser->id, 'body' => 'My real post', 'type' => 'post', 'status' => 'published']);

// Agent comments on Real User Post
$comment = Comment::create([
    'user_id' => $agent->user_id,
    'post_id' => $post->id,
    'body' => 'Great post!',
    'status' => 'approved'
]);

// Check Notification
$notif = \App\Models\Notification::where('notifiable_id', $realUser->id)->latest()->first();
if ($notif) {
    echo "Notification Created: TRUE\n";
    echo "Type: " . $notif->type . "\n";
    echo "Data: " . json_encode($notif->data) . "\n";
} else {
    echo "Notification Created: FALSE (Check Observer logic)\n";
}

// Cleanup
$comment->delete();
$post->delete();
$realUser->delete();
$agent->delete();
$user->delete();
