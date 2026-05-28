<?php

namespace App\Console\Commands;

use App\Events\User\Timeline\CommentCreatedEvent;
use App\Models\AiAgent;
use App\Models\Comment;
use App\Models\Post;
use App\Services\AI\Activity\AgentScheduleManager;
use App\Services\AI\Content\CommentGenerator;
use App\Services\AI\Content\CrossCountryInteractionService;
use App\Services\AI\Memory\AgentMemoryService;
use App\Services\AI\Relationship\AgentRelationshipService;
use App\Services\Safety\ContentModerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateAIAgentChainReactions extends Command
{
    protected $signature = 'ai-agents:generate-chain-reactions {--post-id= : Specific AI post ID} {--limit=10 : Max source posts to process}';
    protected $description = 'Generate deterministic chain-reaction comments across allied/rival/mentor agents';

    protected CommentGenerator $commentGenerator;
    protected CrossCountryInteractionService $crossCountryService;
    protected AgentRelationshipService $relationshipService;
    protected AgentScheduleManager $scheduleManager;
    protected AgentMemoryService $memoryService;
    protected ContentModerationService $moderationService;

    public function __construct(
        AgentScheduleManager $scheduleManager,
        AgentMemoryService $memoryService,
        ContentModerationService $moderationService,
        AgentRelationshipService $relationshipService,
        CrossCountryInteractionService $crossCountryService
    ) {
        parent::__construct();
        $this->scheduleManager = $scheduleManager;
        $this->memoryService = $memoryService;
        $this->moderationService = $moderationService;
        $this->relationshipService = $relationshipService;
        $this->crossCountryService = $crossCountryService;
        $this->commentGenerator = new CommentGenerator();
    }

    public function handle(): int
    {
        if (!$this->isEngagementEnabled()) {
            $this->warn('AI engagement is disabled from Admin settings.');
            return 0;
        }

        $limit = max(1, (int) $this->option('limit'));
        $postId = $this->option('post-id');

        $sourcePosts = Post::query()
            ->whereHas('user', fn ($q) => $q->where('type', 'ai_agent'))
            ->when($postId, fn ($q) => $q->where('id', $postId))
            ->where('status', 'active')
            ->where('created_at', '>=', now()->subHours(2))
            ->latest('id')
            ->limit($limit)
            ->get();

        if ($sourcePosts->isEmpty()) {
            $this->warn('No eligible AI source posts found for chain reaction.');
            return 0;
        }

        $success = 0;
        $failed = 0;

        foreach ($sourcePosts as $post) {
            $cacheKey = "chain_reaction_post_{$post->id}";
            if (Cache::has($cacheKey)) {
                continue;
            }

            $authorAgent = AiAgent::where('user_id', $post->user_id)->first();
            if (!$authorAgent) {
                continue;
            }

            $participants = AiAgent::query()
                ->where('is_active', true)
                ->where('id', '!=', $authorAgent->id)
                ->inRandomOrder()
                ->take(8)
                ->get();

            $ranked = $this->relationshipService->rankParticipants($authorAgent, $participants, 3);

            foreach ($ranked as $row) {
                /** @var AiAgent $agent */
                $agent = $row['agent'];
                $relationship = $row['relationship'];
                $triggerType = $this->relationshipService->toCommentTrigger($relationship);

                if ($this->scheduleManager->hasReachedDailyLimit($agent, 'comment')) {
                    continue;
                }

                if (Comment::where('user_id', $agent->user_id)->where('post_id', $post->id)->exists()) {
                    continue;
                }

                try {
                    $commentText = $this->buildCommentWithRetry($agent, $post, $triggerType);
                    if (!$commentText) {
                        continue;
                    }

                    $moderation = $this->moderationService->analyze($commentText);
                    if (($moderation['action'] ?? null) === 'block') {
                        continue;
                    }

                    $comment = Comment::create([
                        'user_id' => $agent->user_id,
                        'post_id' => $post->id,
                        'content' => $commentText,
                        'text_language' => '',
                    ]);
                    event(new CommentCreatedEvent($comment, (int) $post->user_id));

                    $this->scheduleManager->incrementActivityCount($agent, 'comment');
                    $agent->logActivity('comment_created', [
                        'post_id' => $post->id,
                        'source' => 'chain_reaction',
                        'relationship' => $relationship,
                        'trigger' => $triggerType,
                    ]);
                    $this->memoryService->captureActivity($agent, 'comment_created', [
                        'topic' => 'chain_reaction',
                        'post_id' => $post->id,
                    ]);

                    $success++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::error('Chain reaction comment failed', [
                        'agent_id' => $agent->id,
                        'post_id' => $post->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Cache::put($cacheKey, true, now()->addMinutes(30));
        }

        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $success],
                ['Failed', $failed],
                ['Total', $success + $failed],
            ]
        );

        return 0;
    }

    protected function buildCommentWithRetry(AiAgent $agent, Post $post, string $triggerType): ?string
    {
        $attempts = 0;
        $maxAttempts = 2;

        while ($attempts < $maxAttempts) {
            $attempts++;

            try {
                $commentText = null;
                if ($this->crossCountryService->shouldEngageCrossCountry($agent, $post)) {
                    $commentText = $this->crossCountryService->generateCrossCountryComment($agent, $post);
                }

                if (!$commentText) {
                    $commentText = $this->commentGenerator->generateComment($agent, $post, $triggerType);
                }

                if (is_string($commentText) && trim($commentText) !== '') {
                    return $commentText;
                }
            } catch (\Throwable $e) {
                if ($attempts >= $maxAttempts) {
                    throw $e;
                }
                usleep(150000); // tiny backoff for transient provider/network jitter
            }
        }

        return null;
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
