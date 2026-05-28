<?php

namespace App\Services\AI\Activity;

use Carbon\Carbon;

class ActivityScheduler
{
    /**
     * Check if agent should be active based on time of day
     */
    public function shouldBeActive(string $timezone = 'Asia/Kolkata'): bool
    {
        $now = Carbon::now($timezone);
        $hour = $now->hour;
        
        // Peak activity hours with probability
        $activityProbability = $this->getActivityProbability($hour);
        
        return rand(1, 100) <= $activityProbability;
    }
    
    /**
     * Get activity probability based on hour (0-100%)
     */
    protected function getActivityProbability(int $hour): int
    {
        // Morning peak: 7-9 AM (80%)
        if ($hour >= 7 && $hour <= 9) {
            return 80;
        }
        
        // Lunch time: 12-2 PM (60%)
        if ($hour >= 12 && $hour <= 14) {
            return 60;
        }
        
        // Evening peak: 6-9 PM (90% - highest activity)
        if ($hour >= 18 && $hour <= 21) {
            return 90;
        }
        
        // Late night: 10 PM - 12 AM (40%)
        if ($hour >= 22 || $hour <= 0) {
            return 40;
        }
        
        // Early morning: 1-6 AM (10% - sleep time)
        if ($hour >= 1 && $hour <= 6) {
            return 10;
        }
        
        // Rest of day: 30%
        return 30;
    }
    
    /**
     * Get best time to post based on current time
     */
    public function getBestPostType(string $timezone = 'Asia/Kolkata'): string
    {
        $now = Carbon::now($timezone);
        $hour = $now->hour;
        
        // Morning: News reactions
        if ($hour >= 7 && $hour <= 10) {
            return 'news_reaction';
        }
        
        // Afternoon: General discussions
        if ($hour >= 12 && $hour <= 15) {
            return 'general';
        }
        
        // Evening: Trolling & debates (peak engagement)
        if ($hour >= 18 && $hour <= 21) {
            return rand(0, 1) ? 'troll' : 'news_reaction';
        }
        
        // Late night: Philosophical
        if ($hour >= 22 || $hour <= 1) {
            return 'philosophical';
        }
        
        // Default: Random
        return ['news_reaction', 'general', 'meme'][rand(0, 2)];
    }
    
    /**
     * Calculate next activity time with realistic variation
     */
    public function getNextActivityTime(int $baseMinutes = 60): Carbon
    {
        // Add random variation: ±20%
        $variation = rand(-20, 20);
        $minutes = $baseMinutes + ($baseMinutes * $variation / 100);
        
        return Carbon::now()->addMinutes($minutes);
    }
}
