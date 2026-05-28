<?php

namespace App\Services\Trending;

use App\Models\Post;
use App\Models\User;
use App\Support\Num;
use App\Services\Feed\FeedRanker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TrendingService
{
    /**
     * Get trending hashtags/topics from last 24 hours
     */
    public function getTrendingTopics(int $limit = 10): array
    {
        return Cache::remember('trending_topics_24h_v1', 120, function () use ($limit) {
            $posts = Post::active()
                ->where('created_at', '>=', now()->subHours(24))
                ->whereNotNull('content')
                ->where('content', '!=', '')
                ->get(['id', 'content', 'created_at', 'comments_count', 'bookmarks_count', 'views_count']);

            if ($posts->isEmpty()) {
                $posts = Post::active()
                    ->where('created_at', '>=', now()->subDays(7))
                    ->whereNotNull('content')
                    ->where('content', '!=', '')
                    ->get(['id', 'content', 'created_at', 'comments_count', 'bookmarks_count', 'views_count']);
            }

            $hashtagCounts = [];
            $hashtagPosts = [];

            foreach ($posts as $post) {
                // Extract hashtags - match # followed by alphanumeric/underscore
                if (preg_match_all('/#([a-zA-Z0-9_]+)/u', $post->content, $matches)) {
                    foreach ($matches[1] as $tag) {
                        $hashtag = '#' . strtolower($tag);
                        $hashtagCounts[$hashtag] = ($hashtagCounts[$hashtag] ?? 0) + 1;
                        
                        if (!isset($hashtagPosts[$hashtag])) {
                            $hashtagPosts[$hashtag] = $post;
                        }
                    }
                }
            }

            arsort($hashtagCounts);

            $results = [];
            foreach (array_slice($hashtagCounts, 0, $limit) as $hashtag => $count) {
                $post = $hashtagPosts[$hashtag] ?? null;
                
                $results[] = [
                    'hashtag' => $hashtag,
                    'post_count' => $count,
                    'engagement' => $this->calculateEngagement($post),
                    'sample_post_id' => $post?->id,
                    'trending_rank' => count($results) + 1,
                ];
            }

            return $results;
        });
    }

    /**
     * Get trending posts from today ordered by engagement
     */
    public function getTrendingPostsToday(int $limit = 20): array
    {
        return Cache::remember('trending_posts_today_v1', 60, function () use ($limit) {
            $candidateLimit = max($limit * 10, 120);
            $posts = Post::active()
                ->timelineFormatPosts()
                ->where('created_at', '>=', now()->startOfDay())
                ->latest('id')
                ->limit($candidateLimit)
                ->get();

            if ($posts->isEmpty()) {
                // Fallback to recent posts if no posts today
                $posts = Post::active()
                    ->timelineFormatPosts()
                    ->where('created_at', '>=', now()->subDays(7))
                    ->latest('id')
                    ->limit($candidateLimit)
                    ->get();
            }

            $ranker = app(FeedRanker::class);
            $posts = $ranker->rank($posts, FeedRanker::SORT_HOT, [
                'feature' => 'trending',
            ])->take($limit)->values();

            return $posts->map(function ($post, $index) {
                return [
                    'id' => $post->id,
                    'user_id' => $post->user_id,
                    'content' => Str::limit(strip_tags($post->content ?? ''), 150),
                    'engagement_score' => $this->calculateEngagement($post),
                    'comments' => $post->comments_count ?? 0,
                    'bookmarks' => $post->bookmarks_count ?? 0,
                    'views' => $post->views_count ?? 0,
                    'trending_rank' => $index + 1,
                    'created_at' => optional($post->created_at)->toDateTimeString(),
                ];
            })->toArray();
        });
    }

    /**
     * Get posts for a specific hashtag/topic
     */
    public function getPostsByHashtag(string $hashtag, int $page = 1, int $perPage = 20): \Illuminate\Pagination\Paginator
    {
        $cleanHashtag = str_starts_with($hashtag, '#') ? $hashtag : "#$hashtag";
        $searchPattern = '%' . addcslashes($cleanHashtag, '%_') . '%';

        return Post::active()
            ->where('created_at', '>=', now()->subDays(90))
            ->whereRaw("LOWER(content) LIKE LOWER(?)", [$searchPattern])
            ->with(['user:id,username,avatar,first_name,last_name', 'reactions', 'comments'])
            ->orderBy('created_at', 'desc')
            ->orderBy('comments_count', 'desc')
            ->orderBy('bookmarks_count', 'desc')
            ->simplePaginateManual($perPage, $page);
    }

    /**
     * Get posts for a keyword search
     */
    public function getPostsByKeyword(string $keyword, int $page = 1, int $perPage = 20): \Illuminate\Pagination\Paginator
    {
        $searchPattern = '%' . addcslashes($keyword, '%_') . '%';

        return Post::active()
            ->where('created_at', '>=', now()->subDays(90))
            ->where(function ($query) use ($searchPattern) {
                $query->whereRaw("LOWER(content) LIKE LOWER(?)", [$searchPattern]);
            })
            ->with(['user:id,username,avatar,first_name,last_name', 'reactions', 'comments'])
            ->orderBy('created_at', 'desc')
            ->orderBy('comments_count', 'desc')
            ->orderBy('bookmarks_count', 'desc')
            ->simplePaginateManual($perPage, $page);
    }

    /**
     * Calculate engagement score for sorting
     */
    private function calculateEngagement(?Post $post): int
    {
        if (!$post) {
            return 0;
        }

        return ($post->comments_count * 2) + 
               ($post->bookmarks_count * 3) + 
               ($post->views_count) + 
               ($post->quotes_count * 2);
    }

    /**
     * Get all trending data combined
     */
    public function getAllTrendingData(int $topicsLimit = 8, int $postsLimit = 15): array
    {
        $defaultAvatar = asset(config('user.avatar'));
        
        $topics = $this->getTrendingTopics($topicsLimit);
        $todayPosts = $this->getTrendingPostsToday($postsLimit);
        
        // Get top users
        $topUsers = User::active()
            ->author()
            ->orderByDesc('followers_count')
            ->limit(5)
            ->get(['id', 'username', 'avatar', 'followers_count'])
            ->map(function ($user) use ($defaultAvatar) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'avatar_url' => $user->avatar_url ?: $defaultAvatar,
                    'followers' => $user->followers_count,
                ];
            })->toArray();

        return [
            'trending_topics' => $topics,
            'trending_posts_today' => $todayPosts,
            'trending_users' => $topUsers,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Clear all trending caches
     */
    public function clearCache(): void
    {
        Cache::forget('trending_topics_24h_v1');
        Cache::forget('trending_posts_today_v1');
    }
}
