<?php

namespace App\Services\AI\Activity;

use App\Models\AiAgent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AgentScheduleManager
{
    /**
     * Get agent's personalized daily schedule
     * Each agent has unique wake time, sleep time, and activity patterns
     */
    public function getAgentSchedule(AiAgent $agent): array
    {
        // Cache schedule per agent to maintain consistency
        return Cache::remember("agent_schedule_{$agent->id}", now()->addDays(30), function() use ($agent) {
            return $this->generatePersonalizedSchedule($agent);
        });
    }

    /**
     * Generate unique schedule for agent
     */
    protected function generatePersonalizedSchedule(AiAgent $agent): array
    {
        // Deterministic per agent to avoid random drift.
        $wakeHour = $this->deterministicValue($agent, 'wake_hour', 6, 9);

        // 23..26 (26 => 02:00 next day)
        $sleepHour = $this->deterministicValue($agent, 'sleep_hour', 23, 26);
        if ($sleepHour >= 24) {
            $sleepHour = $sleepHour - 24;
        }

        return [
            'wake_time' => $wakeHour,
            'sleep_time' => $sleepHour,
            'activity_windows' => $this->generateActivityWindows($wakeHour, $sleepHour, $agent),
        ];
    }

    /**
     * Generate activity windows throughout the day
     */
    /**
     * Generate activity windows throughout the day (Strict 7-Slot Schedule)
     */
    protected function generateActivityWindows(int $wakeHour, int $sleepHour, AiAgent $agent): array
    {
        $windows = [];
        $tz = config("countries.countries.{$agent->country}.timezone", 'UTC');

        // Helper to generate jittered time
        $addWindow = function ($baseStartHour, $baseStartMin, $endHour, $endMin, $type, $priority, $activities, $duration = 20) use (&$windows, $tz, $agent) {
            $start = Carbon::createFromTime($baseStartHour, $baseStartMin, 0, $tz);
            $end = Carbon::createFromTime($endHour, $endMin, 0, $tz);
            
            // Calculate max jitter (stay within bounds)
            $diffInMinutes = $end->diffInMinutes($start) - $duration;
            $diffInMinutes = max(0, $diffInMinutes);
            
            // Deterministic jitter within the slot.
            $jitter = $diffInMinutes > 0
                ? $this->deterministicValue($agent, "window:{$type}:jitter", 0, $diffInMinutes)
                : 0;
            $targetTime = $start->copy()->addMinutes($jitter);
            
            $windows[] = [
                'time' => $targetTime->format('H:i'), // "07:17"
                'duration' => $duration,
                'type' => $type,
                'priority' => $priority,
                'activities' => $activities,
            ];
        };

        // 1. Morning Dose (05:30 - 07:00) - Short, heavy news
        $addWindow(5, 30, 7, 0, 'morning_news', 'high', ['post' => 1, 'scan' => true]);

        // 2. Analysis Time (09:00 - 10:30) - Deep dive
        $addWindow(9, 0, 10, 30, 'analysis', 'high', ['post' => 1, 'comments' => [5, 10], 'likes' => [10, 15], 'shares' => [1, 3]]);

        // 3. Lunch Break (12:00 - 13:30) - Light/Funny
        $addWindow(12, 0, 13, 30, 'lunch_break', 'medium', ['post' => 1, 'comments' => [3, 6], 'likes' => [5, 10]]);

        // 4. Afternoon Update (15:00 - 16:00) - Updates
        $addWindow(15, 0, 16, 0, 'afternoon_update', 'medium', ['post' => 1, 'comments' => [5, 8], 'likes' => [15, 20]]);

        // 5. PRIME TIME (18:00 - 20:00) - 2 Posts!
        // Split into two sub-slots to ensure 2 posts
        // Slot A: 18:00 - 18:55
        $addWindow(18, 0, 18, 55, 'prime_time_1', 'critical', ['post' => 1, 'comments' => [10, 15], 'likes' => [20, 30], 'debates' => true]);
        // Slot B: 19:05 - 20:00
        $addWindow(19, 5, 20, 0, 'prime_time_2', 'critical', ['post' => 1, 'comments' => [10, 15], 'likes' => [20, 30]]);

        // 6. Night Wrap (21:30 - 22:30) - Summary/Philosophical
        $addWindow(21, 30, 22, 30, 'night_wrap', 'medium', ['post' => 1, 'replies' => true, 'likes' => [5, 10]]);

        // 7. Midnight (00:00 - 01:00) - Optional/Rare (20% chance)
        if ($this->deterministicValue($agent, 'window:midnight:enabled', 1, 100) <= 20) {
            $addWindow(0, 0, 1, 0, 'midnight_post', 'low', ['post' => 1, 'likes' => [5, 10]]);
        }
        
        // Weekend Special: Late party (Fri/Sat)
        if (now($tz)->isFriday() || now($tz)->isSaturday()) {
             // Add extra late night interaction (scan/comment)
             $addWindow(1, 30, 2, 30, 'weekend_vibes', 'medium', ['comments' => [5, 10], 'likes' => [10, 20]], 40);
        }

        // Sort just in case jitter messed up order (unlikely with distinct slots)
        usort($windows, function($a, $b) {
            return strcmp($a['time'], $b['time']);
        });

        return $windows;
    }

    /**
     * Reset daily activity limits if it's a new day
     */
    public function resetDailyLimitsIfNeeded(AiAgent $agent): void
    {
        // Part 9: Skip random reset if manual override is enabled
        if ($agent->is_manual_override) {
            return;
        }

        if (!$agent->last_limit_reset_date || Carbon::parse($agent->last_limit_reset_date)->isToday() === false) {
            // Deterministic daily quotas (stable for same agent+day).
            $dateKey = now()->toDateString();
            $quotaProfile = $this->getQuotaProfile($agent);

            $postBase = $this->deterministicValue($agent, "quota:{$dateKey}:posts", $quotaProfile['posts'][0], $quotaProfile['posts'][1], $dateKey);
            $commentBase = $this->deterministicValue($agent, "quota:{$dateKey}:comments", $quotaProfile['comments'][0], $quotaProfile['comments'][1], $dateKey);
            $likeBase = $this->deterministicValue($agent, "quota:{$dateKey}:likes", $quotaProfile['likes'][0], $quotaProfile['likes'][1], $dateKey);
            $shareBase = $this->deterministicValue($agent, "quota:{$dateKey}:shares", $quotaProfile['shares'][0], $quotaProfile['shares'][1], $dateKey);

            $agent->daily_posts_limit = $postBase;
            $agent->daily_posts_count = 0;

            $agent->daily_comments_limit = $commentBase;
            $agent->daily_comments_count = 0;

            $agent->daily_likes_limit = $likeBase;
            $agent->daily_likes_count = 0;

            $agent->daily_shares_limit = $shareBase;
            $agent->daily_shares_count = 0;
            
            $agent->last_limit_reset_date = $dateKey;
            $agent->save();
        }
    }

    /**
     * Increment activity count
     */
    public function incrementActivityCount(AiAgent $agent, string $type): void
    {
        $this->resetDailyLimitsIfNeeded($agent);
        
        match($type) {
            'post' => $agent->increment('daily_posts_count'),
            'comment' => $agent->increment('daily_comments_count'),
            'like' => $agent->increment('daily_likes_count'),
            'share' => $agent->increment('daily_shares_count'),
            default => null,
        };
    }

    /**
     * Check if daily limit reached (with optional modifier)
     */
    public function hasReachedDailyLimit(AiAgent $agent, string $type, float $modifier = 1.0): bool
    {
        if ($this->isAlwaysOn()) {
            return false;
        }
        $this->resetDailyLimitsIfNeeded($agent);
        
        return match($type) {
            'post' => $agent->daily_posts_count >= ($agent->daily_posts_limit * $modifier),
            'comment' => $agent->daily_comments_count >= ($agent->daily_comments_limit * $modifier),
            'like' => $agent->daily_likes_count >= ($agent->daily_likes_limit * $modifier),
            'share' => $agent->daily_shares_count >= ($agent->daily_shares_limit * $modifier),
            default => false,
        };
    }



    /**
     * Check if agent should be active right now
     */
    public function shouldBeActiveNow(AiAgent $agent): bool
    {
        if ($this->isAlwaysOn()) {
            return true;
        }
        $schedule = $this->getAgentSchedule($agent);
        $timezone = config("countries.countries.{$agent->country}.timezone", 'UTC');
        $now = Carbon::now($timezone);
        
        $currentHour = $now->hour;
        $currentMinute = $now->minute;

        // Check if within any activity window
        foreach ($schedule['activity_windows'] as $window) {
            list($windowHour, $windowMinute) = explode(':', $window['time']);
            $windowHour = (int) $windowHour;
            $windowMinute = (int) $windowMinute;
            
            $windowStart = Carbon::now($timezone)->setTime($windowHour, $windowMinute, 0);
            $windowEnd = $windowStart->copy()->addMinutes($window['duration']);

            if ($now->between($windowStart, $windowEnd)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get current activity window if active
     */
    public function getCurrentActivityWindow(AiAgent $agent): ?array
    {
        $schedule = $this->getAgentSchedule($agent);
        $timezone = config("countries.countries.{$agent->country}.timezone", 'UTC');
        $now = Carbon::now($timezone);

        foreach ($schedule['activity_windows'] as $window) {
            list($windowHour, $windowMinute) = explode(':', $window['time']);
            $windowHour = (int) $windowHour;
            $windowMinute = (int) $windowMinute;
            
            $windowStart = Carbon::now($timezone)->setTime($windowHour, $windowMinute, 0);
            $windowEnd = $windowStart->copy()->addMinutes($window['duration']);

            if ($now->between($windowStart, $windowEnd)) {
                return $window;
            }
        }

        return null;
    }

    /**
     * Get next activity window
     */
    public function getNextActivityWindow(AiAgent $agent): ?array
    {
        $schedule = $this->getAgentSchedule($agent);
        $timezone = config("countries.countries.{$agent->country}.timezone", 'UTC');
        $now = Carbon::now($timezone);

        $nextWindow = null;
        $minDiff = PHP_INT_MAX;

        foreach ($schedule['activity_windows'] as $window) {
            list($windowHour, $windowMinute) = explode(':', $window['time']);
            $windowHour = (int) $windowHour;
            $windowMinute = (int) $windowMinute;
            
            $windowStart = Carbon::now($timezone)->setTime($windowHour, $windowMinute, 0);
            
            if ($windowStart->lessThan($now)) {
                $windowStart->addDay();
            }

            $diff = $now->diffInMinutes($windowStart);
            
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nextWindow = array_merge($window, ['starts_in_minutes' => $diff]);
            }
        }

        return $nextWindow;
    }

    /**
     * Get activity type for current time
     */
    public function getCurrentActivityType(AiAgent $agent): ?string
    {
        $window = $this->getCurrentActivityWindow($agent);
        return $window['type'] ?? null;
    }

    /**
     * Should agent post now?
     */
    public function shouldPost(AiAgent $agent): bool
    {
        if ($this->isAlwaysOn()) {
            return !$this->hasReachedDailyLimit($agent, 'post');
        }
        $window = $this->getCurrentActivityWindow($agent);
        
        if (!$window) {
            return false;
        }

        if ($activities = $window['activities']) {
            // Check daily limit first
            if ($this->hasReachedDailyLimit($agent, 'post')) {
                return false;
            }
            
            if (isset($activities['post'])) {
            // Check if already posted in this window
            $lastPost = $agent->user->posts()
                ->where('created_at', '>=', now()->subMinutes($window['duration']))
                ->first();

            if ($lastPost) {
                return false; // Already posted in this window
            }

            return true;
        }

        return false;
    }

    }

    /**
     * Should agent comment now?
     */
    public function shouldComment(AiAgent $agent): bool
    {
        if ($this->isAlwaysOn()) {
            return !$this->hasReachedDailyLimit($agent, 'comment');
        }
        $window = $this->getCurrentActivityWindow($agent);
        
        if (!$window) {
            return false;
        }

        // Check daily limit first
        if ($this->hasReachedDailyLimit($agent, 'comment')) {
            return false;
        }

        return isset($window['activities']['comments']);
    }

    /**
     * Get comment count range for current window
     */
    public function getCommentCountRange(AiAgent $agent): array
    {
        $window = $this->getCurrentActivityWindow($agent);
        
        if (!$window || !isset($window['activities']['comments'])) {
            return [0, 0];
        }

        return $window['activities']['comments'];
    }

    /**
     * Get like count range for current window
     */
    public function getLikeCountRange(AiAgent $agent): array
    {
        $window = $this->getCurrentActivityWindow($agent);
        
        if (!$window || !isset($window['activities']['likes'])) {
            return [0, 0];
        }

        return $window['activities']['likes'];
    }

    /**
     * Is this peak activity time?
     */
    public function isPeakTime(AiAgent $agent): bool
    {
        $window = $this->getCurrentActivityWindow($agent);
        return $window && $window['priority'] === 'critical';
    }

    /**
     * Get post type for current window
     */
    public function getPostTypeForWindow(AiAgent $agent): string
    {
        $activityType = $this->getCurrentActivityType($agent);

        return match($activityType ?? 'always_on') {
            'always_on' => 'general',
            'morning_news' => 'breaking_news', // "Breaking news jaisi feel"
            'analysis' => 'analysis', // "Analysis wali post"
            'lunch_break' => 'light_content', // "Funny or Interesting"
            'afternoon_update' => 'general_update', // "Updates"
            'prime_time_1', 'prime_time_2' => 'major_post', // "Din ki sabse important post"
            'night_wrap' => 'emotional_wrap', // "Emotional ya philosophical"
            'midnight_post' => 'deep_thought', // "Deep thinking or Rare"
            default => 'general',
        };
    }

    protected function deterministicValue(AiAgent $agent, string $scope, int $min, int $max, ?string $extra = null): int
    {
        if ($min >= $max) {
            return $min;
        }

        $seed = implode('|', [
            'ai-agent',
            $agent->id,
            $agent->country,
            $agent->personality_type,
            $scope,
            $extra ?? 'static',
        ]);

        $hash = sprintf('%u', crc32($seed));
        $range = ($max - $min) + 1;
        return $min + ((int) $hash % $range);
    }

    protected function getQuotaProfile(AiAgent $agent): array
    {
        if ($agent->warm_up_stage && $agent->warm_up_stage !== 'active') {
            return match ($agent->warm_up_stage) {
                'day1' => ['posts' => [1, 2], 'comments' => [4, 8], 'likes' => [10, 20], 'shares' => [0, 1]],
                'day2' => ['posts' => [2, 4], 'comments' => [10, 18], 'likes' => [20, 35], 'shares' => [1, 3]],
                'day3' => ['posts' => [3, 5], 'comments' => [18, 28], 'likes' => [35, 55], 'shares' => [2, 5]],
                default => ['posts' => [2, 4], 'comments' => [10, 20], 'likes' => [20, 40], 'shares' => [1, 3]],
            };
        }

        return match ($agent->evolution_stage ?? 'seedling') {
            'seedling' => ['posts' => [5, 7], 'comments' => [30, 45], 'likes' => [60, 90], 'shares' => [5, 10]],
            'growing' => ['posts' => [6, 8], 'comments' => [40, 60], 'likes' => [80, 120], 'shares' => [8, 14]],
            'established' => ['posts' => [7, 10], 'comments' => [55, 85], 'likes' => [110, 160], 'shares' => [12, 20]],
            'influencer' => ['posts' => [8, 12], 'comments' => [70, 110], 'likes' => [150, 220], 'shares' => [18, 30]],
            default => ['posts' => [6, 9], 'comments' => [40, 70], 'likes' => [90, 140], 'shares' => [8, 16]],
        };
    }

    protected function isAlwaysOn(): bool
    {
        $envFlag = env('AI_AGENTS_ALWAYS_ON');
        if ($envFlag !== null && in_array(strtolower(trim((string) $envFlag)), ['1', 'true', 'on', 'yes'], true)) {
            return true;
        }

        try {
            $value = DB::table('admin_settings')->where('key', 'ai_agents_always_on')->value('value');
        } catch (\Throwable $e) {
            return false;
        }

        if ($value === null) {
            return false;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }
}
