<?php
/*
|--------------------------------------------------------------------------
| Public Explore Controller (Guest Mode)
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers\Api\Public\Explore;

use App\Models\NewsCache;
use App\Models\Post;
use App\Models\User;
use App\Services\News\NewsAggregator;
use App\Services\Trending\TrendingService;
use App\Services\Feed\FeedContextResolver;
use App\Services\Feed\FeedRanker;
use App\Support\Num;
use Illuminate\Http\Request;
use App\Enums\User\UserType;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;
use App\Http\Resources\User\People\PeopleCollection;
use App\Http\Resources\User\Timeline\TimelineCollection;
use App\Traits\Http\Controllers\Api\User\Explore\ValidatesPeopleFilters;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PublicExploreController extends Controller
{
    use SupportsApiResponses, ValidatesPeopleFilters;

    private array $filter = [];

    public function getPeople(Request $request)
    {
        $filterOptions = $this->getValidatedFilters($request);

        $people = User::active()->author()
            ->unless(empty($filterOptions['query']), function ($query) use ($filterOptions) {
                $query->where(function ($query) use ($filterOptions) {
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
            'data' => PeopleCollection::make($people->items()),
        ]);
    }

    public function getPosts(Request $request)
    {
        $filter = $request->array('filter');

        $this->filter['page'] = data_get_integer($filter, 'page', 1);
        $this->filter['onset'] = data_get_integer($filter, 'onset', 0);
        $this->filter['sort'] = data_get($filter, 'sort', config('feed-ranking.default_sort', FeedRanker::SORT_HOT));

        $ranker = app(FeedRanker::class);
        $context = app(FeedContextResolver::class)->resolve($request, null, 'explore_public');
        $sort = $ranker->normalizeSort((string) $this->filter['sort']);
        $perPage = (int) config('post.paginate_per');
        $candidateLimit = max(
            (int) config('feed-ranking.candidate_min', 120),
            $perPage * (int) config('feed-ranking.candidate_multiplier', 12)
        );

        $feedORMQuery = Post::timelineFormatPosts()
            ->when(! empty($this->filter['onset']), function ($query) {
                $query->where('id', '>', $this->filter['onset']);
            })
            ->whereHas('user', function ($u) {
                $u->where(function ($query) {
                    $query->author()->orWhere('type', UserType::AI_AGENT->value);
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

    /**
     * Get trending topics/hashtags
     */
    public function getTrendingTopics(Request $request)
    {
        $limit = max(1, min(50, (int) $request->input('limit', 10)));
        
        try {
            $service = app(\App\Services\Trending\TrendingService::class);
            $topics = $service->getTrendingTopics($limit);
            
            return $this->responseSuccess([
                'data' => $topics,
                'count' => count($topics),
            ]);
        } catch (\Exception $e) {
            Log::error('Trending topics error', ['error' => $e->getMessage()]);
            return $this->responseError(['message' => 'Failed to fetch trending topics'], 500);
        }
    }

    /**
     * Get trending posts from today
     */
    public function getTrendingToday(Request $request)
    {
        $limit = max(1, min(50, (int) $request->input('limit', 10)));
        $page = max(1, (int) $request->input('page', 1));
        
        try {
            $service = app(\App\Services\Trending\TrendingService::class);
            $trending = $service->getTrendingPostsToday($limit);
            
            // Convert to post objects if needed for TimelineCollection
            $posts = Post::whereIn('id', collect($trending)->pluck('id'))->get();
            
            return $this->responseSuccess([
                'data' => TimelineCollection::make($posts),
                'trending_data' => $trending,
                'count' => count($trending),
            ]);
        } catch (\Exception $e) {
            Log::error('Trending today error', ['error' => $e->getMessage()]);
            return $this->responseError(['message' => 'Failed to fetch trending posts'], 500);
        }
    }

    /**
     * Get all trending data combined
     */
    public function getAllTrending(Request $request)
    {
        $limit = max(1, min(50, (int) $request->input('limit', 10)));
        
        try {
            $service = app(\App\Services\Trending\TrendingService::class);
            $allTrending = $service->getAllTrendingData($limit);
            
            return $this->responseSuccess([
                'data' => $allTrending,
            ]);
        } catch (\Exception $e) {
            Log::error('All trending error', ['error' => $e->getMessage()]);
            return $this->responseError(['message' => 'Failed to fetch trending data'], 500);
        }
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

        $trendingKeywords = Cache::remember('trending_keywords_feed_v2', 60, function () {
            $posts = Post::active()
                ->whereNotNull('content')
                ->where('content', '!=', '')
                ->where('created_at', '>=', now()->subDays(30))
                ->get(['id', 'content', 'created_at']);

            $wordCounts = [];
            $wordPosts = [];

            $stopWords = ['about','above','after','again','against','all','am','an','and','any','are','aren','as','at','be','because','been','before','being','below','between','both','but','by','can','cannot','could','couldn','did','didn','do','does','doesn','doing','don','down','during','each','few','for','from','further','had','hadn','has','hasn','have','haven','having','he','her','here','hers','herself','him','himself','his','how','if','in','into','is','isn','it','its','itself','let','me','more','most','mustn','my','myself','no','nor','not','of','off','on','once','only','or','other','ought','our','ours','ourselves','out','over','own','same','shan','she','should','shouldn','so','some','such','than','that','the','their','theirs','them','themselves','then','there','these','they','this','those','through','to','too','under','until','up','very','was','wasn','we','were','weren','what','when','where','which','while','who','whom','why','with','won','would','wouldn','you','your','yours','yourself','yourselves', 'https', 'http', 'com', 'www', 'just', 'like', 'will', 'make'];

            foreach ($posts as $post) {
                $text = (string) $post->content;

                if (preg_match_all('/#([a-zA-Z0-9_]+)/u', $text, $matches)) {
                    foreach ($matches[1] as $tag) {
                        $word = '#' . strtolower($tag);
                        $wordCounts[$word] = ($wordCounts[$word] ?? 0) + 1;
                        if (!isset($wordPosts[$word])) {
                            $wordPosts[$word] = $post;
                        }
                    }
                }

                $cleanText = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', strip_tags($text)));
                $words = explode(' ', $cleanText);

                foreach ($words as $word) {
                    $word = trim($word);
                    if (strlen($word) > 4 && !in_array($word, $stopWords)) {
                        $wordCounts[$word] = ($wordCounts[$word] ?? 0) + 1;
                        if (!isset($wordPosts[$word])) {
                            $wordPosts[$word] = $post;
                        }
                    }
                }
            }

            arsort($wordCounts);

            $results = [];
            $count = 0;
            foreach ($wordCounts as $word => $freq) {
                if ($count >= 15) {
                    break;
                }

                $displayTitle = str_starts_with($word, '#') ? $word : ucfirst($word);

                $results[] = [
                    'keyword' => $displayTitle,
                    'count' => $freq,
                    'sample_post' => $wordPosts[$word],
                ];
                $count++;
            }
            return $results;
        });

        if (!empty($trendingKeywords)) {
            return collect($trendingKeywords)->take($limit)->map(function ($trend, $index) use ($peoplePool, $defaultAvatar) {
                $keyword = $trend['keyword'];
                $postCount = $trend['count'];
                $samplePost = $trend['sample_post'];

                $slug = Str::slug($keyword);
                if ($slug === '') {
                    $slug = 'trend-' . $index;
                }

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

                $description = $samplePost ? Str::limit(strip_tags((string) $samplePost->content), 200) : 'Trending topic on the platform.';

                return [
                    'id' => crc32($keyword . $index) & 0x7FFFFFFF,
                    'slug' => $slug,
                    'title' => $keyword,
                    'description' => $description,
                    'meta' => $meta,
                    'source' => 'Trends',
                    'url' => '/search?q=' . urlencode($keyword),
                    'image_url' => '',
                    'published_at' => $samplePost ? $samplePost->created_at->toDateTimeString() : now()->toDateTimeString(),
                    'avatars' => $avatars,
                ];
            })->all();
        }

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

        return $newsItems->map(function ($item) use ($peoplePool, $defaultAvatar) {
            $slug = Str::slug((string) $item->title);
            if ($slug === '') {
                $slug = 'news-' . $item->id;
            }

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

            return [
                'id' => $item->id,
                'slug' => $slug,
                'title' => $item->title,
                'description' => $item->description,
                'meta' => $item->published_at?->diffForHumans(),
                'source' => $item->source,
                'url' => $item->url,
                'image_url' => $item->image_url,
                'published_at' => optional($item->published_at)->toDateTimeString(),
                'avatars' => $avatars,
            ];
        })->take($limit)->values()->all();
    }

    protected function ensureFreshNewsCache(): void
    {
        if (!Schema::hasTable('news_cache')) {
            return;
        }

        $lastRefresh = Cache::get('news_cache_last_refresh');
        if ($lastRefresh && now()->diffInMinutes($lastRefresh) < 30) {
            return;
        }

        try {
            app(NewsAggregator::class)->refresh(25);
            Cache::put('news_cache_last_refresh', now(), now()->addHours(6));
        } catch (\Throwable $e) {
            Log::warning('News refresh failed', ['error' => $e->getMessage()]);
        }
    }
}
