<?php

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Models\Post;
use App\Models\PostPoll;
use App\Services\AI\Content\PollGenerator;
use App\Services\AI\Memory\AgentMemoryService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateAIAgentPolls extends Command
{
    protected $signature = 'ai-agents:generate-polls {--agent-id= : Specific agent ID} {--force : Reserved flag; cooldown is always enforced}';
    protected $description = 'Generate polls for AI agents';

    protected PollGenerator $pollGenerator;
    protected AgentMemoryService $memoryService;

    public function __construct(PollGenerator $pollGenerator, AgentMemoryService $memoryService)
    {
        parent::__construct();
        $this->pollGenerator = $pollGenerator;
        $this->memoryService = $memoryService;
    }

    public function handle(): int
    {
        if (!(bool) config('agent-creation.polls.enabled', false)) {
            $this->warn('Poll generation is disabled in config(agent-creation.polls.enabled).');
            return 0;
        }

        if (!$this->isAutomationEnabled()) {
            $this->warn('AI automation is disabled from Admin settings.');
            return 0;
        }

        $agentId = $this->option('agent-id');
        $agents = AiAgent::query()
            ->where('is_active', true)
            ->when($agentId, fn ($q) => $q->where('id', $agentId))
            ->with('user')
            ->get();

        $this->info("Found {$agents->count()} agents. Checking poll schedules...");

        foreach ($agents as $agent) {
            try {
                if (!$this->isPollDue($agent)) {
                    continue;
                }

                $created = DB::transaction(function () use ($agent) {
                    $lockedAgent = AiAgent::query()
                        ->where('id', $agent->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedAgent || !$this->isPollDue($lockedAgent)) {
                        return false;
                    }

                    $pollData = $this->pollGenerator->generatePoll($lockedAgent);

                    $post = Post::create([
                        'user_id' => $lockedAgent->user_id,
                        'content' => $pollData['question'],
                        'type' => 'poll',
                        'status' => 'active',
                        'text_language' => '',
                        'is_ai_generated' => true,
                    ]);

                    PostPoll::create([
                        'post_id' => $post->id,
                        'choices' => $pollData['choices'],
                        'votes' => [],
                        'is_anonymous' => false,
                        'is_cancellable' => false,
                        'expires_at' => now()->addHours((int) ($pollData['duration'] ?? 24)),
                    ]);

                    $lockedAgent->logActivity('poll_created', [
                        'poll_id' => $post->id,
                        'type' => $pollData['type'] ?? 'random',
                    ]);
                    $this->memoryService->captureActivity($lockedAgent, 'poll_created', [
                        'topic' => $pollData['type'] ?? 'poll',
                        'post_id' => $post->id,
                    ]);

                    return true;
                });

                if ($created) {
                    $this->info("Poll created for {$agent->user->name}");
                }
            } catch (\Throwable $e) {
                Log::error("Poll Command Failed for {$agent->id}: " . $e->getMessage());
                $this->error("Failed for {$agent->user->name}: {$e->getMessage()}");
            }
        }

        return 0;
    }

    protected function isAutomationEnabled(): bool
    {
        $value = DB::table('admin_settings')->where('key', 'auto_agent_creation_enabled')->value('value');
        if ($value === null) {
            return true;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }

    protected function isPollDue(AiAgent $agent): bool
    {
        $lastPollPostAt = DB::table('posts')
            ->where('user_id', $agent->user_id)
            ->where('type', 'poll')
            ->orderByDesc('created_at')
            ->value('created_at');

        $lastPollLogAt = DB::table('ai_agent_activity_logs')
            ->where('ai_agent_id', $agent->id)
            ->where('action_type', 'poll_created')
            ->orderByDesc('created_at')
            ->value('created_at');

        $lastPollAt = $this->latestCarbon(
            $this->normalizeToCarbon($lastPollPostAt),
            $this->normalizeToCarbon($lastPollLogAt)
        );

        if (!$lastPollAt) {
            return true;
        }

        $cooldown = $this->resolveCooldownDays($agent, $lastPollAt);

        return $lastPollAt->diffInHours(now()) >= ($cooldown * 24);
    }

    protected function normalizeToCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_object($value) && method_exists($value, 'toDateTimeString')) {
            return Carbon::parse($value->toDateTimeString());
        }

        if (is_scalar($value) || $value === null) {
            return $value ? Carbon::parse((string) $value) : null;
        }

        return null;
    }

    protected function resolveCooldownDays(AiAgent $agent, Carbon $lastPollAt): int
    {
        $min = (int) config('agent-creation.polls.cooldown_days_min', 2);
        $max = (int) config('agent-creation.polls.cooldown_days_max', 3);

        if ($max <= $min) {
            return max(1, $min);
        }

        $seed = $agent->id . '|' . $lastPollAt->toDateString();
        $hash = (int) sprintf('%u', crc32($seed));
        $range = $max - $min + 1;

        return $min + ($hash % $range);
    }

    protected function latestCarbon(?Carbon $first, ?Carbon $second): ?Carbon
    {
        if ($first && $second) {
            return $first->greaterThan($second) ? $first : $second;
        }

        return $first ?: $second;
    }
}
