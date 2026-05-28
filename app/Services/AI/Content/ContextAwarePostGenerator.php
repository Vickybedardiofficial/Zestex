<?php

namespace App\Services\AI\Content;

use App\Models\AiAgent;
use App\Models\Post;
use App\Services\AI\AIProviderManager;
use App\Services\AI\Events\SpecialEventsManager;
use App\Services\AI\Language\LanguageManager;
use App\Services\AI\Memory\AgentMemoryService;
use App\Services\AI\News\CountryNewsAggregator;
use App\Services\AI\News\GlobalNewsCoordinator;
use App\Services\AI\Prompts\AgentPromptTemplate;
use Illuminate\Support\Facades\Log;

class ContextAwarePostGenerator
{
    protected AIProviderManager $aiManager;
    protected PostGenerator $postGenerator;
    protected GlobalNewsCoordinator $globalCoordinator;
    protected CountryNewsAggregator $newsAggregator;
    protected AgentMemoryService $memoryService;
    protected TrollManager $trollManager;
    protected LanguageManager $languageManager;
    protected \App\Services\AI\Evolution\AgentEvolutionManager $evolutionManager;
    protected SpecialEventsManager $eventsManager;

    public function __construct()
    {
        $this->aiManager = new AIProviderManager();
        $this->postGenerator = new PostGenerator();
        $this->newsAggregator = new CountryNewsAggregator();
        $this->globalCoordinator = new GlobalNewsCoordinator();
        $this->memoryService = new AgentMemoryService();
        $this->trollManager = new TrollManager();
        $this->languageManager = new LanguageManager();
        $this->evolutionManager = new \App\Services\AI\Evolution\AgentEvolutionManager();
        $this->eventsManager = new SpecialEventsManager();
    }

    /**
     * Generate context-aware post
     */
    public function generatePost(AiAgent $agent, ?array $context = null): string
    {
        $baseContext = $this->buildContext($agent);
        if (!$context) {
            $context = $baseContext;
        } else {
            $context = array_merge($baseContext, $context);
        }

        $postAngle = $this->determinePostAngle($agent, $context);
        if (!empty($context['global_impact_news']) && rand(1, 100) < 40) {
            $postAngle = 'global_reaction';
        }

        $prompt = $this->buildPrompt($agent, $context, $postAngle);

        if ($agent->personality_type === 'troll' && !empty($context['latest_news'])) {
            $newsItem = $context['latest_news'][array_rand($context['latest_news'])];
            $trigger = $this->trollManager->detectTrigger($newsItem);
            if ($trigger) {
                $prompt = $this->trollManager->generateTrollPrompt($agent, $newsItem, $trigger);
            }
        }

        try {
            $generated = $this->postGenerator->generatePost([
                'prompt' => $prompt,
                'context' => $context,
            ], $agent->ai_provider);

            $rawContent = trim((string) ($generated['content'] ?? ''));
            $hashtagText = trim((string) ($generated['hashtags'] ?? ''));
            $post = $rawContent;
            if ($hashtagText !== '' && !str_contains($post, '#')) {
                $post .= "\n\n" . $hashtagText;
            }

            $postContent = $this->cleanPost($post, $context, $postAngle, $agent);

            if ($agent->personality_type === 'troll' && !$this->trollManager->isSafe($postContent)) {
                return $this->generateSimplePost($agent, $context, $postAngle);
            }

            return $postContent;
        } catch (\Exception $e) {
            Log::error('Context-aware post generation failed', ['error' => $e->getMessage()]);
            return $this->generateSimplePost($agent, $context, $postAngle);
        }
    }

    protected function buildContext(AiAgent $agent): array
    {
        $latestNews = $this->newsAggregator->fetchCountryNews($agent->country, 10);
        $realtimeNews = $this->newsAggregator->fetchCountryNewsRealtime($agent->country, 6);
        $localTopicPool = $this->buildLocalTopicPool($agent);
        $recentPlatformPosts = $this->getRecentPlatformPosts($agent, 2);
        $recentPlatformPostsLastHour = $this->getRecentPlatformPosts($agent, 1);

        if (!empty($realtimeNews)) {
            $latestNews = array_values(array_slice($this->mergeNewsByUrl($realtimeNews, $latestNews), 0, 12));
        }

        $context = [
            'time_of_day' => $this->getTimeOfDay(),
            'trending_topics' => $this->newsAggregator->getTrendingTopics($agent->country),
            'latest_news' => $latestNews,
            'realtime_news' => $realtimeNews,
            'selected_news' => $this->selectNewsItemForAgent($agent, $latestNews),
            'global_impact_news' => [],
            'recent_global_titles' => $this->getRecentGlobalTitles($agent),
            'recent_platform_posts' => $recentPlatformPosts,
            'recent_platform_posts_last_hour' => $recentPlatformPostsLastHour,
            'agent_memory' => $this->memoryService->getMemoryContext($agent),
            'personality' => $agent->personality_type,
            'topics' => $agent->topics ?? [],
            'local_topics' => $localTopicPool,
            'post_style' => $this->pickPostStyleHint($agent),
            'output_language' => '',
        ];

        $context['output_language'] = $this->languageManager->determineOutputLanguage($agent, $context);

        if (in_array($agent->personality_type, ['political', 'tech', 'finance'], true)) {
            $context['global_impact_news'] = $this->globalCoordinator->getGlobalImpactNews($agent->country);
        }

        return $context;
    }

    protected function determinePostAngle(AiAgent $agent, array $context): string
    {
        // Force 30-40% roast/troll angle when contradiction/hypocrisy signals exist.
        if ($this->hasHypocrisySignal($context) && rand(1, 100) <= 35) {
            return 'troll';
        }

        $weights = [
            'breaking' => 16,
            'analysis' => 18,
            'historical' => 10,
            'emotional' => 10,
            'data' => 12,
            'question' => 9,
            'reflection' => 8,
            'community' => 8,
            'forecast' => 6,
            'general' => 3,
        ];
        $weights = $this->applyPersonalityAngleBias($weights, (string) $agent->personality_type);

        if ($agent->personality_type === 'troll') {
            $weights['troll'] = 25;
            $weights['sarcastic'] = 10;
        }

        if (!empty($context['latest_news'])) {
            $weights['breaking'] += 8;
            $weights['analysis'] += 4;
        }
        if (!empty($context['focus_topic'])) {
            $weights['community'] += 4;
            $weights['forecast'] += 3;
        }
        if ($this->shouldRunAiTopicalMode($agent, $context)) {
            $weights['ai_insight'] = 22;
            $weights['question'] += 4;
            $weights['reflection'] += 3;
            $weights['breaking'] = max(6, (int) floor(($weights['breaking'] ?? 0) * 0.5));
        }

        $total = array_sum($weights);
        $rand = rand(1, $total);
        $cumulative = 0;

        foreach ($weights as $angle => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $angle;
            }
        }

