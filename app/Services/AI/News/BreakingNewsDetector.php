<?php

namespace App\Services\AI\News;

use App\Models\NewsCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class BreakingNewsDetector
{
    protected array $breakingKeywords = [
        'Breaking', 'Urgent', 'Crisis', 'Emergency', 
        'War', 'Earthquake', 'Tsunami', 'Attack', 'Blast',
        'Resigns', 'Collapsed', 'Pandemic', 'Lockdown', 
        'Assassinated', 'Explosion', 'Disaster'
    ];

    protected array $liveEventKeywords = [
        'Live', 'Match', 'Score', 'Election', 'Vote', 
        'Counting', 'Debate', 'Ceremony', 'Launch', 
        'Finale', 'Award', 'Protest'
    ];

    /**
     * Check for breaking news in a specific country
     */
    public function getBreakingNews(string $countryCode): ?NewsCache
    {
        if (!$this->hasNewsColumns()) {
            return null;
        }

        // Check cache first to avoid DB hits every run
        return Cache::remember("breaking_news_{$countryCode}", 300, function() use ($countryCode) {
            
            // Look for news in the last hour
            $recentNews = NewsCache::where('created_at', '>=', now()->subHour())
                ->where(function($query) use ($countryCode) {
                    // Match country specific sources or global
                    $query->where('source', 'like', "%_{$countryCode}%")
                          ->orWhere('source', 'google_news'); // Simplified source check
                })
                ->get();

            foreach ($recentNews as $news) {
                if ($this->isBreaking($news->title)) {
                    return $news;
                }
            }

            return null;
        });
    }

    /**
     * Check for live events
     */
    public function getLiveEvent(string $countryCode): ?NewsCache
    {
        if (!$this->hasNewsColumns()) {
            return null;
        }

        return Cache::remember("live_event_{$countryCode}", 300, function() use ($countryCode) {
            $recentNews = NewsCache::where('created_at', '>=', now()->subMinutes(30)) // Very recent
                ->where(function($query) use ($countryCode) {
                    $query->where('source', 'like', "%_{$countryCode}%")
                          ->orWhere('source', 'google_news');
                })
                ->get();

            foreach ($recentNews as $news) {
                if ($this->isLiveEvent($news->title)) {
                    return $news;
                }
            }
            return null;
        });
    }

    /**
     * Determine if a title indicates breaking news
     */
    protected function isBreaking(string $title): bool
    {
        foreach ($this->breakingKeywords as $keyword) {
            if (stripos($title, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if a title indicates a live event
     */
    protected function isLiveEvent(string $title): bool
    {
        foreach ($this->liveEventKeywords as $keyword) {
            if (stripos($title, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function hasNewsColumns(): bool
    {
        if (!Schema::hasTable('news_cache')) {
            return false;
        }

        $columns = Schema::getColumnListing('news_cache');
        return in_array('title', $columns, true) && in_array('source', $columns, true);
    }
}
