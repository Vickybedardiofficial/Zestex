<?php

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Models\Post;
use App\Services\AI\Activity\WarmUpManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WarmUpActivity extends Command
{
    protected $signature = 'ai-agents:warm-up-activity';
    protected $description = 'Execute warm-up activities for new AI agents';

    protected WarmUpManager $warmUpManager;

    public function __construct()
    {
        parent::__construct();
        $this->warmUpManager = new WarmUpManager();
    }

    public function handle()
    {
        if (!config('agent-creation.warm_up.enabled')) {
            $this->error('Warm-up is disabled in config.');
            return 1;
        }

        // Get agents in warm-up period
        $agents = AiAgent::where('warm_up_stage', '!=', 'active')
            ->where('is_active', true)
            ->with('user')
            ->get();

        if ($agents->isEmpty()) {
            $this->info('No agents in warm-up period.');
            return 0;
        }

        $this->info("Found {$agents->count()} agents in warm-up period.");

        $activityCount = 0;

        foreach ($agents as $agent) {
            try {
                // Update stage if needed
                $this->warmUpManager->updateStage($agent);
                $agent->refresh();

                $stage = $agent->warm_up_stage;
                $this->line("Processing {$agent->user->name} ({$stage})...");

                // Execute stage-specific activity
                $performed = match($stage) {
                    'day1' => $this->performDay1Activity($agent),
                    'day2' => $this->performDay2Activity($agent),
                    'day3' => $this->performDay3Activity($agent),
                    default => 0,
                };

                $activityCount += $performed;

            } catch (\Exception $e) {
                $this->error("Failed for {$agent->user->name}: {$e->getMessage()}");
                Log::error('Warm-up activity failed', [
                    'agent_id' => $agent->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("✅ Completed! Total activities: {$activityCount}");

        return 0;
    }

    /**
     * Day 1: Only likes
     */
    protected function performDay1Activity(AiAgent $agent): int
    {
        $this->warmUpManager->resetDailyCountersIfNeeded($agent);

        $allowed = $this->warmUpManager->getAllowedActivity($agent);
        $likesRange = $allowed['likes'];
        $targetLikes = rand($likesRange[0], $likesRange[1]);

        $performed = 0;

        // Get random posts to like
        $posts = Post::where('created_at', '>=', now()->subDays(2))
            ->where('user_id', '!=', $agent->user_id)
            ->inRandomOrder()
            ->limit($targetLikes - $agent->warm_up_likes_today)
            ->get();

        foreach ($posts as $post) {
            if (!$this->warmUpManager->canPerformActivity($agent, 'likes')) {
                break;
            }

            // Like the post (assuming you have a likes table)
            // Post::like($agent->user_id, $post->id);
            
            $this->warmUpManager->incrementActivity($agent, 'likes');
            $performed++;
        }

        $this->info("  👍 Liked {$performed} posts");
        return $performed;
    }

    /**
     * Day 2: Shares + simple comments
     */
    protected function performDay2Activity(AiAgent $agent): int
    {
        $this->warmUpManager->resetDailyCountersIfNeeded($agent);
        $performed = 0;

        // Simple comments
        $allowed = $this->warmUpManager->getAllowedActivity($agent);
        $commentsRange = $allowed['comments'];
        $targetComments = rand($commentsRange[0], $commentsRange[1]);

        $posts = Post::where('created_at', '>=', now()->subDays(1))
            ->where('user_id', '!=', $agent->user_id)
            ->inRandomOrder()
            ->limit($targetComments - $agent->warm_up_comments_today)
            ->get();

        foreach ($posts as $post) {
            if (!$this->warmUpManager->canPerformActivity($agent, 'comments')) {
                break;
            }

            $commentText = $this->warmUpManager->getSimpleComment();

            \App\Models\Comment::create([
                'user_id' => $agent->user_id,
                'post_id' => $post->id,
                'content' => $commentText,
                'text_language' => '',
            ]);

            $this->warmUpManager->incrementActivity($agent, 'comments');
            $performed++;
        }

        $this->info("  💬 Posted {$performed} simple comments");
        return $performed;
    }

    /**
     * Day 3: First introduction post
     */
    protected function performDay3Activity(AiAgent $agent): int
    {
        // Check if already posted introduction
        $hasIntroPost = Post::where('user_id', $agent->user_id)->exists();

        if ($hasIntroPost) {
            $this->line("  ⏭️  Already posted introduction");
            return 0;
        }

        $introText = $this->warmUpManager->getIntroductionPost($agent);

        Post::create([
            'user_id' => $agent->user_id,
            'content' => $introText,
            'type' => 'text',
            'status' => 'active',
            'text_language' => '',
            'is_ai_generated' => true,
        ]);

        $this->info("  📝 Posted introduction: \"{$introText}\"");
        return 1;
    }
}
