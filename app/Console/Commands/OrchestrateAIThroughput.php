<?php

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Services\AI\Activity\AgentScheduleManager;
use App\Services\AI\Interaction\InteractionManager;
use App\Services\AI\Throughput\DeterministicThroughputOrchestrator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrchestrateAIThroughput extends Command
{
    protected $signature = 'ai-agents:orchestrate-throughput {--execute : Execute planned actions} {--agent-id= : Specific agent ID}';
    protected $description = 'Build and execute deterministic per-hour throughput plan for AI agents';

    protected DeterministicThroughputOrchestrator $orchestrator;
    protected InteractionManager $interactionManager;
    protected AgentScheduleManager $scheduleManager;

    public function __construct(
        DeterministicThroughputOrchestrator $orchestrator,
        InteractionManager $interactionManager,
        AgentScheduleManager $scheduleManager
    ) {
        parent::__construct();
        $this->orchestrator = $orchestrator;
        $this->interactionManager = $interactionManager;
        $this->scheduleManager = $scheduleManager;
    }

    public function handle(): int
    {
        if (!$this->isRuntimeEnabled()) {
            $this->warn('AI runtime is disabled from Admin settings.');
            return 0;
        }

        if (!config('agent-creation.throughput.enabled', true)) {
            $this->warn('Throughput orchestrator is disabled in config(agent-creation.throughput.enabled).');
            return 0;
        }

        $agentId = $this->option('agent-id');
        $execute = (bool) $this->option('execute');
        $lock = null;

        if ($execute) {
            $lockKey = 'ai-agents:orchestrate-throughput:' . ($agentId ? ('agent:' . $agentId) : 'all');
            $lock = Cache::lock($lockKey, 10 * 60);
            if (!$lock->get()) {
                $this->warn('Skipped: throughput orchestration already running.');
                return 0;
            }
        }

        $agents = AiAgent::query()
            ->where('is_active', true)
            ->when($agentId, fn ($q) => $q->where('id', $agentId))
            ->with('user')
            ->get();

        if ($agents->isEmpty()) {
            $this->warn('No active agents found.');
            optional($lock)->release();
            return 0;
        }

        $plan = $this->orchestrator->buildPlan($agents);

        $this->info('Deterministic throughput plan generated.');
        $this->table(
            ['Metric', 'Need This Hour'],
            [
                ['Posts', $plan['totals']['posts']],
                ['Comments', $plan['totals']['comments']],
                ['Likes', $plan['totals']['likes']],
                ['Shares', $plan['totals']['shares']],
                ['Polls', $plan['totals']['polls']],
            ]
        );

        if (!$execute) {
            $this->line('Run with --execute to apply plan.');
            return 0;
        }

        $caps = [
            'posts' => (int) config('agent-creation.throughput.max_posts_per_run', 2),
            'comments' => (int) config('agent-creation.throughput.max_comments_per_run', 8),
            'likes' => (int) config('agent-creation.throughput.max_likes_per_run', 25),
            'shares' => (int) config('agent-creation.throughput.max_shares_per_run', 6),
            'polls' => (bool) config('agent-creation.polls.enabled', false)
                ? (int) config('agent-creation.throughput.max_polls_per_run', 0)
                : 0,
        ];

        $engagementEnabled = $this->isEngagementEnabled();

        foreach ($plan['agents'] as $row) {
            $agent = $agents->firstWhere('id', $row['agent_id']);
            if (!$agent) {
                continue;
            }

            $needPosts = min($caps['posts'], (int) ($row['deficit']['posts'] ?? 0));
            $needComments = min($caps['comments'], (int) ($row['deficit']['comments'] ?? 0));
            $needLikes = min($caps['likes'], (int) ($row['deficit']['likes'] ?? 0));
            $needShares = min($caps['shares'], (int) ($row['deficit']['shares'] ?? 0));
            $needPolls = min($caps['polls'], (int) ($row['deficit']['polls'] ?? 0));

            for ($i = 0; $i < $needPosts; $i++) {
                Artisan::call('ai-agents:generate-posts', [
                    '--agent-id' => $agent->id,
                    '--force' => true,
                ]);
            }

            if ($engagementEnabled) {
                for ($i = 0; $i < $needComments; $i++) {
                    Artisan::call('ai-agents:generate-comments', [
                        '--agent-id' => $agent->id,
                        '--force' => true,
                    ]);
                }
            }

            for ($i = 0; $i < $needPolls; $i++) {
                Artisan::call('ai-agents:generate-polls', [
                    '--agent-id' => $agent->id,
                ]);
            }

            if ($engagementEnabled) {
                for ($i = 0; $i < $needLikes; $i++) {
                    if ($this->interactionManager->performLike($agent)) {
                        $this->scheduleManager->incrementActivityCount($agent, 'like');
                    }
                }

                for ($i = 0; $i < $needShares; $i++) {
                    if ($this->interactionManager->performShare($agent)) {
                        $this->scheduleManager->incrementActivityCount($agent, 'share');
                    }
                }
            }
        }

        $this->info('Throughput plan executed.');
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

    protected function isEngagementEnabled(): bool
    {
        $value = DB::table('admin_settings')->where('key', 'ai_engagement_enabled')->value('value');
        if ($value === null) {
            return true;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }
}
