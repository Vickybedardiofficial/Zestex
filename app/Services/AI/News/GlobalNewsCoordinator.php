<?php

namespace App\Services\AI\News;

use App\Models\AiAgent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GlobalNewsCoordinator
{
    protected CountryNewsAggregator $aggregator;

    public function __construct()
    {
        $this->aggregator = new CountryNewsAggregator();
    }

    /**
     * Get impactful global news for a specific country agent
     * This simulates cross-country targeting
     */
    public function getGlobalImpactNews(string $homeCountry): array
    {
        // Define key global players whose news affects others
        $globalPlayers = ['US', 'CN', 'RU', 'FR', 'DE', 'JP', 'IN', 'GB'];
        $impactNews = [];

        // Remove home country from source list
        $sources = array_diff($globalPlayers, [strtoupper($homeCountry)]);

        foreach ($sources as $sourceCountry) {
            $news = $this->fetchCategorizedNews($sourceCountry);
            
            foreach ($news as $category => $articles) {
                foreach ($articles as $article) {
                    // Simple heuristic: Does this news mention the home country?
                    // OR is it a major global category (International Relations)?
                    if ($this->isRelevantTo($article, $homeCountry, $category)) {
                        $article['global_context'] = "News from {$sourceCountry} affecting {$homeCountry}";
                        $article['category'] = $category;
                        $impactNews[] = $article;
                    }
                }
            }
        }

        return $impactNews;
    }

    /**
     * Fetch and categorize news from a country
     */
    public function fetchCategorizedNews(string $countryCode): array
    {
        return Cache::remember("categorized_news_{$countryCode}", 60, function() use ($countryCode) {
            $rawNews = $this->aggregator->fetchCountryNews($countryCode, 30);
            $categorized = [
                'government' => [],
                'corporate' => [],
                'international' => [],
                'social' => [],
                'general' => []
            ];

            foreach ($rawNews as $article) {
                $category = $this->categorizeArticle($article);
                $categorized[$category][] = $article;
            }

            return $categorized;
        });
    }

    /**
     * Categorize article based on keywords
     */
    protected function categorizeArticle(array $article): string
    {
        $text = strtolower($article['title'] . ' ' . $article['description']);

        // Government & Policy
        if (preg_match('/(govt|government|minister|law|bill|parliament|congress|senate|budget|tax|policy|election|vote|prime minister|president)/', $text)) {
            return 'government';
        }

        // Corporate & Business
        if (preg_match('/(stock|market|share|ceo|company|merger|acquisition|layoff|profit|revenue|bank|economy|inflation|trade|scandal|fraud|monopoly)/', $text)) {
            return 'corporate';
        }

        // International Relations
        if (preg_match('/(treaty|sanction|diplomat|foreign|border|war|conflict|summit|un|nato|g20|trade deal|agreement|relations)/', $text)) {
            return 'international';
        }

        // Social Movements
        if (preg_match('/(protest|rights|human rights|movement|activist|rally|march|campaign|justice|freedom|equality|climate)/', $text)) {
            return 'social';
        }

        return 'general';
    }

    /**
     * Check relevance of foreign news to home country
     */
    protected function isRelevantTo(array $article, string $homeCountry, string $category): bool
    {
        $text = strtolower($article['title'] . ' ' . $article['description']);
        
        // 1. Direct Mention
        // Map codes to names
        $countryNames = [
            'us' => ['usa', 'united states', 'america', 'us '],
            'in' => ['india', 'indian', 'delhi'],
            'uk' => ['uk', 'britain', 'london'],
            'cn' => ['china', 'chinese', 'beijing'],
            'ru' => ['russia', 'russian', 'moscow'],
            'jp' => ['japan', 'japanese', 'tokyo'],
            // Add more as needed
        ];

        $keywords = $countryNames[$homeCountry] ?? [$homeCountry];
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) return true;
        }

        // 2. High Impact International News (Always relevant)
        if ($category === 'international' && 
            (str_contains($text, 'war') || str_contains($text, 'crisis') || str_contains($text, 'global'))) {
            return true;
        }

        // 3. Major Corporate News (Global Economy)
        if ($category === 'corporate' && 
            (str_contains($text, 'crash') || str_contains($text, 'recession') || str_contains($text, 'oil'))) {
            return true;
        }

        return false;
    }
}