        return 'general';
    }

    protected function hasHypocrisySignal(array $context): bool
    {
        $scan = json_encode([
            'news' => array_slice((array) ($context['latest_news'] ?? []), 0, 5),
            'trends' => array_slice((array) ($context['trending_topics'] ?? []), 0, 10),
            'posts' => array_slice((array) ($context['recent_platform_posts_last_hour'] ?? []), 0, 10),
        ], JSON_UNESCAPED_UNICODE);

        $text = mb_strtolower((string) $scan);
        $needles = ['hypocrisy', 'contradiction', 'flip-flop', 'u-turn', 'double standard', 'inconsistent'];
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build prompt for post generation
     */
    protected function buildPrompt(AiAgent $agent, array $context, string $postType): string
    {
        $outputLanguage = (string) ($context['output_language'] ?? $agent->language ?? 'English');
        $recentTitles = array_values(array_slice((array) ($context['recent_global_titles'] ?? []), 0, 30));
        $recentPosts = array_values(array_slice((array) ($context['recent_platform_posts'] ?? []), 0, 12));
        $recentPostsLastHour = array_values(array_slice((array) ($context['recent_platform_posts_last_hour'] ?? []), 0, 12));
        $realtimeNews = array_values(array_slice((array) ($context['realtime_news'] ?? []), 0, 5));

        $prompt = "You are a viral AI agent. Goal: maximize likes, comments, and shares.\n\n";
        $prompt .= "Strict output rules (mandatory):\n";
        $prompt .= "1) First line MUST be a hook (question/shock/emoji-led). No title line.\n";
        $prompt .= "2) Keep post concise and punchy (80-120 words). No long analysis blocks.\n";
        $prompt .= "3) Last line MUST be a question or CTA.\n";
        $prompt .= "4) Use 2-4 emojis from this set: 🔥 😂 🤡 👀 💀.\n";
        $prompt .= "5) Use 2-4 trend hashtags.\n";
        $prompt .= "6) No repetitive lines, no filler, no boring long analysis blocks.\n";
        $prompt .= "7) Never include media_desc or metadata text in content.\n";
        $prompt .= "8) Never start with these phrases: Fresh view, Quick signal check, This thread deserves a second look, Re-sharing with context, Worth amplifying, Signal update.\n";
        $prompt .= "9) Must reference at least one concrete point from real-time news (last 1-2 hours) or the last 2h platform trend.\n";
        $prompt .= "10) Return strict JSON only:\n";
        $prompt .= "{\"content\":\"...\",\"hashtags\":[\"Viral\",\"Trending\",\"BreakingNews\"]}\n\n";
        $prompt .= "Language: {$outputLanguage}\n";
        $prompt .= "Personality: {$agent->personality_type}\n";
        $prompt .= "Post type: {$postType}\n";
        $prompt .= "Avoid repeating these recent lines: " . json_encode($recentTitles, JSON_UNESCAPED_UNICODE) . "\n";
        $prompt .= "Recent platform posts (last 1 hour): " . json_encode($recentPostsLastHour, JSON_UNESCAPED_UNICODE) . "\n";
        $prompt .= "Recent platform posts (last 2 hours): " . json_encode($recentPosts, JSON_UNESCAPED_UNICODE) . "\n";
        $prompt .= "Real-time news (last 1-2 hours): " . json_encode($realtimeNews, JSON_UNESCAPED_UNICODE) . "\n";
        $prompt .= "Context: " . json_encode([
            'news' => array_slice((array) ($context['latest_news'] ?? []), 0, 4),
            'trends' => array_slice((array) ($context['trending_topics'] ?? []), 0, 8),
            'focus' => $context['focus_topic'] ?? $postType,
            'country' => $agent->country,
        ], JSON_UNESCAPED_UNICODE) . "\n";
        $prompt .= "Generate now.";

        return $prompt;
    }

    protected function getFormatInstruction(AiAgent $agent, string $outputLanguage): string
    {
        $template = new AgentPromptTemplate();
        // Since we can't easily access the protected personaMap here without changing visibility or duplicating, 
        // we'll rely on the agent's personality_type to switch.
        // Ideally AgentPromptTemplate should expose this, but for now we'll switch based on type.
        
        $type = $agent->personality_type;
        $commonRules = "Avoid markdown symbols like ** or ##. Use concrete details and avoid generic filler. Do not include private contact details.";
        
        if (in_array($type, ['political', 'finance', 'general'])) {
            // Strict / Formal
             return "Output format must be strict:\n"
            . "1) First line exactly: 🤖\n"
            . "2) Second line title must be 8-12 words\n"
            . "3) Body must be 200-400 words in 3-4 short paragraphs\n"
            . "4) End body with one engaging question\n"
            . "5) Add source line exactly: 📎 Source — [Publisher] [Full Article Link]\n"
            . "6) Last line must include 2-4 relevant hashtags\n"
            . "7) Write title and body only in {$outputLanguage}\n"
            . $commonRules;
        }

        if ($type === 'tech') {
            // Technical / List-heavy
            return "Output format:\n"
            . "1) First line exactly: 🤖\n"
            . "2) Second line: A crisp technical headline (max 10 words)\n"
            . "3) Body: Start with a brief context (1-2 sentences). Then provide a bulleted list (use '-') of 3 key takeaways or specs. End with an implication summary.\n"
            . "4) Add source line exactly: 📎 Source — [Publisher] [Full Article Link]\n"
            . "5) Last line: 3-5 tech-focused hashtags\n"
            . "6) Write in {$outputLanguage}\n"
            . $commonRules;
        }
        
        if ($type === 'troll') {
            // Short / Punchy / Sarcastic
             return "Output format:\n"
            . "1) First line exactly: 🤖\n"
            . "2) No formal title. Start directly with the hook.\n"
            . "3) Body: Short, punchy, sarcastic commentary. Max 2 short paragraphs. Be witty, not long-winded.\n"
            . "4) (Optional) Source line: 📎 Source — [Publisher] [Full Article Link] (include only if it helps the joke)\n"
            . "5) Last line: 2 ironic hashtags\n"
            . "6) Write in {$outputLanguage}\n"
            . $commonRules;
        }

        if (in_array($type, ['sports', 'entertainment'])) {
             // Casual / Expressive
             return "Output format:\n"
            . "1) First line exactly: 🤖\n"
            . "2) Headline: Punchy and emotional (exclams allowed!)\n"
            . "3) Body: High energy, can use emojis sparingly. 2-3 short paragraphs.\n"
            . "4) Add source line: 📎 Source — [Publisher] [Full Article Link]\n"
            . "5) Last line: 3-5 trending hashtags\n"
            . "6) Write in {$outputLanguage}\n"
            . $commonRules;
        }

        // Fallback
        return "Output format:\n"
            . "1) First line exactly: 🤖\n"
            . "2) Title: Clear and descriptive\n"
            . "3) Body: 3 paragraphs explaining the situation.\n"
            . "4) Source line: 📎 Source — [Publisher] [Full Article Link]\n"
            . "5) Hashtags: 3 relevant tags\n"
            . "6) Write in {$outputLanguage}\n"
            . $commonRules;
    }

    protected function pickPostStyleHint(AiAgent $agent): string
    {
        $styles = [
            'deep_dive',
            'benchmark',
            'threat_report',
            'architecture_review',
            'operational_playbook',
            'field_notes',
            'debate_prompt',
            'case_study',
            'systems_explainer',
            'community_ama',
        ];

        if ($agent->personality_type === 'sports') {
            $styles[] = 'match_breakdown';
        }
        if ($agent->personality_type === 'entertainment') {
            $styles[] = 'culture_commentary';
        }

        return $styles[array_rand($styles)];
    }

    protected function shouldRunAiTopicalMode(AiAgent $agent, array $context): bool
    {
        $topicHint = strtolower(implode(' ', (array) ($context['local_topics'] ?? [])));
        if (str_contains($topicHint, 'ai') || str_contains($topicHint, 'artificial intelligence')) {
            return rand(1, 100) <= 45;
        }

        // Ensure occasional AI-topic posts even when news feed is dominated by non-AI.
        return rand(1, 100) <= 20;
    }

    protected function buildLocalTopicPool(AiAgent $agent): array
    {
        $base = [];
        $base = array_merge($base, is_array($agent->topics) ? $agent->topics : []);
        $base = array_merge($base, [
            'ai',
            'artificial intelligence',
            'technology',
            'economy',
            'education',
            'health',
            'jobs',
            'policy',
            'sports',
            'entertainment',
            'climate',
            'science',
        ]);

        $base = array_values(array_unique(array_filter(array_map(function ($v) {
            return trim((string) $v);
        }, $base), fn ($v) => $v !== '')));

        shuffle($base);
        return array_slice($base, 0, 10);
    }

    protected function applyPersonalityAngleBias(array $weights, string $personality): array
    {
        switch ($personality) {
            case 'tech':
                $weights['data'] = ($weights['data'] ?? 0) + 8;
                $weights['forecast'] = ($weights['forecast'] ?? 0) + 6;
                break;
            case 'political':
                $weights['analysis'] = ($weights['analysis'] ?? 0) + 7;
                $weights['historical'] = ($weights['historical'] ?? 0) + 5;
                break;
            case 'sports':
                $weights['emotional'] = ($weights['emotional'] ?? 0) + 8;
                $weights['community'] = ($weights['community'] ?? 0) + 6;
                break;
            case 'entertainment':
                $weights['community'] = ($weights['community'] ?? 0) + 7;
                $weights['question'] = ($weights['question'] ?? 0) + 6;
                break;
            case 'troll':
                $weights['sarcastic'] = ($weights['sarcastic'] ?? 0) + 8;
                $weights['question'] = ($weights['question'] ?? 0) + 5;
                break;
            default:
                $weights['reflection'] = ($weights['reflection'] ?? 0) + 5;
                $weights['community'] = ($weights['community'] ?? 0) + 3;
                break;
        }

        return $weights;
    }

    protected function getRecentGlobalTitles(AiAgent $agent): array
    {
        $titles = Post::query()
            ->where('is_ai_generated', true)
            ->where('created_at', '>=', now()->subHours(10))
            ->where('user_id', '!=', $agent->user_id)
            ->latest('id')
            ->limit(30)
            ->pluck('content')
            ->map(function (string $content) {
                $lines = preg_split('/\r\n|\r|\n/', (string) $content) ?: [];
                foreach ($lines as $line) {
                    $line = trim((string) $line);
                    if (
                        $line === ''
                        || $line === '🤖'
                        || $line === 'ðŸ¤–'
                        || str_starts_with($line, '📎')
                        || str_starts_with($line, 'ðŸ“Ž')
                    ) {
                        continue;
                    }

                    return mb_substr($line, 0, 90);
                }

                return '';
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $titles;
    }

    protected function getRecentPlatformPosts(AiAgent $agent, int $hours = 2): array
    {
        $hours = max(1, min(6, $hours));

        return Post::query()
            ->where('status', 'active')
            ->where('created_at', '>=', now()->subHours($hours))
            ->where('user_id', '!=', $agent->user_id)
            ->latest('id')
            ->limit(20)
            ->pluck('content')
            ->map(function ($content) {
                $line = trim((string) strtok((string) $content, "\n"));
                return mb_substr($line, 0, 140);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Clean post content
     */
    protected function cleanPost(string $post, array $context = [], string $postType = 'general', ?AiAgent $agent = null): string
    {
        $decoded = json_decode($post, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $cleanJson = preg_replace('/^```json\s*|\s*```$/', '', $post);
            $decoded = json_decode($cleanJson, true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            $jsonBlock = $this->extractJsonBlock((string) $post);
            if ($jsonBlock !== null) {
                $decoded = json_decode($jsonBlock, true);
            }
        }

        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['content'])) {
            $hashtags = $this->normalizeHashtags($decoded['hashtags'] ?? '');
            $content = $this->stripMediaDescArtifacts((string) $decoded['content']);
            return $this->enforceReachStyle($content, $context, $postType, $agent, $hashtags);
        }

        $post = str_replace(['```json', '```', '**', '__', '#'], '', (string) $post);
        $post = trim($post);
        $post = $this->stripMediaDescArtifacts($post);
        $post = $this->scrubSensitiveTokens($post);
        $post = preg_replace('/\{\\s*\"content\"\\s*:\\s*\".*$/s', '', $post) ?? $post;
        $post = preg_replace('/\\{[^\\n]*\\}$/', '', $post) ?? $post;

        return $this->enforceReachStyle($post, $context, $postType, $agent, []);
    }

    protected function extractJsonBlock(string $raw): ?string
    {
        $start = strpos($raw, '{');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escape = false;
        $len = strlen($raw);
        for ($i = $start; $i < $len; $i++) {
            $ch = $raw[$i];
            if ($inString) {
                if ($escape) {
                    $escape = false;
                    continue;
                }
                if ($ch === '\\') {
                    $escape = true;
                    continue;
                }
                if ($ch === '"') {
                    $inString = false;
                }
                continue;
            }

            if ($ch === '"') {
                $inString = true;
                continue;
            }
            if ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($raw, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    protected function enforceReachStyle(string $content, array $context, string $postType, ?AiAgent $agent, array $candidateTags): string
    {
        $minBodyWords = 80;
        $maxBodyWords = 120;

        $content = trim((string) $content);
        $content = preg_replace('/\b(important hai|zyada logon tak pahunchna chahiye)\b/i', '', (string) $content) ?? $content;
        $content = preg_replace('/\b(Media|Original Post|Source)\s*:\s*.+$/mi', '', (string) $content) ?? $content;
        $content = $this->stripBoringPhrases($content);

        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $lines = array_values(array_filter(array_map(function ($line) {
            return trim((string) (preg_replace('/\s+/', ' ', (string) $line) ?? $line));
        }, $lines), fn ($line) => $line !== '' && !str_starts_with($line, '#')));

        $base = trim(implode(' ', $lines));
        if ($base === '') {
            $news = $this->pickBestNewsItem($context);
            $base = trim(((string) ($news['title'] ?? 'Breaking shift in key trend')) . '. ' . ((string) ($news['description'] ?? 'Market and policy signals are moving quickly.')));
        }

        $sentences = preg_split('/(?<=[\.\!\?])\s+/', $base) ?: [$base];
        $hookCore = trim((string) ($sentences[0] ?? 'Big shift alert'));
        $hook = $this->buildHookLine($hookCore, $postType, $context);
        $cta = $this->buildCtaLine($postType, $context);

        $bodySeed = trim(implode(' ', array_slice($sentences, 1)));
        if ($bodySeed === '') {
            $bodySeed = $base;
        }

        $bodyWords = array_values(array_filter(preg_split('/\s+/', $bodySeed) ?: []));
        if (count($bodyWords) > $maxBodyWords) {
            $bodyWords = array_slice($bodyWords, 0, $maxBodyWords);
        }
        if (count($bodyWords) < $minBodyWords) {
            $bodyWords = $this->padBodyWords($bodyWords, $context, $postType, $agent, $minBodyWords);
        }
        if (count($bodyWords) > $maxBodyWords) {
            $bodyWords = array_slice($bodyWords, 0, $maxBodyWords);
        }
        $body = trim(implode(' ', $bodyWords));
        $body = $this->removeRepeatedSentences($body, $context, $postType, $agent, $minBodyWords, $maxBodyWords);
        $body = $this->stripBoringPhrases($body);
        $body = $this->ensureRealtimeAnchor($body, $context, $postType, $agent, $maxBodyWords);

        $tags = $this->mergeTrendingHashtags($candidateTags, $context, $postType, $agent);

        return implode("\n\n", [
            $hook,
            $body,
            $cta,
            implode(' ', $tags),
        ]);
    }

    protected function buildHookLine(string $hookCore, string $postType, array $context): string
    {
        $clean = trim(preg_replace('/[^\p{L}\p{N}\s\?\!\-]/u', ' ', $hookCore) ?? $hookCore);
        $clean = preg_replace('/\b(Fresh view|Quick signal check|This thread deserves a second look|Re-?sharing with context|Worth amplifying|Signal update)\b/i', '', (string) $clean) ?? $clean;
        $clean = trim((string) preg_replace('/\s+/', ' ', (string) $clean));
        if ($clean === '') {
            $clean = ucfirst((string) ($context['focus_topic'] ?? $postType)) . ' is shifting fast';
        }

        $prefixes = ["\u{1F525}", "\u{1F440}", "\u{1F480}", "\u{1F602}", "\u{1F921}"];
        $prefix = $prefixes[array_rand($prefixes)];

        if (!preg_match('/[\?\!]\s*$/u', $clean)) {
            $clean .= '?';
        }

        return "{$prefix} {$clean}";
    }

    protected function buildCtaLine(string $postType, array $context): string
    {
        $topic = ucfirst((string) ($context['focus_topic'] ?? $postType));
        $ctas = [
            "What's your take on this {$topic} shift right now? \u{1F440}",
            "Agree or disagree - what signal are we missing? \u{1F525}",
            "Real trend or short hype burst? Drop your call. \u{1F480}",
            "One-line hot take: where does this go next? \u{1F921}",
            "If you had one counter-point with evidence, what is it? \u{1F440}",
            "Would you bet on this in the next 30 days, yes or no? \u{1F525}",
            "Which metric proves this is real and not noise? \u{1F480}",
            "Reply with one hard fact that supports your side. \u{1F921}",
        ];

        return $ctas[array_rand($ctas)];
    }

    protected function padBodyWords(array $bodyWords, array $context, string $postType, ?AiAgent $agent, int $targetMinWords = 80): array
    {
        $news = $this->pickBestNewsItem($context);
        $topic = (string) ($context['focus_topic'] ?? $postType);
        $country = (string) ($agent?->country ?? ($news['country'] ?? 'global'));
        $source = (string) ($news['source'] ?? 'live desk');

        $snippets = [
            "This {$topic} shift is now influencing decisions across {$country}.",
            "Execution quality and policy consistency matter more than loud headlines right now.",
            "Fresh reporting from {$source} suggests sentiment is moving quickly this hour.",
            "The strongest signal is whether delivery stays stable over the next few updates.",
            "Short spikes can fade fast if proof on ground does not match the narrative.",
            "Public response usually improves when updates are transparent and easy to verify.",
        ];

        $seed = abs(crc32((string) ($agent?->id ?? 0) . '|' . $topic . '|' . $country . '|' . now()->format('YmdH')));
        shuffle($snippets);
        $cursor = 0;

        while (count($bodyWords) < $targetMinWords && $cursor < count($snippets)) {
            $snippet = $snippets[($seed + $cursor) % count($snippets)];
            $cursor++;
            $snippetWords = preg_split('/\s+/', trim($snippet)) ?: [];
            foreach ($snippetWords as $word) {
                $bodyWords[] = $word;
                if (count($bodyWords) >= $targetMinWords) {
                    break;
                }
            }
        }

        return $bodyWords;
    }

    protected function removeRepeatedSentences(string $body, array $context, string $postType, ?AiAgent $agent, int $minWords = 80, int $maxWords = 120): string
    {
        $sentences = preg_split('/(?<=[\.\!\?])\s+/', trim($body)) ?: [];
        $seen = [];
        $unique = [];

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if ($sentence === '') {
                continue;
            }

            $normalized = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $sentence) ?? $sentence);
            $normalized = preg_replace('/\s+/', ' ', trim($normalized)) ?? trim($normalized);
            if ($normalized === '' || isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;
            $unique[] = $sentence;
        }

        $result = trim(implode(' ', $unique));
        $words = array_values(array_filter(preg_split('/\s+/', $result) ?: []));
        if (count($words) >= $minWords) {
            return $result;
        }

        $words = $this->padBodyWords($words, $context, $postType, $agent, $minWords);
        if (count($words) > $maxWords) {
            $words = array_slice($words, 0, $maxWords);
        }

        return trim(implode(' ', $words));
    }

    protected function stripBoringPhrases(string $text): string
    {
        $blocked = [
            'fresh view',
            'quick signal check',
            'this thread deserves a second look',
            're-sharing with context',
            'resharing with context',
            'worth amplifying',
            'signal update',
            'big update',
        ];

        $clean = (string) $text;
        foreach ($blocked as $phrase) {
            $clean = preg_replace('/\b' . preg_quote($phrase, '/') . '\b/i', '', $clean) ?? $clean;
        }

        return trim((string) (preg_replace('/\s+/', ' ', $clean) ?? $clean));
    }

    protected function ensureRealtimeAnchor(string $body, array $context, string $postType, ?AiAgent $agent, int $maxWords = 120): string
    {
        $news = $this->pickBestNewsItem($context);
        $title = trim((string) ($news['title'] ?? ''));
        $source = trim((string) ($news['source'] ?? 'live desk'));

        if ($title === '') {
            return $body;
        }

        $keywords = array_values(array_filter(
            preg_split('/\s+/', preg_replace('/[^A-Za-z0-9 ]/', ' ', strtolower($title)) ?: '') ?: [],
            fn ($w) => strlen((string) $w) >= 5
        ));

        $lowerBody = strtolower($body);
        foreach (array_slice($keywords, 0, 4) as $word) {
            if (str_contains($lowerBody, strtolower($word))) {
                return $body;
            }
        }

        $topic = (string) ($context['focus_topic'] ?? $postType);
        $anchor = "Live feed from {$source} shows this {$topic} signal accelerating right now.";
        $combined = trim($body . ' ' . $anchor);
        $words = array_values(array_filter(preg_split('/\s+/', $combined) ?: []));
        if (count($words) > $maxWords) {
            $words = array_slice($words, 0, $maxWords);
        }

        return trim(implode(' ', $words));
    }

    protected function normalizeHashtags(mixed $raw): array
    {
        $input = [];

        if (is_string($raw)) {
            $input = preg_split('/[\s,]+/', trim($raw)) ?: [];
        } elseif (is_array($raw)) {
            $input = $raw;
        } else {
            return [];
        }

        $tags = [];
        foreach ($input as $tag) {
            $clean = preg_replace('/[^A-Za-z0-9_]/', '', ltrim((string) $tag, '#'));
            if ($clean !== '') {
                $tags[] = '#' . $clean;
            }
        }

        return array_values(array_unique($tags));
    }

    protected function stripMediaDescArtifacts(string $text): string
    {
        $clean = preg_replace('/^\s*media_desc\s*:\s*.*$/im', '', $text) ?? $text;
        $clean = preg_replace('/"media_desc"\s*:\s*"[^"]*"\s*,?/i', '', $clean) ?? $clean;
        return trim($clean);
    }

    protected function mergeNewsByUrl(array $primary, array $secondary): array
    {
        $merged = [];
        $seen = [];

        foreach (array_merge($primary, $secondary) as $item) {
            $url = trim((string) ($item['url'] ?? ''));
            $key = $url !== '' ? strtolower($url) : md5(json_encode($item));
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $merged[] = $item;
        }

        return $merged;
    }

    protected function mergeTrendingHashtags(array $candidateTags, array $context, string $postType, ?AiAgent $agent): array
    {
        $tags = $candidateTags;
        $tags[] = '#Viral';
        $tags[] = '#Trending';
        $tags[] = '#BreakingNews';
        $tags[] = '#' . preg_replace('/[^A-Za-z0-9_]/', '', ucfirst($postType));

        foreach ((array) ($context['trending_topics'] ?? []) as $topic) {
            $clean = preg_replace('/[^A-Za-z0-9_]/', '', (string) $topic);
            if ($clean !== '') {
                $tags[] = '#' . ucfirst($clean);
            }
            if (count(array_unique($tags)) >= 6) {
                break;
            }
        }

        if ($agent?->country) {
            $tags[] = '#' . preg_replace('/[^A-Za-z0-9_]/', '', (string) $agent->country);
        }

        $tags = array_values(array_unique(array_filter($tags)));
        return array_slice($tags, 0, 4);
    }

    protected function ensureStructuredPost(string $content, array $context = [], string $postType = 'general', ?AiAgent $agent = null): string
    {
        $raw = trim($content);
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), fn ($line) => $line !== ''));

        // If the LLM followed instructions, it should start with 🤖.
        // If not, we might need to prepend it or just accept it as is if it's close enough.
        $hasRobotError = false;
        if (empty($lines) || $lines[0] !== '🤖') {
             // Try to find 🤖
             $found = false;
             foreach($lines as $k => $l) {
                 if ($l === '🤖') {
                     // slice from here
                     $lines = array_slice($lines, $k);
                     $found = true;
                     break;
                 }
             }
             if (!$found) {
                 array_unshift($lines, '🤖');
             }
        }

        $news = $this->pickBestNewsItem($context);
        $source = (string) ($news['source'] ?? 'Latest global feed');
        $url = (string) ($news['url'] ?? ($news['link'] ?? '#'));
        $sourceLine = "📎 Source — {$source} [{$url}]";

        // Extract parts simply by looking for the robot and source/hashtags
        // We no longer enforce "Line 2 is title". Title might be missing for trolls.
        
        $bodyLines = [];
        $title = '';
        $hashtagsLine = '';
        $foundSource = false;
        
        // Scan lines
        for ($i = 1; $i < count($lines); $i++) {
            $line = $lines[$i];
            
            // Checks for specific markers
            if (str_starts_with($line, '📎 Source')) {
                 $foundSource = true;
                 // We can use our generated source line instead of the AI's if we want to ensure valid links
                 continue; 
            }
            if (str_starts_with($line, '#')) {
                // likely hashtags
                $hashtagsLine = $line;
                continue;
            }

            // Heuristic for Title:
            // If it's the first text line, short (< 15 words) and no punctuation at end? Likely title.
            // But trolls might skip title.
            if ($title === '' && empty($bodyLines) && str_word_count($line) < 15 && !preg_match('/[.\?!]$/', $line) && $agent?->personality_type !== 'troll') {
                 $title = $line;
                 continue;
            }
            
            $bodyLines[] = $line;
        }

        // Reconstruct
        $bodyText = implode("\n\n", $bodyLines);
        
        // If body is empty (failure), use fallback
        if (empty($bodyText) || mb_strlen($bodyText) < 50) {
             // Determine if we really need fallback or if it's just a short troll post
             if ($agent?->personality_type !== 'troll' || mb_strlen($bodyText) < 10) {
                // Reuse existing normalization for text length/quality if needed, but 
                // for now we trust the new prompt to generate better content.
                // If extremely broken, we fail over to the old robust normalizer:
                return $this->ensureStructuredPostLegacy($content, $context, $postType, $agent);
             }
        }

        // Ensure hashtags
        if (empty($hashtagsLine)) {
            $hashtags = $this->buildHashtags($agent, $postType, $context);
            $hashtagsLine = implode(' ', $hashtags);
        }

        $finalParts = ['🤖'];
        if ($title) $finalParts[] = $title;
        $finalParts[] = $bodyText;
        if ($agent?->personality_type !== 'troll' || $foundSource) {
             $finalParts[] = $sourceLine;
        }
        $finalParts[] = $hashtagsLine;

        return implode("\n\n", $finalParts);
    }
    
    // Kept as backup for severe failures
    protected function ensureStructuredPostLegacy(string $content, array $context = [], string $postType = 'general', ?AiAgent $agent = null): string
    {
        $raw = trim($content);
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), fn ($line) => $line !== ''));

        $news = $this->pickBestNewsItem($context);
        $source = (string) ($news['source'] ?? 'Latest global feed');
        $url = (string) ($news['url'] ?? ($news['link'] ?? '#'));

        $title = $this->extractTitle($lines);
        $title = $this->normalizeTitle($title);

        $bodyText = $this->extractBody($lines);
        $bodyText = $this->normalizeBody($bodyText, $context, $postType, $agent);
        if (!str_ends_with(trim($bodyText), '?')) {
            $bodyText = rtrim($bodyText, ". \t\n\r\0\x0B") . '?';
        }

        $hashtags = $this->buildHashtags($agent, $postType, $context);
        $sourceLine = "📎 Source — {$source} [{$url}]";

        return implode("\n\n", [
            '🤖',
            $title,
            $bodyText,
            $sourceLine,
        ]);
    }

    protected function extractTitle(array $lines): string
    {
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || $line === '🤖') {
                continue;
            }
            if (str_starts_with($line, '📎') || str_starts_with($line, '#')) {
                continue;
            }

            return $line;
        }

        return 'Latest update with major impact across current affairs landscape';
    }

    protected function extractBody(array $lines): string
    {
        $chunks = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || $line === '🤖') {
                continue;
            }
            if (str_starts_with($line, '📎') || str_starts_with($line, '#')) {
                continue;
            }
            $chunks[] = $line;
        }

        if (!empty($chunks)) {
            array_shift($chunks);
        }

        return trim(implode(' ', $chunks));
    }

    protected function normalizeTitle(string $title): string
    {
        $min = (int) config('agent-creation.post_format.title_min_words', 8);
        $max = (int) config('agent-creation.post_format.title_max_words', 12);
        $words = preg_split('/\s+/', trim($title)) ?: [];
        $words = array_values(array_filter($words, fn ($w) => $w !== ''));

        if (count($words) > $max) {
            $words = array_slice($words, 0, $max);
        }

        while (count($words) < $min) {
            $words[] = 'update';
        }

        return trim(implode(' ', $words));
    }

    protected function normalizeBody(string $body, array $context, string $postType, ?AiAgent $agent = null): string
    {
        $min = (int) config('agent-creation.post_format.body_min_words', 200);
        $max = (int) config('agent-creation.post_format.body_max_words', 400);

        $news = $this->pickBestNewsItem($context);
        $title = (string) ($news['title'] ?? 'A major development has emerged');
        $desc = (string) ($news['description'] ?? 'The story is developing and public impact is being assessed.');
        $source = (string) ($news['source'] ?? 'Global desk');
        $topic = ucfirst($postType);
        $focusTopic = (string) ($context['focus_topic'] ?? ($agent?->personality_type ?? 'general'));

        if ($body === '' || mb_strlen($body) < 220) {
            $body = $this->buildFallbackBody($title, $desc, $source, $topic, $agent, $focusTopic);
        }

        $baseWords = preg_split('/\s+/', trim($body)) ?: [];
        $baseWords = array_values(array_filter($baseWords, fn ($w) => $w !== ''));

        if (count($baseWords) > $max) {
            $baseWords = array_slice($baseWords, 0, $max);
        }

        $fillerPool = $this->buildFillerPool($topic, $focusTopic, $source, $title);
        $fillerIdx = 0;
        while (count($baseWords) < $min) {
            $filler = $fillerPool[$fillerIdx % count($fillerPool)];
            $fillerIdx++;
            $fillerWords = preg_split('/\s+/', trim($filler)) ?: [];
            $baseWords = array_merge($baseWords, $fillerWords);
        }

        if (count($baseWords) > $max) {
            $baseWords = array_slice($baseWords, 0, $max);
        }

        $normalized = implode(' ', $baseWords);
        $segments = [];
        $segmentWords = max(35, (int) floor(count($baseWords) / 4));
        for ($i = 0; $i < 4; $i++) {
            $slice = array_slice($baseWords, $i * $segmentWords, $segmentWords);
            if (!empty($slice)) {
                $segments[] = implode(' ', $slice);
            }
        }

        if (empty($segments)) {
            $segments[] = $normalized;
        }

        return implode("\n\n", $segments);
    }

    protected function buildFillerPool(string $topic, string $focusTopic, string $source, string $title): array
    {
        return [
            "This {$topic} cycle is also shaping behavior in {$focusTopic}, where execution speed now matters more than headline promises.",
            "A key watchpoint is whether institutions publish measurable milestones and follow through on delivery timelines this month.",
            "Public trust usually improves only when claims are matched by transparent data and independent verification from reliable outlets.",
            "Stakeholders are likely to compare this with prior phases to judge whether the present shift is structural or only temporary.",
            "In the near term, policy clarity, budget alignment, and service quality will determine whether confidence stabilizes or weakens.",
            "The story titled '{$title}' indicates that communication quality is now as important as decision quality for public acceptance.",
            "Source context from {$source} should be combined with additional reporting before drawing strong conclusions on long-term outcomes.",
            "A practical takeaway is to track what changes on ground over the next 7 to 30 days rather than only reacting to narratives.",
        ];
    }

    protected function buildHashtags(?AiAgent $agent, string $postType, array $context): array
    {
        $min = (int) config('agent-creation.post_format.hashtags_min', 2);
        $max = (int) config('agent-creation.post_format.hashtags_max', 4);

        $tags = [];
        $tags[] = '#' . preg_replace('/[^A-Za-z0-9_]/', '', (string) ($agent?->country ?? 'World'));
        $tags[] = '#' . preg_replace('/[^A-Za-z0-9_]/', '', ucfirst($postType));

        foreach (($context['trending_topics'] ?? []) as $topic) {
            $clean = preg_replace('/[^A-Za-z0-9_]/', '', (string) $topic);
            if ($clean !== '') {
                $tags[] = '#' . ucfirst($clean);
            }
            if (count($tags) >= $max) {
                break;
            }
        }

        $tags = array_values(array_unique($tags));
        while (count($tags) < $min) {
            $tags[] = '#AIUpdates';
        }

        return array_slice($tags, 0, $max);
    }

    protected function pickBestNewsItem(array $context): array
    {
        if (!empty($context['selected_news']) && is_array($context['selected_news'])) {
            return $context['selected_news'];
        }

        $latest = $context['latest_news'] ?? [];
        if (empty($latest)) {
            return [];
        }

        usort($latest, function ($a, $b) {
            $aTs = strtotime((string) ($a['published_at'] ?? 'now')) ?: 0;
            $bTs = strtotime((string) ($b['published_at'] ?? 'now')) ?: 0;
            if ($aTs === $bTs) {
                $aMedia = (!empty($a['video_url']) ? 2 : 0) + (!empty($a['image_url']) ? 1 : 0);
                $bMedia = (!empty($b['video_url']) ? 2 : 0) + (!empty($b['image_url']) ? 1 : 0);
                return $bMedia <=> $aMedia;
            }
            return $bTs <=> $aTs;
        });

        return $latest[0] ?? [];
    }

    protected function selectNewsItemForAgent(AiAgent $agent, array $latestNews): array
    {
        if (empty($latestNews)) {
            return [];
        }

        // Keep "latest-first" but distribute top stories across agents to reduce same-post clusters.
        usort($latestNews, function ($a, $b) {
            $aTs = strtotime((string) ($a['published_at'] ?? 'now')) ?: 0;
            $bTs = strtotime((string) ($b['published_at'] ?? 'now')) ?: 0;
            return $bTs <=> $aTs;
        });

        $topPool = array_slice($latestNews, 0, min(5, count($latestNews)));
        $seed = abs(crc32($agent->id . '|' . now()->format('YmdH')));
        $idx = $seed % count($topPool);

        return $topPool[$idx] ?? ($latestNews[0] ?? []);
    }

    /**
     * Generate simple fallback post
     */
    protected function generateSimplePost(AiAgent $agent, array $context = [], string $postType = 'general'): string
    {
        $news = $this->pickBestNewsItem($context);
        $title = (string) ($news['title'] ?? 'A major development has emerged');
        $desc = (string) ($news['description'] ?? 'The story is developing and public impact is being assessed.');
        $fallback = trim($title . '. ' . $desc);
        return $this->enforceReachStyle($fallback, $context, $postType, $agent, []);
    }

    protected function buildFallbackBody(string $title, string $desc, string $source, string $topic, ?AiAgent $agent = null, ?string $focusTopic = null): string
    {
        $lens = $this->buildAgentLensSentence($agent, $title);
        $focusTopic = $focusTopic ?: 'public affairs';
        $variant = abs(crc32(($agent?->id ?? 0) . '|' . $title . '|' . now()->format('YmdH')));

        $personaLine = match ((string) ($agent?->personality_type ?? 'general')) {
            'tech' => 'I am prioritizing technological feasibility and systems impact.',
            'political' => 'I am prioritizing policy credibility and institutional accountability.',
            'sports' => 'I am prioritizing momentum shifts and high-pressure performance signals.',
            'entertainment' => 'I am prioritizing audience sentiment and narrative influence.',
            'troll' => 'I am prioritizing contradiction checks and accountability signals.',
            default => 'I am prioritizing practical impact and evidence quality.',
        };

        if ($variant % 5 === 0) {
            $p1 = "{$title}. {$desc} This now sits at the center of {$topic} debate and is directly tied to how {$focusTopic} decisions are prioritized this week.";
            $p2 = "Behind the headline, a longer chain is visible: earlier warnings, delayed execution, and mixed messaging increased uncertainty before this spike in attention.";
            $p3 = "For everyday users, the immediate effect is practical, not abstract: costs, timelines, service quality, and trust signals can all shift within days.";
            $p4 = "A prior cycle showed that course-correction is possible when institutions publish transparent milestones and independent verification. {$source} reporting gives a baseline. {$lens} {$personaLine}";
        } elseif ($variant % 5 === 1) {
            $p1 = "{$title}. {$desc} The core question now is whether leaders can convert promises into measurable outcomes for {$focusTopic} stakeholders.";
            $p2 = "Context matters: this pattern has repeated before, where short-term narratives moved faster than ground reality and left a credibility gap.";
            $p3 = "The next phase will likely be judged through execution indicators such as delivery speed, policy consistency, and visible accountability across agencies.";
            $p4 = "Historically, trust recovered only after regular public updates and independent scrutiny. {$source} provides useful reference points to track that transition. {$lens} {$personaLine}";
        } elseif ($variant % 5 === 2) {
            $p1 = "{$title}. {$desc} Beyond the headline momentum, this is now a test of governance quality in {$focusTopic} and the broader {$topic} ecosystem.";
            $p2 = "The background suggests accumulated pressure rather than a single trigger: unresolved bottlenecks and fragmented communication shaped the present outcome.";
            $p3 = "In practical terms, people should watch for policy follow-through, budget clarity, and whether frontline impact aligns with official claims over the next month.";
            $p4 = "Comparable phases in the past stabilized only when evidence replaced rhetoric and timelines were audited publicly. {$source} context helps map that trajectory. {$lens} {$personaLine}";
        } elseif ($variant % 5 === 3) {
            $p1 = "{$title}. {$desc} The immediate concern is how fast decisions convert into visible change for communities linked to {$focusTopic}.";
            $p2 = "Recent cycles suggest that unclear ownership and delayed communication create secondary risks, even when initial intent is constructive.";
            $p3 = "Watch for measurable checkpoints, not only statements: implementation milestones, funding clarity, and independent status validation.";
            $p4 = "{$source} updates should be cross-read with additional outlets to confirm trend direction. {$lens} {$personaLine}";
        } else {
            $p1 = "{$title}. {$desc} The key divide now is between narrative momentum and delivery reality in {$topic} and {$focusTopic}.";
            $p2 = "When institutions fail to align message, timeline, and metrics, public confidence usually degrades faster than expected.";
            $p3 = "Short-term volatility is likely, but sustained outcomes depend on execution discipline, transparent reporting, and policy consistency.";
            $p4 = "Historical comparisons suggest stabilization is possible once evidence quality improves and accountability cycles become routine. {$source} helps establish baseline context. {$lens} {$personaLine}";
        }

        return implode("\n\n", [$p1, $p2, $p3, $p4]);
    }

    protected function buildAgentLensSentence(?AiAgent $agent, string $seed): string
    {
        if (!$agent) {
            return 'A distinct analytical lens is required to avoid narrative repetition.';
        }

        $variants = [
            'I am tracking this through a policy-first lens focused on measurable outcomes.',
            'My current lens prioritizes implementation quality over headline-level reactions.',
            'I am evaluating this by consistency between public claims and real execution.',
            'My angle here is institutional accountability and timeline credibility.',
            'I am reading this through downstream impact on workers, markets, and services.',
            'My perspective is centered on risk exposure if follow-through remains weak.',
            'I am comparing this with prior cycles to test whether this shift is structural.',
            'My lens prioritizes public trust signals over short-term narrative wins.',
        ];

        $hash = abs(crc32($agent->id . '|' . $agent->country . '|' . $seed));
        $idx = $hash % count($variants);

        return $variants[$idx];
    }

    protected function scrubSensitiveTokens(string $text): string
    {
        $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[redacted-email]', $text) ?? $text;
        $text = preg_replace('/\+?\d[\d\s\-\(\)]{7,}\d/', '[redacted-phone]', $text) ?? $text;
        $text = preg_replace('/\b(address|street|zip code|postal code|house number)\b/i', 'location detail', $text) ?? $text;
        return $text;
    }

    /**
     * Get time of day
     */
    protected function getTimeOfDay(): string
    {
        $hour = now()->hour;

        if ($hour >= 5 && $hour < 12) {
            return 'morning';
        }
        if ($hour >= 12 && $hour < 17) {
            return 'afternoon';
        }
        if ($hour >= 17 && $hour < 21) {
            return 'evening';
        }

        return 'night';
    }

    /**
     * Generate a reply to a user comment
     */
    public function generateReply(AiAgent $agent, $comment): string
    {
        $context = $this->buildContext($agent);
        $userComment = (string) $comment->content;
        $userName = (string) $comment->user->name;

        $systemProfile = (new AgentPromptTemplate())->build($agent, $context);
        $prompt = $systemProfile . "\n\n";
        $prompt .= "A real user named '{$userName}' commented on your post: \"{$userComment}\". ";
        $prompt .= 'If they disagree, counter politely. If they agree, acknowledge and add one point. If they ask a question, answer directly. ';
        $prompt .= "Keep under 280 chars in {$context['output_language']}.";

        try {
            $reply = $this->aiManager->generateText($prompt, $agent->ai_provider, [
                'temperature' => 0.9,
                'max_tokens' => 150,
            ]);

            return trim($reply);
        } catch (\Exception $e) {
            Log::error('Reply generation failed', ['error' => $e->getMessage()]);
            return 'Interesting point. What do others think?';
        }
    }
}


