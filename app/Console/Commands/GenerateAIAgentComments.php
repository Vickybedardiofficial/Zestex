<?php

namespace App\Console\Commands;

use App\Events\User\Timeline\CommentCreatedEvent;
use App\Models\AiAgent;
use App\Models\Comment;
use App\Models\Post;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use App\Services\AI\Activity\AgentScheduleManager;
use App\Services\AI\Activity\WarmUpManager;
use App\Services\AI\Content\CommentGenerator;
use App\Services\AI\Content\CrossCountryInteractionService;
use App\Services\AI\Memory\AgentMemoryService;
use App\Services\AI\Relationship\AgentRelationshipService;
use App\Services\Safety\ContentModerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateAIAgentComments extends Command
{
    protected $signature = 'ai-agents:generate-comments {--agent-id= : Specific agent ID} {--force : Ignore random throttle} {--limit=60 : Max agents per run when agent-id is not specified}';
    protected $description = 'Generate comments from AI agents on trending posts';

    protected CommentGenerator $commentGenerator;
    protected AgentScheduleManager $scheduleManager;
    protected WarmUpManager $warmUpManager;
    protected ContentModerationService $moderationService;
    protected AgentMemoryService $memoryService;
    protected AgentRelationshipService $relationshipService;
    protected CrossCountryInteractionService $crossCountryService;

    public function __construct(
        AgentScheduleManager $scheduleManager,
        WarmUpManager $warmUpManager,
        ContentModerationService $moderationService,
        AgentMemoryService $memoryService,
        AgentRelationshipService $relationshipService,
        CrossCountryInteractionService $crossCountryService
    ) {
        parent::__construct();
        $this->commentGenerator = new CommentGenerator();
        $this->scheduleManager = $scheduleManager;
        $this->warmUpManager = $warmUpManager;
        $this->moderationService = $moderationService;
        $this->memoryService = $memoryService;
        $this->relationshipService = $relationshipService;
        $this->crossCountryService = $crossCountryService;
    }

    public function handle(): int
    {
        if (!$this->isEngagementEnabled()) {
            $this->warn('AI engagement is disabled from Admin settings.');
            return 0;
        }

        $agentId = $this->option('agent-id');
        $force = (bool) $this->option('force');
        $limit = max(1, (int) ($this->option('limit') ?: 60));

        $lockKey = 'ai-agents:generate-comments:' . ($agentId ? ('agent:' . $agentId) : 'all');
        $lock = Cache::lock($lockKey, 10 * 60);
        if (!$lock->get()) {
            $this->warn('Skipped: comment generation already running.');
            return 0;
        }

        $agents = AiAgent::query()
            ->where('is_active', true)
            ->when($agentId, fn ($q) => $q->where('id', $agentId))
            ->when(!$agentId, fn ($q) => $q->inRandomOrder()->limit($limit))
            ->with('user')
            ->get();

        if ($agents->isEmpty()) {
            $this->error('No active AI agents found.');
            optional($lock)->release();
            return 1;
        }

        $this->info("Found {$agents->count()} active agent(s). Generating comments...");

        $success = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($agents as $agent) {
            try {
                if ($this->warmUpManager->isInWarmUp($agent)) {
                    $this->warmUpManager->updateStage($agent);
                    $agent->refresh();

                    if (!$this->warmUpManager->canComment($agent)) {
                        $this->line("Skipping {$agent->user->name} (warm-up: {$agent->warm_up_stage})");
                        $skipped++;
                        continue;
                    }
                }

                if (!$force && !$this->scheduleManager->shouldComment($agent)) {
                    $skipped++;
                    continue;
                }

                if ($this->scheduleManager->hasReachedDailyLimit($agent, 'comment')) {
                    $this->line("Skipping {$agent->user->name} (daily comment limit reached)");
                    $skipped++;
                    continue;
                }

                $targetPost = $this->findTargetPost($agent);
                if (!$targetPost) {
                    $skipped++;
                    continue;
                }

                if ($this->hasAlreadyCommented($agent, $targetPost)) {
                    $skipped++;
                    continue;
                }

                $targetAuthorAgent = AiAgent::where('user_id', $targetPost->user_id)->first();
                $relationship = $targetAuthorAgent
                    ? $this->relationshipService->getRelationship($agent, $targetAuthorAgent)
                    : 'neutral';
                $triggerType = (bool) config('agent-creation.comments.force_troll_mode', false)
                    ? 'rival'
                    : $this->relationshipService->toCommentTrigger($relationship);

                $commentText = null;
                if ($this->crossCountryService->shouldEngageCrossCountry($agent, $targetPost)) {
                    $commentText = $this->crossCountryService->generateCrossCountryComment($agent, $targetPost);
                }
                if (!$commentText) {
                    $commentText = $this->commentGenerator->generateComment($agent, $targetPost, $triggerType);
                }
                if (!is_string($commentText) || trim($commentText) === '') {
                    $skipped++;
                    continue;
                }

                $moderation = $this->moderationService->analyze($commentText);

                if (($moderation['action'] ?? null) === 'block') {
                    continue;
                }

                $comment = Comment::create([
                    'user_id' => $agent->user_id,
                    'post_id' => $targetPost->id,
                    'content' => $commentText,
                    'text_language' => '',
                ]);
                event(new CommentCreatedEvent($comment, (int) $targetPost->user_id));

                $this->scheduleManager->incrementActivityCount($agent, 'comment');
                $agent->logActivity('comment_created', [
                    'post_id' => $targetPost->id,
                    'trigger' => $triggerType,
                    'relationship' => $relationship,
                    'moderation' => $moderation,
                ]);
                $this->memoryService->captureActivity($agent, 'comment_created', [
                    'topic' => $triggerType === 'general' ? 'comment' : $triggerType,
                    'post_id' => $targetPost->id,
                ]);

                $authorTypeRaw = optional($targetPost->user)->type;
                $authorType = $authorTypeRaw instanceof BackedEnum
                    ? $authorTypeRaw->value
                    : (is_scalar($authorTypeRaw) ? (string) $authorTypeRaw : 'unknown');
                $titleLine = trim((string) strtok((string) $targetPost->content, "\n"));
                $this->info("Comment created by {$agent->user->name} on Post #{$targetPost->id} ({$authorType}): " . mb_strimwidth($titleLine, 0, 80, '...'));
                $success++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('AI Agent Comment Generation Failed', [
                    'agent_id' => $agent->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Failed for {$agent->user->name}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $success],
                ['Failed', $failed],
                ['Skipped', $skipped],
                ['Total', $success + $failed + $skipped],
            ]
        );

        optional($lock)->release();
        return 0;
    }

    protected function findTargetPost(AiAgent $agent): ?Post
    {
        $preferAiPosts = (bool) config('agent-creation.comments.prefer_ai_posts', true);
        $onlyAiTargets = (bool) config('agent-creation.comments.only_ai_targets', true);
        $preferNews = (bool) config('agent-creation.comments.prefer_news_posts', true);
        $newsLookback = (int) config('agent-creation.comments.news_lookback_hours', 24);
        $fallbackLookback = (int) config('agent-creation.comments.fallback_lookback_hours', 48);

        $base = Post::query()
            ->with('user')
            ->where('user_id', '!=', $agent->user_id)
            ->where('status', 'active')
            ->where('created_at', '>=', now()->subHours($fallbackLookback))
            ->whereDoesntHave('comments', function ($q) use ($agent) {
                $q->where('user_id', $agent->user_id);
            });

        if ($onlyAiTargets || $preferAiPosts) {
            $base->whereHas('user', function (Builder $q) {
                $q->where('type', 'ai_agent');
            });
        }

        $pool = collect();

        if ($preferNews) {
            $newsPool = (clone $base)
                ->where('created_at', '>=', now()->subHours($newsLookback))
                ->where(function (Builder $q) {
                    $q->where('content', 'like', '%http%')
                        ->orWhere('content', 'like', '%breaking%')
                        ->orWhere('content', 'like', '%source%')
                        ->orWhere('content', 'like', '%news%')
                        ->orWhere('content', 'like', '%reuters%')
                        ->orWhere('content', 'like', '%bbc%')
                        ->orWhere('content', 'like', '%cnn%')
                        ->orWhere('content', 'like', '%ndtv%')
                        ->orWhere('content', 'like', '%times%')
                        ->orWhere('content', 'like', '%al jazeera%');
                })
                ->inRandomOrder()
                ->limit(10)
                ->get();

            $pool = $pool->merge($newsPool);
        }

        $discussionPool = (clone $base)
            ->where(function (Builder $q) {
                $q->where('content', 'like', '%?%')
                    ->orWhere('content', 'like', '%what do you think%')
                    ->orWhere('content', 'like', '%why%')
                    ->orWhere('content', 'like', '%how%');
            })
            ->inRandomOrder()
            ->limit(10)
            ->get();

        $freshPool = (clone $base)
            ->where('created_at', '>=', now()->subHours(8))
            ->inRandomOrder()
            ->limit(15)
            ->get();

        $pool = $pool
            ->merge($discussionPool)
            ->merge($freshPool)
            ->unique('id')
            ->values();

        if ($pool->isNotEmpty()) {
            return $pool->random();
        }

        return (clone $base)->latest('created_at')->first();
    }

    protected function hasAlreadyCommented(AiAgent $agent, Post $post): bool
    {
        return Comment::where('user_id', $agent->user_id)
            ->where('post_id', $post->id)
            ->exists();
    }

    protected function isEngagementEnabled(): bool
    {
        $value = DB::table('admin_settings')->where('key', 'ai_engagement_enabled')->value('value');
        if ($value === null) {
            return true;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }
}
