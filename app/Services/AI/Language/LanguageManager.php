<?php

namespace App\Services\AI\Language;

use App\Models\AiAgent;
use App\Models\Post;
use Illuminate\Support\Facades\Log;

class LanguageManager
{
    protected $languages = [
        'India' => ['Hindi', 'English', 'Tamil', 'Telugu', 'Marathi', 'Bengali', 'Urdu'],
        'USA' => ['English', 'Spanish'],
        'UK' => ['English'],
        'China' => ['Mandarin', 'Cantonese'],
        'Japan' => ['Japanese'],
        'Korea' => ['Korean'],
        'France' => ['French', 'English'],
        'Germany' => ['German', 'English'],
        'Brazil' => ['Portuguese'],
        'Russia' => ['Russian'],
        'UAE' => ['Arabic', 'English'],
        'Saudi Arabia' => ['Arabic'],
        'Turkey' => ['Turkish'],
        'Iran' => ['Farsi'],
        'Kenya' => ['Swahili', 'English'],
    ];

    /**
     * Determine the output language for a post based on context
     */
    public function determineOutputLanguage(AiAgent $agent, array $context): string
    {
        // 1. If replying to a specific post/comment, match that language (Simulated detection)
        if (!empty($context['reply_to_language'])) {
            return $context['reply_to_language'];
        }

        $countryCode = strtoupper((string) $agent->country);
        $countryLanguages = array_values((array) config("countries.countries.{$countryCode}.languages", []));
        $available = [];
        foreach ($countryLanguages as $langCode) {
            $available[] = $this->normalizeLanguageLabel((string) $langCode);
        }
        $available = array_values(array_unique(array_filter($available)));

        // 2. Check for Global Impact News (Part 6 Integration)
        // If the topic is extremely global (e.g., War, Crypto, Tech), prefer English for reach
        if (!empty($context['global_impact_news'])) {
            // Keep global reach but don't force English all the time.
            if (rand(1, 100) <= 30) {
                return 'English';
            }
        }

        // 3. Check Agent's Native/Preferred Language
        $nativeLang = $this->normalizeLanguageLabel((string) ($agent->language ?? 'English'));

        // 4. Multilingual rotation for countries that have multiple configured languages.
        if (count($available) > 1) {
            $seed = abs(crc32($agent->id . '|' . now()->format('YmdH')));
            $idx = $seed % count($available);
            $rotated = (string) $available[$idx];

            // Mostly rotate, sometimes stick to native voice for consistency.
            if (rand(1, 100) <= 70) {
                return $rotated;
            }
        }

        return $nativeLang;
    }

    /**
     * Check if a post should be auto-translated (viral content)
     */
    public function shouldTranslate(Post $post): bool
    {
        // Translate if viral (> 100 likes or > 20 comments)
        if (($post->views_count ?? 0) > 500 || ($post->comments_count ?? 0) > 20) {
            return true;
        }

        // Translate if it's a "Breaking News" type post
        $content = strtolower((string) $post->content);
        if (str_contains($content, 'breaking:') || str_contains($content, 'alert:')) {
            return true;
        }

        return false;
    }

    /**
     * Generate translation prompt (for AI Provider)
     */
    public function getTranslationPrompt(string $content, string $targetLang): string
    {
        return "Task: Translate the following social media post into {$targetLang}. Keep the tone, emojis, and hashtags intact. \n\nPost: \"{$content}\"";
    }

    /**
     * Get primary languages for a country
     */
    public function getLanguagesForCountry(string $country): array
    {
        return $this->languages[$country] ?? ['English'];
    }

    protected function normalizeLanguageLabel(string $lang): string
    {
        $map = [
            'en-us' => 'English',
            'en-gb' => 'English',
            'en-in' => 'English',
            'en-pk' => 'English',
            'en-bd' => 'English',
            'en-ca' => 'English',
            'en-au' => 'English',
            'en-nz' => 'English',
            'en-ie' => 'English',
            'es-es' => 'Spanish',
            'es-mx' => 'Spanish',
            'es-ar' => 'Spanish',
            'es-cl' => 'Spanish',
            'es-co' => 'Spanish',
            'es-ve' => 'Spanish',
            'pt-br' => 'Portuguese',
            'pt-pt' => 'Portuguese',
            'fr-fr' => 'French',
            'fr-ca' => 'French',
            'de-de' => 'German',
            'it-it' => 'Italian',
            'ru-ru' => 'Russian',
            'ja-jp' => 'Japanese',
            'ko-kr' => 'Korean',
            'zh-cn' => 'Chinese',
            'ar-ae' => 'Arabic',
            'ar-sa' => 'Arabic',
            'ar-eg' => 'Arabic',
            'hi-in' => 'Hindi',
            'bn-bd' => 'Bengali',
            'ur-pk' => 'Urdu',
            'ms-my' => 'Malay',
            'id-id' => 'Indonesian',
            'th-th' => 'Thai',
            'vi-vn' => 'Vietnamese',
            'tr-tr' => 'Turkish',
            'uk-ua' => 'Ukrainian',
            'pl-pl' => 'Polish',
            'nl-nl' => 'Dutch',
            'sv-se' => 'Swedish',
            'no-no' => 'Norwegian',
            'da-dk' => 'Danish',
            'fi-fi' => 'Finnish',
            'el-gr' => 'Greek',
            'he-il' => 'Hebrew',
            'am-et' => 'Amharic',
            'sw-ke' => 'Swahili',
            'tl-ph' => 'Tagalog',
            'ms-my' => 'Malay',
            'id-id' => 'Indonesian',
            'he-il' => 'Hebrew',
            'da-dk' => 'Danish',
            'no-no' => 'Norwegian',
        ];

        $normalized = strtolower(trim($lang));
        return $map[$normalized] ?? ($lang ?: 'English');
    }
}
