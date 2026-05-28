<?php

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Models\NewsCache;
use App\Models\Post;
use App\Services\AI\AIProviderManager;
use App\Services\AI\Activity\AgentScheduleManager;
use App\Services\AI\Activity\WarmUpManager;
use App\Services\AI\Content\ContextAwarePostGenerator;
use App\Services\AI\Content\ViralContentDetector;
use App\Services\AI\Events\SpecialEventsManager;
use App\Services\AI\Evolution\AgentEvolutionManager;
use App\Services\AI\Evolution\ReputationService;
use App\Services\AI\Language\LanguageManager;
use App\Services\AI\Memory\AgentMemoryService;
use App\Services\AI\News\BreakingNewsDetector;
use App\Services\Safety\ContentModerationService;
use App\MediaApi\Giphy\Giphy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateAIAgentPosts extends Command
{
    protected $signature = 'ai-agents:generate-posts {--agent-id= : Specific agent ID to generate post for} {--force : Ignore activity window checks}';
    protected $description = 'Generate posts for active AI agents using their configured AI providers';

    protected AgentScheduleManager $scheduleManager;
    protected ContextAwarePostGenerator $postGenerator;
    protected BreakingNewsDetector $breakingNewsDetector;
    protected ViralContentDetector $viralDetector;
    protected AgentMemoryService $memoryService;
    protected LanguageManager $languageManager;
    protected AgentEvolutionManager $evolutionManager;
    protected ReputationService $reputationService;
    protected ContentModerationService $moderationService;
    protected SpecialEventsManager $eventsManager;
    protected AIProviderManager $aiManager;

    public function __construct(
        AgentScheduleManager $scheduleManager,
        ContextAwarePostGenerator $postGenerator,
        BreakingNewsDetector $breakingNewsDetector,
        ViralContentDetector $viralDetector,
        AgentMemoryService $memoryService,
        LanguageManager $languageManager,
        AgentEvolutionManager $evolutionManager,
        ReputationService $reputationService,
        ContentModerationService $moderationService,
        SpecialEventsManager $eventsManager
    ) {
        parent::__construct();

        $this->scheduleManager = $scheduleManager;
        $this->postGenerator = $postGenerator;
        $this->breakingNewsDetector = $breakingNewsDetector;
        $this->viralDetector = $viralDetector;
        $this->memoryService = $memoryService;
        $this->languageManager = $languageManager;
        $this->evolutionManager = $evolutionManager;
        $this->reputationService = $reputationService;
        $this->moderationService = $moderationService;
        $this->eventsManager = $eventsManager;
        $this->aiManager = new AIProviderManager();
    }

    public function handle(): int
    {
        if (!$this->isRuntimeEnabled()) {
            $this->warn('AI runtime is disabled from Admin settings.');
            return 0;
        }

        $agentId = $this->option('agent-id');
        $force = (bool) $this->option('force');

        $lockKey = 'ai-agents:generate-posts:' . ($agentId ? ('agent:' . $agentId) : 'all');
        $lock = Cache::lock($lockKey, 14 * 60);
        if (!$lock->get()) {
            $this->warn('Skipped: post generation already running.');
            return 0;
        }

        $agents = AiAgent::query()
            ->where('is_active', true)
            ->when($agentId, fn ($q) => $q->where('id', $agentId))
            ->with('user')
            ->get();

        if ($agents->isEmpty()) {
            $this->error('No active AI agents found.');
            optional($lock)->release();
            return 1;
        }

        $runViralDetector = (bool) config('agent-creation.viral_reactions.enabled', false);
        if ($runViralDetector && !$agentId) {
            $this->info('Checking for viral content and chain reactions...');
            try {
                $this->viralDetector->detectAndReact();
            } catch (\Throwable $e) {
                Log::warning('Viral detector failed, continuing post generation.', ['error' => $e->getMessage()]);
            }
        }

        $this->info("Found {$agents->count()} active agent(s). Generating posts...");

        $successCount = 0;
        $failCount = 0;

        foreach ($agents as $agent) {
            try {
                $warmUpManager = new WarmUpManager();

                if ($warmUpManager->isInWarmUp($agent)) {
                    $warmUpManager->updateStage($agent);
                    $agent->refresh();

                    if (!$warmUpManager->canPost($agent)) {
                        $this->line("Skipping {$agent->user->name} (warm-up: {$agent->warm_up_stage})");
                        continue;
                    }
                }

                $this->evolutionManager->checkAndUpgradeStage($agent);
                if (!$agent->last_reputation_update || $agent->last_reputation_update->diffInHours(now()) >= 4) {
                    $this->reputationService->updateReputation($agent);
                }

                $breakingNews = $this->breakingNewsDetector->getBreakingNews($agent->country);
                $liveEvent = $this->breakingNewsDetector->getLiveEvent($agent->country);

                $isBreaking = false;
                $isBreakingAnalysis = false;
                $isLiveEvent = false;
                $generationContext = [];

                if ($breakingNews) {
                    $existingPosts = $agent->user->posts()
                        ->where('content', 'like', '%' . substr($breakingNews->title, 0, 20) . '%')
                        ->latest('created_at')
                        ->get();

                    if ($existingPosts->isEmpty()) {
                        $isBreaking = true;
                    } elseif ($existingPosts->count() === 1) {
                        $lastPostTime = $existingPosts->first()->created_at;
                        if ($lastPostTime->diffInMinutes(now()) >= 30) {
                            $isBreakingAnalysis = true;
                        }
                    }
                }

                if (!$isBreaking && $liveEvent) {
                    $isLiveEvent = true;
                }

                if (!$force && !$isBreaking && !$isBreakingAnalysis && !$isLiveEvent) {
                    if (!$this->isWithinPeakWindowForAgent($agent)) {
                        $this->line("Skipping {$agent->user->name} (outside IST peak window/random delay gate)");
                        continue;
                    }

                    if (!$this->scheduleManager->shouldBeActiveNow($agent)) {
                        $nextWindow = $this->scheduleManager->getNextActivityWindow($agent);
                        $this->line("Skipping {$agent->user->name} (not in activity window, next in {$nextWindow['starts_in_minutes']} min)");
                        continue;
                    }

                    if (!$this->scheduleManager->shouldPost($agent)) {
                        $this->line("Skipping {$agent->user->name} (already posted in this window)");
                        continue;
                    }
                }

                $boostFactor = $this->eventsManager->getActivityBoostFactor($agent);
                if (!$force && !$isBreaking && !$isBreakingAnalysis && !$isLiveEvent && $this->scheduleManager->hasReachedDailyLimit($agent, 'post', $boostFactor)) {
                    $this->line("Skipping {$agent->user->name} (daily post limit reached: {$agent->daily_posts_count}/" . ((int) ($agent->daily_posts_limit * $boostFactor)) . ')');
                    continue;
                }

                if ($isBreaking) {
                    $generationContext = array_merge(
                        $this->buildGenerationContext($agent, 'breaking_news'),
                        ['breaking_news' => $breakingNews]
                    );
                    $content = $this->postGenerator->generatePost($agent, $generationContext);
                    $postType = 'breaking_news';
                } elseif ($isBreakingAnalysis) {
                    $generationContext = array_merge(
                        $this->buildGenerationContext($agent, 'analysis'),
                        ['analysis' => $breakingNews]
                    );
                    $content = $this->postGenerator->generatePost($agent, $generationContext);
                    $postType = 'analysis';
                } elseif ($isLiveEvent) {
                    $generationContext = array_merge(
                        $this->buildGenerationContext($agent, 'live_event'),
                        ['live_event' => $liveEvent]
                    );
                    $content = $this->postGenerator->generatePost($agent, $generationContext);
                    $postType = 'live_event';
                } else {
                    $postType = $this->scheduleManager->getPostTypeForWindow($agent);
                    $generationContext = $this->buildGenerationContext($agent, $postType);
                    $content = $this->postGenerator->generatePost($agent, $generationContext);
                }

                $content = $this->ensureUniquePostContent($agent, $content, $postType, $breakingNews, $liveEvent);
                if (!is_string($content) || trim($content) === '') {
                    $this->line("Skipping {$agent->user->name} (duplicate regeneration exhausted)");
                    continue;
                }

                $moderation = $this->moderationService->analyze($content);
                if (($moderation['action'] ?? null) === 'block') {
                    $regenerated = false;
                    for ($retry = 1; $retry <= 2; $retry++) {
                        $retryContext = array_merge($generationContext, [
                            'variation_seed' => now()->timestamp . ':' . $agent->id . ':safe:' . $retry . ':' . Str::random(6),
                            'safety_mode' => true,
                            'format_retry' => true,
                        ]);
                        $content = $this->postGenerator->generatePost($agent, $retryContext);
                        $content = $this->ensureUniquePostContent($agent, $content, $postType, $breakingNews, $liveEvent);
                        $moderation = $this->moderationService->analyze($content);

                        if (($moderation['action'] ?? null) !== 'block') {
                            $regenerated = true;
                            break;
                        }
                    }

                    if (!$regenerated) {
                        $this->warn("Blocked unsafe post by {$agent->user->name}: {$moderation['reason']}");
                        continue;
                    }
                }

                $status = (($moderation['action'] ?? null) === 'hold') ? 'draft' : 'active';

                $post = Post::create([
                    'user_id' => $agent->user_id,
                    'content' => $content,
                    'type' => 'text',
                    'status' => $status,
                    'text_language' => '',
                    'is_ai_generated' => true,
                ]);

                $this->attachSourceMediaToPost($post, $content, $breakingNews);

                $agent->logActivity('post_generated', [
                    'post_id' => $post->id,
                    'post_type' => $postType,
                    'provider' => $agent->ai_provider,
                    'content_length' => strlen($content),
                ]);
                $this->memoryService->captureActivity($agent, 'post_generated', [
                    'topic' => $postType,
                    'post_id' => $post->id,
                    'historical_anchor' => $isBreaking && $breakingNews ? ($breakingNews->title ?? null) : null,
                    'summary' => substr((string) $content, 0, 200),
                    'source' => $isBreaking && $breakingNews ? ($breakingNews->source ?? null) : null,
                ]);

                $this->memoryService->rememberShortTerm(
                    $agent,
                    'post_' . now()->timestamp,
                    'Posted: ' . substr($content, 0, 80),
                    5
                );

                $agent->touch('last_activity_at');
                $this->scheduleManager->incrementActivityCount($agent, 'post');

                if ($isBreaking || $isLiveEvent || $this->languageManager->shouldTranslate($post)) {
                    $targetLang = strtolower((string) $agent->language) === 'en-us' ? 'Hindi' : 'English';

                    try {
                        $transPrompt = $this->languageManager->getTranslationPrompt($content, $targetLang);
                        $translation = $this->aiManager->generateText($transPrompt, $agent->ai_provider);

                        $post->comments()->create([
                            'user_id' => $agent->user_id,
                            'content' => "[Auto-Translation to {$targetLang}]: {$translation}",
                            'text_language' => '',
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('Translation failed', ['agent_id' => $agent->id, 'error' => $e->getMessage()]);
                    }
                }

                // Multilingual boost: for multilingual countries, periodically add a translated variant.
                $countryLanguages = (array) config('countries.countries.' . strtoupper((string) $agent->country) . '.languages', []);
                if (count($countryLanguages) > 1 && rand(1, 100) <= 35) {
                    $altLanguage = $this->resolveAltLanguageForAgent($agent, $countryLanguages);
                    if ($altLanguage !== null) {
                        try {
                            $transPrompt = $this->languageManager->getTranslationPrompt($content, $altLanguage);
                            $translation = $this->aiManager->generateText($transPrompt, $agent->ai_provider);

                            $post->comments()->create([
                                'user_id' => $agent->user_id,
                                'content' => "[Auto-Translation to {$altLanguage}]: {$translation}",
                                'text_language' => '',
                            ]);
                        } catch (\Throwable $e) {
                            Log::warning('Multilingual translation failed', ['agent_id' => $agent->id, 'error' => $e->getMessage()]);
                        }
                    }
                }

                $successCount++;
                $this->info("Post created for {$agent->user->name}");
            } catch (\Throwable $e) {
                $failCount++;
                $this->error("Failed for {$agent->user->name}: {$e->getMessage()}");
                Log::error('AI Agent Post Generation Failed', [
                    'agent_id' => $agent->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info('Generation complete!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $successCount],
                ['Failed', $failCount],
                ['Total', $successCount + $failCount],
            ]
        );

        optional($lock)->release();
        return 0;
    }

    protected function isRuntimeEnabled(): bool
    {
        $value = DB::table('admin_settings')->where('key', 'ai_runtime_enabled')->value('value');
        if ($value === null) {
            return true;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }

    protected function isWithinPeakWindowForAgent(AiAgent $agent): bool
    {
        $peak = (array) config('agent-creation.peak_posting', []);
        if (!(bool) ($peak['enabled'] ?? true)) {
            return true;
        }

        $timezone = (string) ($peak['timezone'] ?? 'Asia/Kolkata');
        $startHour = (int) ($peak['start_hour'] ?? 19);
        $endHour = (int) ($peak['end_hour'] ?? 22);
        $delayMin = max(0, (int) ($peak['min_random_delay_minutes'] ?? 15));
        $delayMax = max($delayMin, (int) ($peak['random_delay_minutes'] ?? 90));
        $offPeakProbability = max(0, min(100, (int) ($peak['off_peak_probability'] ?? 15)));

        $now = now($timezone);
        $windowStart = $now->copy()->setTime($startHour, 0, 0);
        $windowEnd = $now->copy()->setTime($endHour, 0, 0);

        $delaySeed = abs(crc32($agent->id . '|' . $now->toDateString() . '|peak_delay'));
        $range = max(0, $delayMax - $delayMin);
        $delayMinutes = $delayMin + ($range > 0 ? ($delaySeed % ($range + 1)) : 0);
        $windowStartWithDelay = $windowStart->copy()->addMinutes($delayMinutes);

        if ($now->greaterThanOrEqualTo($windowStartWithDelay) && $now->lessThan($windowEnd)) {
            return true;
        }

        if ($offPeakProbability <= 0) {
            return false;
        }

        $offPeakRoll = (abs(crc32($agent->id . '|' . $now->format('YmdH') . '|off_peak')) % 100) + 1;
        return $offPeakRoll <= $offPeakProbability;
    }

    protected function attachSourceMediaToPost(Post $post, string $content, mixed $breakingNews = null): void
    {
        try {
            $sourceUrl = $this->extractSourceUrl($content);

            $imageUrl = null;
            $videoUrl = null;

            if ($breakingNews) {
                $imageUrl = (string) ($breakingNews->image_url ?? '');
                $sourceUrl = $sourceUrl ?: (string) ($breakingNews->url ?? '');
            }

            if ($sourceUrl) {
                if (!$imageUrl) {
                    $imageUrl = (string) NewsCache::where('url', $sourceUrl)->value('image_url');
                }

                $fetched = $this->fetchMediaFromSource($sourceUrl);
                if (!$imageUrl && !empty($fetched['image_url'])) {
                    $imageUrl = (string) $fetched['image_url'];
                }
                if (!empty($fetched['video_url'])) {
                    $videoUrl = (string) $fetched['video_url'];
                }
            }

            if ($videoUrl) {
                $this->attachExternalMedia($post, $videoUrl, 'video');
                $post->update(['type' => 'video']);
                return;
            }

            if ($imageUrl) {
                $this->attachExternalMedia($post, $imageUrl, 'image');
                $post->update(['type' => 'image']);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed attaching source media for AI post', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function extractSourceUrl(string $content): ?string
    {
        if (preg_match('/\[(https?:\/\/[^\]\s]+)\]/i', $content, $match)) {
            return rtrim((string) $match[1], " \t\n\r\0\x0B.,)");
        }

        if (preg_match('/https?:\/\/[^\s]+/i', $content, $match)) {
            return rtrim((string) $match[0], " \t\n\r\0\x0B.,)");
        }

        return null;
    }

    protected function fetchMediaFromSource(string $url): array
    {
        $result = [
            'image_url' => null,
            'video_url' => null,
        ];

        if ($this->isDirectVideoUrl($url) || preg_match('/(youtube\.com|youtu\.be|vimeo\.com)/i', $url)) {
            $result['video_url'] = $url;
        }

        try {
            $response = Http::timeout(6)->get($url);
            if (!$response->successful()) {
                return $result;
            }

            $html = (string) $response->body();
            $result['image_url'] = $this->extractMetaContent($html, ['og:image', 'twitter:image', 'og:image:url']);
            $result['video_url'] = $result['video_url']
                ?: $this->extractMetaContent($html, ['og:video:url', 'og:video', 'twitter:player:stream']);

            if ($result['image_url']) {
                $result['image_url'] = $this->normalizeMetaUrl($result['image_url'], $url);
            }
            if ($result['video_url']) {
                $result['video_url'] = $this->normalizeMetaUrl($result['video_url'], $url);
            }
        } catch (\Throwable $e) {
            // Silent fallback.
        }

        return $result;
    }

    protected function extractMetaContent(string $html, array $properties): ?string
    {
        foreach ($properties as $property) {
            $pattern = '/<meta[^>]+(?:property|name)=["\']' . preg_quote($property, '/') . '["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i';
            if (preg_match($pattern, $html, $match)) {
                return trim((string) $match[1]);
            }
        }

        return null;
    }

    protected function normalizeMetaUrl(string $mediaUrl, string $pageUrl): string
    {
        if (Str::startsWith($mediaUrl, ['http://', 'https://'])) {
            return $mediaUrl;
        }

        $parts = parse_url($pageUrl);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return $mediaUrl;
        }

        $base = $parts['scheme'] . '://' . $parts['host'];
        if (Str::startsWith($mediaUrl, '//')) {
            return $parts['scheme'] . ':' . $mediaUrl;
        }
        if (Str::startsWith($mediaUrl, '/')) {
            return $base . $mediaUrl;
        }

        return rtrim($base, '/') . '/' . ltrim($mediaUrl, '/');
    }

    protected function attachExternalMedia(Post $post, string $url, string $type): void
    {
        if ($post->media()->exists()) {
            return;
        }

        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        if ($type === 'video') {
            $extension = $extension ?: 'mp4';
            $post->media()->create([
                'source_path' => $url,
                'type' => 'video',
                'disk' => Giphy::getDisk(),
                'extension' => $extension,
                'mime' => 'video/' . $extension,
                'status' => 'processed',
                'metadata' => [
                    'external_source' => true,
                ],
            ]);
            return;
        }

        $extension = $extension ?: 'jpg';
        $post->media()->create([
            'source_path' => $url,
            'type' => 'image',
            'disk' => Giphy::getDisk(),
            'extension' => $extension,
            'mime' => 'image/' . $extension,
            'status' => 'processed',
            'metadata' => [
                'external_source' => true,
            ],
        ]);
    }

    protected function isDirectVideoUrl(string $url): bool
    {
        return (bool) preg_match('/\.(mp4|webm|mov|m3u8)(\?.*)?$/i', $url);
    }

    protected function ensureUniquePostContent(
        AiAgent $agent,
        string $content,
        string $postType,
        mixed $breakingNews = null,
        mixed $liveEvent = null
    ): string {
        $attempts = 0;
        $maxAttempts = 4;

        while ($attempts < $maxAttempts && $this->isDuplicatePostContent($agent, $content)) {
            $attempts++;

            $context = [
                'variation_seed' => now()->timestamp . ':' . $agent->id . ':' . $attempts . ':' . Str::random(6),
                'post_type' => $postType,
                'focus_topic' => $this->pickFocusTopic($agent, $postType),
                'post_style' => $this->pickPostStyle($agent, $postType),
            ];

            if ($breakingNews) {
                $context['breaking_news'] = $breakingNews;
            }
            if ($liveEvent) {
                $context['live_event'] = $liveEvent;
            }

            $content = $this->postGenerator->generatePost($agent, $context);
        }

        if ($this->isDuplicatePostContent($agent, $content)) {
            // Never publish near-duplicate text. Skip this cycle for this agent.
            return '';
        }

        return $content;
    }

    protected function isDuplicatePostContent(AiAgent $agent, string $content): bool
    {
        $fingerprint = $this->contentFingerprint($content);
        $title = $this->extractTitleLine($content);
        $normalizedCurrent = $this->normalizeForSimilarity($content);
        $currentBodySignature = $this->bodySignature($content);

        $recentOwnPosts = Post::query()
            ->where('user_id', $agent->user_id)
            ->where('is_ai_generated', true)
            ->where('created_at', '>=', now()->subDays(3))
            ->latest('id')
            ->limit(30)
            ->pluck('content');

        foreach ($recentOwnPosts as $existing) {
            $existing = (string) $existing;
            if ($this->contentFingerprint($existing) === $fingerprint) {
                return true;
            }
            if ($currentBodySignature !== '' && $this->bodySignature($existing) === $currentBodySignature) {
                return true;
            }
            if ($this->similarityScore($normalizedCurrent, $this->normalizeForSimilarity($existing)) >= 0.82) {
                return true;
            }
        }

        // Global guard: prevent the same generated post from appearing across multiple AI profiles.
        $recentGlobalAiPosts = Post::query()
            ->where('is_ai_generated', true)
            ->where('created_at', '>=', now()->subHours(24))
            ->where('user_id', '!=', $agent->user_id)
            ->latest('id')
            ->limit(200)
            ->pluck('content');

        foreach ($recentGlobalAiPosts as $existingGlobal) {
            $existingGlobal = (string) $existingGlobal;
            if ($this->contentFingerprint($existingGlobal) === $fingerprint) {
                return true;
            }
            if ($currentBodySignature !== '' && $this->bodySignature($existingGlobal) === $currentBodySignature) {
                return true;
            }
            if ($this->similarityScore($normalizedCurrent, $this->normalizeForSimilarity($existingGlobal)) >= 0.76) {
                return true;
            }
        }

        if ($title !== '') {
            $sameTitleRecent = Post::query()
                ->where('is_ai_generated', true)
                ->where('created_at', '>=', now()->subHours(18))
                ->where('content', 'like', '%' . addcslashes($title, '%_\\') . '%')
                ->exists();
            if ($sameTitleRecent) {
                return true;
            }
        }

        return false;
    }

    protected function contentFingerprint(string $content): string
    {
        $normalized = mb_strtolower(trim($content));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return sha1($normalized);
    }

    protected function extractTitleLine(string $content): string
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $content) ?: [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || $line === '🤖') {
                continue;
            }
            if (str_starts_with($line, '📎')) {
                continue;
            }

            return mb_substr($line, 0, 120);
        }

        return '';
    }

    protected function buildGenerationContext(AiAgent $agent, string $postType): array
    {
        return [
            'variation_seed' => now()->timestamp . ':' . $agent->id . ':' . $postType . ':' . Str::random(6),
            'focus_topic' => $this->pickFocusTopic($agent, $postType),
            'post_style' => $this->pickPostStyle($agent, $postType),
            'post_type' => $postType,
        ];
    }

    protected function pickFocusTopic(AiAgent $agent, string $fallback): string
    {
        $fromAgent = is_array($agent->topics) ? $agent->topics : [];
        $base = array_merge(
            array_values(array_filter(array_map('strval', $fromAgent))),
            [
                'ai',
                'artificial intelligence',
                'technology',
                'economy',
                'policy',
                'sports',
                'education',
                'health',
                'culture',
                'jobs',
                'science',
                'climate',
            ]
        );
        $base = array_values(array_unique(array_filter($base, fn ($v) => trim($v) !== '')));

        if (empty($base)) {
            return $fallback ?: 'general';
        }

        return (string) $base[array_rand($base)];
    }

    protected function pickPostStyle(AiAgent $agent, string $postType): string
    {
        $styles = [
            'deep_dive',
            'benchmark',
            'how_to',
            'debate',
            'threat_report',
            'field_notes',
            'explainer',
            'quick_take',
            'ama',
        ];

        if ($agent->personality_type === 'tech') {
            $styles[] = 'architecture';
            $styles[] = 'performance';
        }
        if ($agent->personality_type === 'political') {
            $styles[] = 'policy_brief';
            $styles[] = 'geopolitical';
        }
        if ($postType === 'breaking_news') {
            $styles[] = 'breaking';
        }

        return $styles[array_rand($styles)];
    }

    protected function normalizeForSimilarity(string $content): string
    {
        $content = mb_strtolower($content);
        $content = preg_replace('/https?:\/\/\S+/i', ' ', $content) ?? $content;
        $content = preg_replace('/#[\p{L}\p{N}_]+/u', ' ', $content) ?? $content;
        $content = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $content) ?? $content;
        $content = preg_replace('/\s+/', ' ', trim($content)) ?? trim($content);

        return $content;
    }

    protected function similarityScore(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }

        similar_text($a, $b, $percent);
        $similarTextScore = $percent / 100.0;

        $aTokens = array_values(array_unique(array_filter(explode(' ', $a))));
        $bTokens = array_values(array_unique(array_filter(explode(' ', $b))));
        if (empty($aTokens) || empty($bTokens)) {
            return $similarTextScore;
        }

        $intersection = count(array_intersect($aTokens, $bTokens));
        $union = count(array_unique(array_merge($aTokens, $bTokens)));
        $jaccard = $union > 0 ? ($intersection / $union) : 0.0;

        return ($similarTextScore * 0.6) + ($jaccard * 0.4);
    }

    protected function resolveAltLanguageForAgent(AiAgent $agent, array $countryLanguages): ?string
    {
        $current = strtolower(trim((string) $agent->language));
        $normalized = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $countryLanguages)));
        if (count($normalized) < 2) {
            return null;
        }

        foreach ($normalized as $lang) {
            if (strtolower($lang) !== $current) {
                return $this->normalizeLanguageLabel($lang);
            }
        }

        return null;
    }

    protected function normalizeLanguageLabel(string $lang): string
    {
        $map = [
            'en-us' => 'English', 'en-gb' => 'English', 'en-in' => 'English', 'en-pk' => 'English', 'en-bd' => 'English',
            'en-ca' => 'English', 'en-au' => 'English', 'en-nz' => 'English', 'en-ie' => 'English',
            'hi-in' => 'Hindi', 'ur-pk' => 'Urdu', 'bn-bd' => 'Bengali', 'zh-cn' => 'Chinese', 'ja-jp' => 'Japanese',
            'ko-kr' => 'Korean', 'fr-fr' => 'French', 'fr-ca' => 'French', 'de-de' => 'German', 'it-it' => 'Italian',
            'es-es' => 'Spanish', 'es-mx' => 'Spanish', 'es-ar' => 'Spanish', 'es-cl' => 'Spanish', 'es-co' => 'Spanish',
            'es-ve' => 'Spanish', 'pt-br' => 'Portuguese', 'pt-pt' => 'Portuguese', 'ar-ae' => 'Arabic', 'ar-sa' => 'Arabic',
            'ar-eg' => 'Arabic', 'id-id' => 'Indonesian', 'ms-my' => 'Malay', 'th-th' => 'Thai', 'vi-vn' => 'Vietnamese',
            'tr-tr' => 'Turkish', 'uk-ua' => 'Ukrainian', 'pl-pl' => 'Polish', 'nl-nl' => 'Dutch', 'sv-se' => 'Swedish',
            'no-no' => 'Norwegian', 'da-dk' => 'Danish', 'fi-fi' => 'Finnish', 'el-gr' => 'Greek', 'he-il' => 'Hebrew',
            'am-et' => 'Amharic', 'sw-ke' => 'Swahili', 'tl-ph' => 'Tagalog',
        ];

        $k = strtolower(trim($lang));
        return $map[$k] ?? $lang;
    }

    protected function bodySignature(string $content): string
    {
        $normalized = $this->normalizeForSimilarity($content);
        if ($normalized === '') {
            return '';
        }

        $parts = explode(' source ', $normalized);
        $body = trim($parts[0] ?? $normalized);
        if ($body === '') {
            $body = $normalized;
        }

        $tokens = array_values(array_filter(explode(' ', $body)));
        if (count($tokens) > 80) {
            $tokens = array_slice($tokens, 0, 80);
        }

        return sha1(implode(' ', $tokens));
    }
}
