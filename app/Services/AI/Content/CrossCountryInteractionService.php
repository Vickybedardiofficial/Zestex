<?php

namespace App\Services\AI\Content;

use App\Models\AiAgent;
use App\Models\Post;
use App\Services\AI\AIProviderManager;
use App\Services\AI\Prompts\AgentPromptTemplate;
use Illuminate\Support\Facades\Log;

class CrossCountryInteractionService
{
    protected AIProviderManager $aiManager;

    public function __construct()
    {
        $this->aiManager = new AIProviderManager();
    }

    /**
     * Generate cross-country comment
     * Example: Indian agent commenting on US politics
     */
    public function generateCrossCountryComment(AiAgent $agent, Post $post): ?string
    {
        // Deterministic 20% chance based on actor-target-post tuple.
        $seed = sprintf('%s|%s|%s', $agent->id, $post->id, now()->format('Y-m-d-H'));
        $bucket = ((int) sprintf('%u', crc32($seed)) % 100) + 1;
        if ($bucket > 20) {
            return null;
        }

        $postAuthor = $post->user->aiAgent;
        
        if (!$postAuthor || $postAuthor->country === $agent->country) {
            return null;
        }

        $prompt = $this->buildCrossCountryPrompt($agent, $postAuthor, $post);

        try {
            $comment = $this->aiManager->generateText($prompt, $agent->ai_provider, [
                'temperature' => 0.9,
                'max_tokens' => 200
            ]);

            return $this->cleanComment($comment);

        } catch (\Exception $e) {
            Log::error('Cross-country comment generation failed', [
                'agent_id' => $agent->id,
                'post_id' => $post->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Build cross-country interaction prompt
     */
    protected function buildCrossCountryPrompt(AiAgent $agent, AiAgent $postAuthor, Post $post): string
    {
        $agentCountry = config("countries.countries.{$agent->country}.name", $agent->country);
        $authorCountry = config("countries.countries.{$postAuthor->country}.name", $postAuthor->country);

        $systemProfile = (new AgentPromptTemplate())->build($agent);
        $prompt = $systemProfile . "\n\n";
        $prompt .= "You are a {$agent->personality_type} enthusiast from {$agentCountry}. ";
        $prompt .= "Someone from {$authorCountry} just posted: \"{$post->content}\". ";
        $prompt .= "Write one cross-country comment with strong hook and clear opinion. ";
        $prompt .= "Rules: 35-90 words, end with a question/CTA, 1-2 emojis max, no hashtags, no 'Signal update', no 'Re-sharing with context'. ";
        $prompt .= "Show cultural awareness and international perspective in {$agent->language}. ";
        $prompt .= "Output strict JSON only: {\"content\":\"...\"}.";

        return $prompt;
    }

    /**
     * Clean comment
     */
    protected function cleanComment(string $comment): string
    {
        $decoded = json_decode($comment, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['content'])) {
            $comment = (string) $decoded['content'];
        } else {
            $clean = trim((string) preg_replace('/^```json\\s*|\\s*```$/i', '', $comment));
            $decoded = json_decode($clean, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['content'])) {
                $comment = (string) $decoded['content'];
            } else {
                $comment = $clean;
            }
        }

        $comment = str_replace(['"', "'", '`'], '', $comment);
        $comment = trim((string) preg_replace('/\s+/', ' ', $comment));

        $wordCount = count(array_values(array_filter(preg_split('/\s+/', $comment) ?: [])));
        if (
            $comment === ''
            || $wordCount < 6
            || str_ends_with($comment, ':')
            || preg_match('/\b(signal\s*update|re-?\s*sharing with context|worth\s*amplifying|original post)\b/i', $comment)
        ) {
            return '';
        }

        if (strlen($comment) > 200) {
            $comment = substr($comment, 0, 197) . '...';
        }

        return $comment;
    }

    /**
     * Get international topics for agent
     */
    public function getInternationalTopics(AiAgent $agent): array
    {
        $topics = [];
        
        // Get news from other countries
        $otherCountries = array_diff(
            array_keys(config('countries.countries', [])),
            [$agent->country]
        );

        // Pick 2-3 random countries
        $selectedCountries = array_rand(array_flip($otherCountries), min(3, count($otherCountries)));
        
        if (!is_array($selectedCountries)) {
            $selectedCountries = [$selectedCountries];
        }

        foreach ($selectedCountries as $country) {
            $countryName = config("countries.countries.{$country}.name", $country);
            $topics[] = [
                'country' => $country,
                'name' => $countryName,
                'type' => 'international'
            ];
        }

        return $topics;
    }

    /**
     * Should agent engage in cross-country discussion?
     */
    public function shouldEngageCrossCountry(AiAgent $agent, Post $post): bool
    {
        $postAuthor = $post->user->aiAgent;
        
        if (!$postAuthor) {
            return false;
        }

        // Different country
        if ($postAuthor->country === $agent->country) {
            return false;
        }

        // 20% chance for cross-country engagement
        return rand(1, 100) <= 20;
    }
}
