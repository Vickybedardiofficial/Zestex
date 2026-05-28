<?php

namespace App\Services\Analytics;

use App\Models\AiAgent;
use App\Models\Post;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyticsService
{
    /**
     * Get Real-Time Stats for Dashboard
     */
    public function getRealTimeStats(): array
    {
        return Cache::remember('admin_realtime_stats', 60, function() {
            return [
                'active_agents_1h' => AiAgent::where('last_activity_at', '>=', now()->subHour())->count(),
                'posts_today' => Post::whereDate('created_at', today())->count(),
                'comments_today' => Comment::whereDate('created_at', today())->count(),
                'real_users_new' => User::whereDoesntHave('aiAgent')->whereDate('created_at', today())->count(),
                'trending_topics' => $this->getTrendingTopics(),
            ];
        });
    }

    /**
     * Get Daily Report Data
     */
    public function getDailyReport(): array
    {
        return Cache::remember('admin_daily_report', 3600, function() {
            return [
                'top_agents' => $this->getTopAgents(),
                'viral_posts' => $this->getViralPosts(),
                'engagement_heatmap' => $this->getEngagementHeatmap(),
            ];
        });
    }

    /**
     * Get Individual Agent Performance
     */
    public function getAgentPerformance(AiAgent $agent): array
    {
        $postColumns = Schema::getColumnListing('posts');
        $likesColumn = in_array('likes_count', $postColumns, true)
            ? 'likes_count'
            : (in_array('views_count', $postColumns, true) ? 'views_count' : null);

        return [
            'total_posts_week' => $agent->user->posts()->where('created_at', '>=', now()->subWeek())->count(),
            'avg_likes' => $likesColumn ? ((int) ($agent->user->posts()->avg($likesColumn) ?? 0)) : 0,
            'reputation_score' => $agent->reputation_score,
            'evolution_stage' => $agent->evolution_stage,
            'last_active' => $agent->last_activity_at ? $agent->last_activity_at->diffForHumans() : 'Never',
        ];
    }

    protected function getTrendingTopics(): array
    {
        // Simple mock or extraction from recent posts
        // In real system, this would aggregation hashtags
        return [
            '#Election2024',
            '#Crypto',
            '#AI',
            '#SpaceX'
        ]; 
    }

    protected function getTopAgents(): array
    {
        return AiAgent::orderBy('reputation_score', 'desc')
            ->take(5)
            ->with('user:id,first_name,last_name,avatar')
            ->get()
            ->map(function($agent) {
                $userName = $agent->user ? $agent->user->name : "Agent {$agent->id}";
                return [
                    'name' => $userName,
                    'reputation' => $agent->reputation_score,
                    'posts_today' => $agent->daily_posts_count
                ];
            })
            ->toArray();
    }

    protected function getViralPosts(): array
    {
        $postColumns = Schema::getColumnListing('posts');
        $rankColumn = in_array('likes_count', $postColumns, true)
            ? 'likes_count'
            : (in_array('views_count', $postColumns, true) ? 'views_count' : 'created_at');

        return Post::orderBy($rankColumn, 'desc')
            ->whereDate('created_at', today())
            ->take(5)
            ->with('user:id,first_name,last_name')
            ->get()
            ->map(function($post) {
                $likes = 0;
                if (Schema::hasColumn('posts', 'likes_count')) {
                    $likes = (int) ($post->likes_count ?? 0);
                } elseif (Schema::hasColumn('posts', 'views_count')) {
                    $likes = (int) ($post->views_count ?? 0);
                }

                return [
                    'author' => $post->user ? $post->user->name : 'Unknown',
                    'content' => substr((string) $post->content, 0, 50) . '...',
                    'likes' => $likes
                ];
            })
            ->toArray();
    }

    protected function getEngagementHeatmap(): array
    {
        // Mock heatmap data for UI (Hour -> Activity Level)
        $heatmap = [];
        for ($i=0; $i<24; $i++) {
            $heatmap[$i] = rand(100, 1000); // Activity count per hour
        }
        return $heatmap;
    }
}
