<?php
/*
|--------------------------------------------------------------------------
| Zestex - Social Network Platform.
|--------------------------------------------------------------------------
| Based on: Zestex - The Social Network Web Application.
|--------------------------------------------------------------------------
| Author: Vicky Bedardi Yadav
|--------------------------------------------------------------------------
| Branded by: Vicky Bedardi Yadav
| E-mail: vicktbedardi9@gmail.com
|--------------------------------------------------------------------------
| Copyright (c) Flip Basket Pvt Ltd. All rights reserved. 
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers\Api\User\Explore;

use App\Models\NewsCache;
use App\Models\Post;
use App\Models\User;
use App\Services\News\NewsAggregator;
use App\Support\Num;
use Illuminate\Http\Request;
use App\Database\Configs\Table;
use App\Services\Feed\FeedContextResolver;
use App\Services\Feed\FeedRanker;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;
use App\Http\Resources\User\People\PeopleCollection;
use App\Http\Resources\User\Timeline\TimelineCollection;
use App\Traits\Http\Controllers\Api\User\Explore\ValidatesPeopleFilters;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ExploreController extends Controller
{
    use SupportsApiResponses,
        ValidatesPeopleFilters;

    private $filter = [];
    private $me = null;

    public function __construct()
    {
        $this->me = me();
    }

    public function getPeople(Request $request)
    {
        $filterOptions = $this->getValidatedFilters($request);
        
        $people = User::active()->author()->excludeSelf()->whereNotIn('id', function ($query) {
            $query->select('following_id')->from(Table::FOLLOWS)->where('follower_id', me()->id);
        })->whereNotIn('id', function ($query) {
            $query->select('blocked_id')->from(Table::USER_BLOCKS)->where('blocker_id', me()->id);
        })->whereNotIn('id', function ($query) {
            $query->select('blocker_id')->from(Table::USER_BLOCKS)->where('blocked_id', me()->id);
        })->unless(empty($filterOptions['query']), function ($query) use ($filterOptions) {
            $query->where(function($query) use ($filterOptions) {
                $query->whereLike('username', "%{$filterOptions['query']}%")
                    ->orWhereLike('first_name', "%{$filterOptions['query']}%")
                    ->orWhereLike('last_name', "%{$filterOptions['query']}%")
                    ->orWhereLike('city', "%{$filterOptions['query']}%")
                    ->orWhereLike('caption', "%{$filterOptions['query']}%")
                    ->orWhereLike('bio', "%{$filterOptions['query']}%");
            });
        })
        ->orderByDesc('followers_count')
        ->orderByDesc('publications_count')
        ->simplePaginateManual(30, (! empty($filterOptions['page']) ? $filterOptions['page'] : 1));

        return $this->responseSuccess([
            'data' => PeopleCollection::make($people->items())
        ]);
    }

    public function getPosts(Request $request)
    {
        $filter = $request->array('filter');

        $this->filter['page'] = data_get_integer($filter, 'page', 1);
        $this->filter['onset'] = data_get_integer($filter, 'onset', 0);
        $this->filter['sort'] = data_get($filter, 'sort', config('feed-ranking.default_sort', FeedRanker::SORT_HOT));

        $ranker = app(FeedRanker::class);
        $context = app(FeedContextResolver::class)->resolve($request, $this->me, 'explore');
        $sort = $ranker->normalizeSort((string) $this->filter['sort']);
        $perPage = (int) config('post.paginate_per');
        $candidateLimit = max(
            (int) config('feed-ranking.candidate_min', 120),
            $perPage * (int) config('feed-ranking.candidate_multiplier', 12)
        );

        $feedORMQuery = Post::timelineFormatPosts()
            ->excludeBlockedUsers($this->me->id)
            ->when(! empty($this->filter['onset']), function($query) {
                $query->where('id', '>', $this->filter['onset']);
            })->when((! $this->me->isAdmin()), function($query) {
                $query->where(function($query) {
                    $query->where('user_id', $this->me->id)->orWhereHas('user', function($u) {
                        $u->author();
                    });
                });
            });

        $timelinePosts = $ranker->rank(
            $feedORMQuery->latest('id')->take($candidateLimit)->get(),
            $sort,
            $context
        )->forPage($this->filter['page'], $perPage)->values();
        
        return $this->responseSuccess([
            'data' => TimelineCollection::make($timelinePosts),
            'meta' => [
                'sort' => $sort,
                'supported_sorts' => $ranker->supportedSorts(),
                'context' => $context,
            ],
        ]);
    }

    public function getNews(Request $request)
    {
        $limit = max(1, min(10, (int) $request->input('limit', 3)));
        $items = $this->buildNewsPayload($limit);

        return $this->responseSuccess([
            'data' => $items,
        ]);
    }

    public function getNewsItem(Request $request)
    {
        $slug = (string) $request->input('slug', '');
        if ($slug === '') {
            return $this->responseSuccess(['data' => null]);
        }

        $item = collect($this->buildNewsPayload(100))
            ->first(fn ($row) => ($row['slug'] ?? '') === $slug);

        return $this->responseSuccess([
            'data' => $item,
        ]);
    }

    protected function buildNewsPayload(int $limit): array
    {
        $this->ensureFreshNewsCache();

        $peoplePool = User::query()
            ->active()
            ->author()
            ->select(['id', 'username', 'avatar', 'first_name', 'last_name'])
            ->orderByDesc('followers_count')
            ->limit(30)
            ->get();

        $defaultAvatar = asset(config('user.avatar'));

        // Extract Trending Keywords from Recent Posts
        $trendingKeywords = Cache::remember('trending_keywords_feed_v2', 60, function () {
            $posts = Post::active()
                ->whereNotNull('content')
                ->where('content', '!=', '')
                ->where('created_at', '>=', now()->subDays(30))
                ->get(['id', 'content', 'created_at']);

            $wordCounts = [];
            $wordPosts = [];

            // Ignore common stop words
            $stopWords = ['about','above','after','again','against','all','am','an','and','any','are','aren','as','at','be','because','been','before','being','below','between','both','but','by','can','cannot','could','couldn','did','didn','do','does','doesn','doing','don','down','during','each','few','for','from','further','had','hadn','has','hasn','have','haven','having','he','her','here','hers','herself','him','himself','his','how','if','in','into','is','isn','it','its','itself','let','me','more','most','mustn','my','myself','no','nor','not','of','off','on','once','only','or','other','ought','our','ours','ourselves','out','over','own','same','shan','she','should','shouldn','so','some','such','than','that','the','their','theirs','them','themselves','then','there','these','they','this','those','through','to','too','under','until','up','very','was','wasn','we','were','weren','what','when','where','which','while','who','whom','why','with','won','would','wouldn','you','your','yours','yourself','yourselves', 'https', 'http', 'com', 'www', 'just', 'like', 'will', 'make'];

            foreach ($posts as $post) {
                $text = (string)$post->content;
                
                // Extract hashtags
                if (preg_match_all('/#([a-zA-Z0-9_]+)/u', $text, $matches)) {
                    foreach ($matches[1] as $tag) {
                        $word = '#' . strtolower($tag);
                        $wordCounts[$word] = ($wordCounts[$word] ?? 0) + 1;
                        if (!isset($wordPosts[$word])) $wordPosts[$word] = $post;
                    }
                }

                // Extract normal words (length > 4) for fallback
                $cleanText = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($text)));
                $words = explode(' ', $cleanText);

                foreach ($words as $word) {
                    $word = trim($word);
                    if (strlen($word) > 4 && !in_array($word, $stopWords)) {
                        $wordCounts[$word] = ($wordCounts[$word] ?? 0) + 1;
                        if (!isset($wordPosts[$word])) $wordPosts[$word] = $post;
                    }
                }
            }

            arsort($wordCounts);
            
            $results = [];
            $count = 0;
            foreach ($wordCounts as $word => $freq) {
                if ($count >= 15) break;
                
                $displayTitle = str_starts_with($word, '#') ? $word : ucfirst($word);
                
                $results[] = [
                    'keyword' => $displayTitle,
                    'count' => $freq,
                    'sample_post' => $wordPosts[$word]
                ];
                $count++;
            }
            return $results;
        });

        // Use trending keywords first
        if (!empty($trendingKeywords)) {
            return collect($trendingKeywords)->take($limit)->map(function ($trend, $index) use ($peoplePool, $defaultAvatar) {
                $keyword = $trend['keyword'];
                $postCount = $trend['count'];
                $samplePost = $trend['sample_post'];

                $slug = Str::slug($keyword);
                if ($slug === '') $slug = 'trend-' . $index;

                $metaTime = $samplePost ? $samplePost->created_at->diffForHumans() : 'just now';
                $meta = $metaTime . ' | Trending | ' . Num::abbreviate($postCount) . ' posts';

                $avatars = $peoplePool
                    ->shuffle()
                    ->take(3)
                    ->values()
                    ->map(function (User $user) use ($defaultAvatar) {
                        return [
                            'username' => (string) $user->username,
                            'avatar_url' => $user->avatar_url ?: $defaultAvatar,
                        ];
                    })
                    ->all();

                $description = $samplePost ? Str::limit(strip_tags((string)$samplePost->content), 200) : "Trending topic on the platform.";

                return [
                    'id' => crc32($keyword . $index) & 0x7FFFFFFF, // positive integer
                    'slug' => $slug,
                    'title' => $keyword,
                    'description' => $description,
                    'meta' => $meta,
                    'source' => 'Trends',
                    'url' => '/search?q=' . urlencode($keyword), // Users can search it
                    'image_url' => '',
                    'published_at' => $samplePost ? $samplePost->created_at->toDateTimeString() : now()->toDateTimeString(),
                    'avatars' => $avatars,
                ];
            })->all();
        }

        // Fallback to real NewsCache if no trending topics found
        $poolSize = max(20, $limit * 6);
        $newsItems = NewsCache::query()
            ->whereNotNull('title')
            ->where('title', '!=', '')
            ->recent(72)
            ->orderByDesc('published_at')
            ->orderByDesc('is_trending')
            ->limit($poolSize)
            ->get();

        if ($newsItems->isEmpty()) {
            $newsItems = NewsCache::query()
                ->whereNotNull('title')
                ->where('title', '!=', '')
                ->orderByDesc('published_at')
                ->orderByDesc('is_trending')
                ->limit($poolSize)
                ->get();
        }

        if ($newsItems->isEmpty()) {
            return $this->buildFallbackNewsFromPosts($limit, $peoplePool, $defaultAvatar);
        }

        $offset = now()->minute % max(1, $newsItems->count());
        $rotated = $newsItems->slice($offset)->concat($newsItems->slice(0, $offset))->take($limit)->values();

        return $rotated->values()->map(function (NewsCache $news, int $index) use ($peoplePool, $defaultAvatar) {
            $slug = Str::slug((string) $news->title);
            $slug = $slug !== '' ? $slug : ('news-' . $news->id);
            $slug = $slug . '-' . $news->id;

            $publishedAt = $news->published_at ?: $news->created_at;
            $metaTime = $publishedAt ? $publishedAt->diffForHumans() : 'just now';

            $relatedPostsCount = $this->estimateRelatedPostCount((string) $news->title);
            $meta = $metaTime . ' | News | ' . Num::abbreviate($relatedPostsCount) . ' posts';

            $avatars = $peoplePool
                ->slice(($index * 3) % max(1, $peoplePool->count()), 3)
                ->values()
                ->map(function (User $user) use ($defaultAvatar) {
                    return [
                        'username' => (string) $user->username,
                        'avatar_url' => $user->avatar_url ?: $defaultAvatar,
                    ];
                })
                ->all();

            if (empty($avatars) && $peoplePool->isNotEmpty()) {
                $fallback = $peoplePool->first();
                $avatars[] = [
                    'username' => (string) $fallback->username,
                    'avatar_url' => $fallback->avatar_url ?: $defaultAvatar,
                ];
            }

            return [
                'id' => $news->id,
                'slug' => $slug,
                'title' => (string) $news->title,
                'description' => (string) ($news->description ?? ''),
                'meta' => $meta,
                'source' => (string) ($news->source ?? 'Unknown'),
                'url' => (string) ($news->url ?? ''),
                'image_url' => (string) ($news->image_url ?? ''),
                'published_at' => optional($publishedAt)->toDateTimeString(),
                'avatars' => $avatars,
            ];
        })->all();
    }

    protected function buildFallbackNewsFromPosts(int $limit, $peoplePool, string $defaultAvatar): array
    {
        $posts = Post::query()
            ->where('status', 'active')
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->latest('created_at')
            ->limit($limit)
            ->get(['id', 'content', 'created_at', 'hash_id']);

        if ($posts->isEmpty()) {
            return [
                [
                    'id' => 999999,
                    'slug' => 'welcome-to-zestex',
                    'title' => 'Welcome to Zestex!',
                    'description' => 'Start posting to see trending topics. New posts will appear here as Today\'s News based on trending keywords!',
                    'meta' => 'just now | Trending | 1 posts',
                    'source' => 'Zestex',
                    'url' => '/',
                    'image_url' => '',
                    'published_at' => now()->toDateTimeString(),
                    'avatars' => $peoplePool->take(3)->map(function($u) use ($defaultAvatar) {
                        return [
                            'username' => (string) $u->username,
                            'avatar_url' => $u->avatar_url ?: $defaultAvatar,
                        ];
                    })->all(),
                ]
            ];
        }

        return $posts->values()->map(function (Post $post, int $index) use ($peoplePool, $defaultAvatar) {
            $title = Str::of((string) $post->content)->replace("\n", ' ')->limit(110, '')->toString();
            $slug = Str::slug($title ?: ('post-' . $post->id));
            $slug = ($slug !== '' ? $slug : ('post-' . $post->id)) . '-p' . $post->id;

            $avatars = $peoplePool
                ->slice(($index * 3) % max(1, $peoplePool->count()), 3)
                ->values()
                ->map(function (User $user) use ($defaultAvatar) {
                    return [
                        'username' => (string) $user->username,
                        'avatar_url' => $user->avatar_url ?: $defaultAvatar,
                    ];
                })
                ->all();

            return [
                'id' => $post->id,
                'slug' => $slug,
                'title' => $title ?: 'Latest post update',
                'description' => Str::of((string) $post->content)->limit(400)->toString(),
                'meta' => $post->created_at->diffForHumans() . ' | News | ' . Num::abbreviate(1) . ' posts',
                'source' => 'ZESTEX Feed',
                'url' => url('/publication/' . ($post->hash_id ?? $post->id)),
                'image_url' => '',
                'published_at' => optional($post->created_at)->toDateTimeString(),
                'avatars' => $avatars,
            ];
        })->all();
    }

    protected function ensureFreshNewsCache(): void
    {
        $latest = NewsCache::query()->max('published_at');
        $isStale = true;

        if (!empty($latest)) {
            try {
                $isStale = now()->diffInMinutes($latest) > 30;
            } catch (\Throwable $e) {
                $isStale = true;
            }
        }

        if (!$isStale) {
            return;
        }

        if (Cache::get('news_cache_refresh_in_progress')) {
            return;
        }

        Cache::put('news_cache_refresh_in_progress', true, 120);

        try {
            app(NewsAggregator::class)->fetchAllNews();
        } catch (\Throwable $e) {
            Log::warning('Failed to auto-refresh news cache for Today\'s News', [
                'error' => $e->getMessage(),
            ]);
        } finally {
            Cache::forget('news_cache_refresh_in_progress');
        }
    }

    protected function estimateRelatedPostCount(string $title): int
    {
        $title = strtolower(trim($title));
        if ($title === '') {
            return Post::query()->where('created_at', '>=', now()->subDays(7))->count();
        }

        $keywords = collect(preg_split('/\s+/', $title) ?: [])
            ->map(fn ($word) => preg_replace('/[^a-z0-9]/', '', $word))
            ->filter(fn ($word) => strlen((string) $word) >= 5)
            ->unique()
            ->take(3)
            ->values();

        $query = Post::query()->where('created_at', '>=', now()->subDays(7));
        if ($keywords->isNotEmpty()) {
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('content', 'like', '%' . $word . '%');
                }
            });
        }

        return max(1, (int) $query->count());
    }

    /**
     * Get trending topics (hashtags) from last 24 hours
     */
    public function getTrendingTopics(Request $request)
    {
        $trendingService = app(\App\Services\Trending\TrendingService::class);
        $limit = min(20, max(1, (int) $request->input('limit', 10)));
        
        $topics = $trendingService->getTrendingTopics($limit);
        
        return $this->responseSuccess([
            'data' => $topics,
            'total' => count($topics),
        ]);
    }

    /**
     * Get trending posts from today
     */
    public function getTrendingToday(Request $request)
    {
        $trendingService = app(\App\Services\Trending\TrendingService::class);
        $limit = min(50, max(1, (int) $request->input('limit', 20)));
        
        $posts = $trendingService->getTrendingPostsToday($limit);
        
        return $this->responseSuccess([
            'data' => $posts,
            'total' => count($posts),
        ]);
    }

    /**
     * Get all trending data
     */
    public function getAllTrending(Request $request)
    {
        $trendingService = app(\App\Services\Trending\TrendingService::class);
        $data = $trendingService->getAllTrendingData(
            (int) $request->input('topics_limit', 8),
            (int) $request->input('posts_limit', 15)
        );
        
        return $this->responseSuccess([
            'data' => $data,
        ]);
    }

    /**
     * Search posts by hashtag
     */
    public function searchHashtag(Request $request)
    {
        $hashtag = (string) $request->input('hashtag', '');
        $page = max(1, (int) $request->input('page', 1));
        
        if (empty($hashtag)) {
            return $this->responseError([
                'message' => 'Hashtag is required',
            ], 400);
        }

        $trendingService = app(\App\Services\Trending\TrendingService::class);
        $posts = $trendingService->getPostsByHashtag($hashtag, $page, 20);
        
        return $this->responseSuccess([
            'data' => TimelineCollection::make($posts->items()),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'has_more' => $posts->hasMorePages(),
            ],
        ]);
    }

    /**
     * Search posts by keyword
     */
    public function searchKeyword(Request $request)
    {
        $keyword = (string) $request->input('keyword', '');
        $page = max(1, (int) $request->input('page', 1));
        
        if (empty($keyword)) {
            return $this->responseError([
                'message' => 'Keyword is required',
            ], 400);
        }

        $trendingService = app(\App\Services\Trending\TrendingService::class);
        $posts = $trendingService->getPostsByKeyword($keyword, $page, 20);
        
        return $this->responseSuccess([
            'data' => TimelineCollection::make($posts->items()),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'has_more' => $posts->hasMorePages(),
            ],
        ]);
    }

}
