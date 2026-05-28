<?php

namespace App\Services\News;

use App\Models\NewsCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NewsAggregator
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('news-sources', []);
    }

    /**
     * Fetch latest news from all sources
     */
    public function fetchAllNews(): array
    {
        $results = [
            'google_news' => 0,
            'rss' => 0,
            'total' => 0
        ];

        try {
            // Fetch from Google News API
            if ($this->isSourceEnabled('google_news')) {
                $results['google_news'] = $this->fetchGoogleNews();
            }

            // Fetch from RSS feeds
            if ($this->isSourceEnabled('rss')) {
                $results['rss'] = $this->fetchRSSFeeds();
            }

            $results['total'] = $results['google_news'] + $results['rss'];

            // Clean old news (older than 7 days)
            $this->cleanOldNews();

            return $results;

        } catch (\Exception $e) {
            Log::error('News aggregation failed', [
                'error' => $e->getMessage()
            ]);
            return $results;
        }
    }

    /**
     * Fetch from Google News API
     */
    protected function fetchGoogleNews(): int
    {
        $apiKey = env('NEWS_API_KEY');
        if (empty($apiKey)) {
            return 0;
        }

        $count = 0;
        $categories = ['politics', 'sports', 'technology', 'entertainment'];
        $country = 'in'; // India

        foreach ($categories as $category) {
            try {
                $response = Http::get('https://newsapi.org/v2/top-headlines', [
                    'apiKey' => $apiKey,
                    'country' => $country,
                    'category' => $category === 'politics' ? 'general' : $category,
                    'pageSize' => 20
                ]);

                if ($response->successful()) {
                    $articles = $response->json()['articles'] ?? [];
                    
                    foreach ($articles as $article) {
                        $this->storeNews([
                            'source' => 'google_news',
                            'category' => $category,
                            'title' => $article['title'] ?? '',
                            'description' => $article['description'] ?? '',
                            'url' => $article['url'] ?? '',
                            'image_url' => $article['urlToImage'] ?? null,
                            'published_at' => $article['publishedAt'] ?? now()
                        ]);
                        $count++;
                    }
                }

            } catch (\Exception $e) {
                Log::warning('Google News fetch failed', [
                    'category' => $category,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }

    /**
     * Fetch from RSS feeds
     */
    protected function fetchRSSFeeds(): int
    {
        $feeds = [
            'bbc' => [
                'url' => 'http://feeds.bbci.co.uk/news/rss.xml',
                'category' => 'general'
            ],
            'cnn' => [
                'url' => 'http://rss.cnn.com/rss/edition.rss',
                'category' => 'general'
            ],
            'times_of_india' => [
                'url' => 'https://timesofindia.indiatimes.com/rssfeedstopstories.cms',
                'category' => 'politics'
            ],
            'ndtv' => [
                'url' => 'https://feeds.feedburner.com/ndtvnews-top-stories',
                'category' => 'politics'
            ]
        ];

        $count = 0;

        foreach ($feeds as $feedName => $feedData) {
            try {
                $xml = simplexml_load_file($feedData['url']);
                
                if ($xml === false) {
                    continue;
                }

                foreach ($xml->channel->item as $item) {
                    $this->storeNews([
                        'source' => 'rss_' . $feedName,
                        'category' => $feedData['category'],
                        'title' => (string) $item->title,
                        'description' => (string) ($item->description ?? ''),
                        'url' => (string) $item->link,
                        'image_url' => null,
                        'published_at' => isset($item->pubDate) ? date('Y-m-d H:i:s', strtotime((string) $item->pubDate)) : now()
                    ]);
                    $count++;
                }

            } catch (\Exception $e) {
                Log::warning('RSS feed fetch failed', [
                    'feed' => $feedName,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }

    /**
     * Store news in cache
     */
    protected function storeNews(array $data): void
    {
        try {
            // Check if already exists
            $exists = NewsCache::where('url', $data['url'])->exists();
            
            if (!$exists) {
                NewsCache::create($data);
            }

        } catch (\Exception $e) {
            Log::warning('News storage failed', [
                'title' => $data['title'] ?? '',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get trending topics
     */
    public function getTrendingTopics(): array
    {
        return Cache::remember('trending_topics', 1800, function () {
            // Get most frequent keywords from recent news
            $recentNews = NewsCache::recent(6)->get();
            
            $keywords = [];
            foreach ($recentNews as $news) {
                $words = str_word_count(strtolower($news->title), 1);
                foreach ($words as $word) {
                    if (strlen($word) > 4) { // Only words longer than 4 chars
                        $keywords[$word] = ($keywords[$word] ?? 0) + 1;
                    }
                }
            }

            arsort($keywords);
            return array_slice(array_keys($keywords), 0, 10);
        });
    }

    /**
     * Get latest news by category
     */
    public function getLatestNews(string $category = null, int $limit = 10): array
    {
        $query = NewsCache::recent(24)->orderBy('published_at', 'desc');

        if ($category) {
            $query->category($category);
        }

        return $query->limit($limit)->get()->toArray();
    }

    /**
     * Clean old news
     */
    protected function cleanOldNews(): void
    {
        NewsCache::where('created_at', '<', now()->subDays(7))->delete();
    }

    /**
     * Check if source is enabled
     */
    protected function isSourceEnabled(string $source): bool
    {
        return $this->config['sources'][$source]['enabled'] ?? true;
    }
}
