<?php

namespace App\Services\AI\Activity;

use App\Models\AiAgent;
use Carbon\Carbon;

class WarmUpManager
{
    /**
     * Check if agent is in warm-up period
     */
    public function isInWarmUp(AiAgent $agent): bool
    {
        return $agent->warm_up_stage !== 'active';
    }

    /**
     * Get current warm-up stage
     */
    public function getCurrentStage(AiAgent $agent): string
    {
        if (!$agent->account_created_at) {
            return 'active';
        }

        $daysOld = Carbon::parse($agent->account_created_at)->diffInDays(now());
        $daysOld = max(0, (int) floor($daysOld));

        if ($daysOld >= 3) {
            return 'active';
        }

        return 'day' . ($daysOld + 1);
    }

    /**
     * Update agent's warm-up stage
     */
    public function updateStage(AiAgent $agent): void
    {
        $newStage = $this->getCurrentStage($agent);
        
        if ($agent->warm_up_stage !== $newStage) {
            $agent->warm_up_stage = $newStage;
            
            if ($newStage === 'active') {
                $agent->warm_up_completed_at = now();
            }
            
            $agent->save();
        }
    }

    /**
     * Check if agent can post
     */
    public function canPost(AiAgent $agent): bool
    {
        $stage = $agent->warm_up_stage;
        
        if ($stage === 'active') {
            return true;
        }

        $config = config("agent-creation.warm_up.{$stage}");
        return $config['can_post'] ?? false;
    }

    /**
     * Check if agent can comment
     */
    public function canComment(AiAgent $agent): bool
    {
        $stage = $agent->warm_up_stage;
        
        if ($stage === 'active') {
            return true;
        }

        $config = config("agent-creation.warm_up.{$stage}");
        return isset($config['comments']);
    }

    /**
     * Get allowed activity for current stage
     */
    public function getAllowedActivity(AiAgent $agent): array
    {
        $stage = $agent->warm_up_stage;
        
        if ($stage === 'active') {
            return [
                'likes' => PHP_INT_MAX,
                'shares' => PHP_INT_MAX,
                'comments' => PHP_INT_MAX,
                'posts' => PHP_INT_MAX,
            ];
        }

        $config = config("agent-creation.warm_up.{$stage}", []);
        
        return [
            'likes' => $config['likes'] ?? [0, 0],
            'shares' => $config['shares'] ?? [0, 0],
            'comments' => $config['comments'] ?? [0, 0],
            'posts' => $config['posts'] ?? 0,
        ];
    }

    /**
     * Reset daily counters if new day
     */
    public function resetDailyCountersIfNeeded(AiAgent $agent): void
    {
        if (!$agent->warm_up_activity_date || 
            Carbon::parse($agent->warm_up_activity_date)->isToday() === false) {
            
            $agent->warm_up_likes_today = 0;
            $agent->warm_up_shares_today = 0;
            $agent->warm_up_comments_today = 0;
            $agent->warm_up_activity_date = now()->toDateString();
            $agent->save();
        }
    }

    /**
     * Check if can perform activity today
     */
    public function canPerformActivity(AiAgent $agent, string $activityType): bool
    {
        $this->resetDailyCountersIfNeeded($agent);
        
        $allowed = $this->getAllowedActivity($agent);
        $activityLimits = $allowed[$activityType] ?? [0, 0];
        
        if (is_array($activityLimits)) {
            $maxAllowed = $activityLimits[1]; // Max value
        } else {
            $maxAllowed = $activityLimits;
        }

        $currentCount = match($activityType) {
            'likes' => $agent->warm_up_likes_today,
            'shares' => $agent->warm_up_shares_today,
            'comments' => $agent->warm_up_comments_today,
            'posts' => 0, // Posts are special - checked separately
            default => 0,
        };

        return $currentCount < $maxAllowed;
    }

    /**
     * Increment activity counter
     */
    public function incrementActivity(AiAgent $agent, string $activityType): void
    {
        $this->resetDailyCountersIfNeeded($agent);

        match($activityType) {
            'likes' => $agent->increment('warm_up_likes_today'),
            'shares' => $agent->increment('warm_up_shares_today'),
            'comments' => $agent->increment('warm_up_comments_today'),
            default => null,
        };
    }

    /**
     * Get introduction post for day 3
     */
    public function getIntroductionPost(AiAgent $agent): string
    {
        $language = $agent->language ?? 'en';
        $templates = config("agent-creation.introduction_templates.{$language}", 
                           config('agent-creation.introduction_templates.en'));

        $template = $templates[array_rand($templates)];
        
        // Replace {topic} with agent's personality
        $topic = match($agent->personality_type) {
            'political' => 'politics and current affairs',
            'sports' => 'sports',
            'tech' => 'technology',
            'entertainment' => 'entertainment',
            default => 'various topics',
        };

        return str_replace('{topic}', $topic, $template);
    }

    /**
     * Get simple comment for day 2
     */
    public function getSimpleComment(): string
    {
        $comments = [
            'Interesting point.',
            'I agree with this.',
            'Well said!',
            'This makes sense.',
            'Good observation.',
            'True that.',
            'Exactly!',
            'Fair point.',
            'Makes you think.',
            'Couldn\'t agree more.',
        ];

        return $comments[array_rand($comments)];
    }
}
