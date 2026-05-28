<?php

namespace App\Services\AI\News;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CountryNewsAggregator
{
    /**
     * Fetch news for specific country
     */
    public function fetchCountryNews(string $countryCode, int $limit = 10): array
    {
        $cacheKey = "country_news_v2:" . strtoupper($countryCode) . ":limit:" . (int) $limit;

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($countryCode, $limit) {
            return $this->fetchCountryNewsFresh($countryCode, $limit);
        });
    }

    public function fetchCountryNewsRealtime(string $countryCode, int $limit = 10): array
    {
        return $this->fetchCountryNewsFresh($countryCode, $limit);
    }

    protected function fetchCountryNewsFresh(string $countryCode, int $limit = 10): array
    {
        $startedAt = microtime(true);
        $budgetSeconds = max(2, (int) config('agent-creation.news.request_budget_seconds', 8));

        $sources = config("countries.news_sources.{$countryCode}", []);
        $globalSources = config('countries.news_sources.global', []);
        
        $allSources = array_merge($globalSources, $sources);
        $maxSources = max(1, (int) config('agent-creation.news.max_sources_per_country', 6));
        if (count($allSources) > $maxSources) {
            $allSources = array_slice($allSources, 0, $maxSources, true);
        }
        $articles = [];
        
        foreach ($allSources as $sourceName => $rssUrl) {
            if ((microtime(true) - $startedAt) >= $budgetSeconds) {
                Log::warning("CountryNewsAggregator budget reached for {$countryCode}; stopping further RSS source fetch.");
                break;
            }

            try {
                $feed = $this->parseRSS($rssUrl);
                
                foreach ($feed as $item) {
                    $published = $this->normalizePublishedAt($item['pubDate'] ?? null);
                    $articles[] = [
                        'title' => $item['title'] ?? '',
                        'description' => $item['description'] ?? '',
                        'url' => $item['link'] ?? '',
                        'image_url' => $item['image_url'] ?? null,
                        'video_url' => $item['video_url'] ?? null,
                        'source' => $sourceName,
                        'country' => $countryCode,
                        'published_at' => $published,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("Failed to fetch from {$sourceName}: " . $e->getMessage());
                continue;
            }
        }

        if ((bool) config('agent-creation.news.realtime_enabled', false) && (microtime(true) - $startedAt) < $budgetSeconds) {
            $remainingBudget = (int) floor($budgetSeconds - (microtime(true) - $startedAt));
            $realtime = $this->fetchRealtimeWebNews($countryCode, max(4, (int) ceil($limit / 2)), max(1, $remainingBudget));
            if (!empty($realtime)) {
                $articles = array_merge($realtime, $articles);
            }
        }

        $articles = $this->dedupeArticles($articles);
        usort($articles, function ($a, $b) {
            $aTs = strtotime((string) ($a['published_at'] ?? 'now')) ?: 0;
            $bTs = strtotime((string) ($b['published_at'] ?? 'now')) ?: 0;

            if ($aTs === $bTs) {
                $aMedia = (!empty($a['video_url']) ? 2 : 0) + (!empty($a['image_url']) ? 1 : 0);
                $bMedia = (!empty($b['video_url']) ? 2 : 0) + (!empty($b['image_url']) ? 1 : 0);
                return $bMedia <=> $aMedia;
            }

            return $bTs <=> $aTs;
        });

        return array_slice($articles, 0, $limit);
    }

    protected function fetchRealtimeWebNews(string $countryCode, int $limit = 6, int $timeoutSeconds = 8): array
    {
        $query = $this->buildRealtimeQuery($countryCode);
        $articles = [];
        $timeoutSeconds = max(1, $timeoutSeconds);

        try {
            $serperKey = (string) config('services.serper.key', '');
            $serperEndpoint = (string) config('services.serper.endpoint', 'https://serper.dev/search');
            if ($serperKey !== '' && $serperEndpoint !== '') {
                $serper = Http::withHeaders([
                    'X-API-KEY' => $serperKey,
                    'Content-Type' => 'application/json',
                ])->timeout($timeoutSeconds)->post($serperEndpoint, [
                    'q' => $query,
                    'num' => $limit,
                    'gl' => strtolower($countryCode),
                ]);

                if ($serper->successful()) {
                    $payload = $serper->json();
                    foreach ((array) ($payload['organic'] ?? []) as $item) {
                        $articles[] = [
                            'title' => (string) ($item['title'] ?? ''),
                            'description' => (string) ($item['snippet'] ?? ''),
                            'url' => (string) ($item['link'] ?? ''),
                            'image_url' => null,
                            'video_url' => null,
                            'source' => 'serper',
                            'country' => strtoupper($countryCode),
                            'published_at' => now()->toDateTimeString(),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Serper realtime fetch failed: ' . $e->getMessage());
        }

        try {
            $tavilyKey = (string) config('services.tavily.key', '');
            $tavilyEndpoint = (string) config('services.tavily.endpoint', 'https://api.tavily.com/search');
            if ($tavilyKey !== '' && $tavilyEndpoint !== '') {
                $tavily = Http::timeout($timeoutSeconds)->post($tavilyEndpoint, [
                    'api_key' => $tavilyKey,
                    'query' => $query,
                    'search_depth' => 'advanced',
                    'max_results' => $limit,
                    'topic' => 'news',
                ]);

                if ($tavily->successful()) {
                    $payload = $tavily->json();
                    foreach ((array) ($payload['results'] ?? []) as $item) {
                        $articles[] = [
                            'title' => (string) ($item['title'] ?? ''),
                            'description' => (string) ($item['content'] ?? ''),
                            'url' => (string) ($item['url'] ?? ''),
                            'image_url' => null,
                            'video_url' => null,
                            'source' => 'tavily',
                            'country' => strtoupper($countryCode),
                            'published_at' => now()->toDateTimeString(),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Tavily realtime fetch failed: ' . $e->getMessage());
        }

        return array_slice($this->dedupeArticles($articles), 0, $limit);
    }

    protected function buildRealtimeQuery(string $countryCode): string
    {
        $country = strtoupper(trim($countryCode));
        $topic = match ($country) {
            'IN' => 'India',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            default => $country,
        };

        return "latest breaking news {$topic} last 2 hours";
    }
    
    /**
     * Parse RSS feed
     */
    protected function parseRSS(string $url): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'ZestexNewsBot/1.0 (+https://localhost)',
                'Accept' => 'application/rss+xml, application/xml, text/xml;q=0.9, */*;q=0.8',
            ])
                ->connectTimeout(max(1, (int) config('agent-creation.news.connect_timeout_seconds', 2)))
                ->timeout(max(2, (int) config('agent-creation.news.timeout_seconds', 3)))
                ->retry(max(0, (int) config('agent-creation.news.retry_times', 1)), 200)
                ->get($url);
            
            if (!$response->successful()) {
                return [];
            }
            
            $xml = simplexml_load_string($response->body());
            
            if (!$xml) {
                return [];
            }
            
            $items = [];
            
            // Handle RSS 2.0
            if (isset($xml->channel->item)) {
                foreach ($xml->channel->item as $item) {
                    $media = $this->extractRssMedia($item);
                    $items[] = [
                        'title' => (string) $item->title,
                        'description' => (string) ($item->description ?? ''),
                        'link' => (string) $item->link,
                        'pubDate' => (string) ($item->pubDate ?? now()),
                        'image_url' => $media['image_url'],
                        'video_url' => $media['video_url'],
                    ];
                }
            }
            
            // Handle Atom
            elseif (isset($xml->entry)) {
                foreach ($xml->entry as $entry) {
                    $items[] = [
                        'title' => (string) $entry->title,
                        'description' => (string) ($entry->summary ?? $entry->content ?? ''),
                        'link' => (string) ($entry->link['href'] ?? $entry->id),
                        'pubDate' => (string) ($entry->published ?? $entry->updated ?? now()),
                        'image_url' => null,
                        'video_url' => null,
                    ];
                }
            }
            
            return $items;
            
        } catch (\Exception $e) {
            // External feeds are unreliable; treat as non-fatal signal.
            Log::warning("RSS parse warning for {$url}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get trending topics for country
     */
    public function getTrendingTopics(string $countryCode): array
    {
        $news = $this->fetchCountryNews($countryCode, 20);
        
        // Extract keywords from titles
        $keywords = [];
        foreach ($news as $article) {
            $words = explode(' ', strtolower($article['title']));
            foreach ($words as $word) {
                if (strlen($word) > 5) {
                    $keywords[] = $word;
                }
            }
        }
        
        // Count frequency
        $frequency = array_count_values($keywords);
        arsort($frequency);
        
        return array_slice(array_keys($frequency), 0, 10);
    }

    protected function extractRssMedia(\SimpleXMLElement $item): array
    {
        $imageUrl = null;
        $videoUrl = null;

        $enclosure = $item->enclosure;
        if ($enclosure && isset($enclosure['url'])) {
            $encUrl = (string) $enclosure['url'];
            $encType = strtolower((string) ($enclosure['type'] ?? ''));
            if (str_contains($encType, 'video') || preg_match('/\.(mp4|webm|mov|m3u8)(\?.*)?$/i', $encUrl)) {
                $videoUrl = $encUrl;
            } elseif (str_contains($encType, 'image')) {
                $imageUrl = $encUrl;
            }
        }

        $media = $item->children('media', true);
        if ($media) {
            if (!$imageUrl && isset($media->thumbnail)) {
                $attrs = $media->thumbnail->attributes();
                if (isset($attrs['url'])) {
                    $imageUrl = (string) $attrs['url'];
                }
            }

            if (isset($media->content)) {
                foreach ($media->content as $content) {
                    $attrs = $content->attributes();
                    $url = (string) ($attrs['url'] ?? '');
                    $type = strtolower((string) ($attrs['type'] ?? ''));
                    if (!$url) {
                        continue;
                    }
                    if (!$videoUrl && (str_contains($type, 'video') || preg_match('/\.(mp4|webm|mov|m3u8)(\?.*)?$/i', $url))) {
                        $videoUrl = $url;
                    }
                    if (!$imageUrl && (str_contains($type, 'image') || preg_match('/\.(jpg|jpeg|png|webp|gif)(\?.*)?$/i', $url))) {
                        $imageUrl = $url;
                    }
                }
            }
        }

        return [
            'image_url' => $imageUrl,
            'video_url' => $videoUrl,
        ];
    }

    protected function normalizePublishedAt(?string $raw): string
    {
        if (!$raw) {
            return now()->toDateTimeString();
        }

        try {
            return \Carbon\Carbon::parse($raw)->toDateTimeString();
        } catch (\Throwable $e) {
            return now()->toDateTimeString();
        }
    }

    protected function dedupeArticles(array $articles): array
    {
        $seen = [];
        $out = [];

        foreach ($articles as $item) {
            $titleKey = Str::lower(trim((string) ($item['title'] ?? '')));
            $urlKey = Str::lower(trim((string) ($item['url'] ?? '')));
            $key = sha1($titleKey . '|' . $urlKey);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $out[] = $item;
        }

        return $out;
    }
}
