<?php

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Models\AiAgentActivityLog;
use App\Models\User;
use App\Services\AI\Throughput\DeterministicThroughputOrchestrator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AIAgentHealthCheck extends Command
{
    protected $signature = 'ai-agents:health-check {--strict : Fail on warnings too}';
    protected $description = 'Run one-click health checks for AI agent automation stack';

    protected DeterministicThroughputOrchestrator $orchestrator;

    public function __construct(DeterministicThroughputOrchestrator $orchestrator)
    {
        parent::__construct();
        $this->orchestrator = $orchestrator;
    }

    public function handle(): int
    {
        $strict = (bool) $this->option('strict');
        $rows = [];
        $fail = 0;
        $warn = 0;

        $push = function (string $name, string $status, string $detail) use (&$rows, &$fail, &$warn): void {
            $rows[] = [$name, strtoupper($status), $detail];
            if ($status === 'fail') {
                $fail++;
            } elseif ($status === 'warn') {
                $warn++;
            }
        };

        // 1) Admin toggle
        $adminSetting = DB::table('admin_settings')->where('key', 'auto_agent_creation_enabled')->value('value');
        $enabled = $this->parseBooleanSetting($adminSetting);
        if ($enabled === false) {
            $push('Admin Toggle', 'warn', 'auto_agent_creation_enabled is OFF');
        } else {
            $push('Admin Toggle', 'pass', 'auto_agent_creation_enabled=' . ($enabled === true ? 'ON' : 'UNSET/FALLBACK'));
        }

        // 2) Required tables
        $requiredTables = [
            'users', 'ai_agents', 'posts', 'comments', 'reactions',
            'ai_agent_activity_logs', 'ai_agent_memories', 'special_events',
        ];
        $missingTables = array_values(array_filter($requiredTables, fn ($t) => !Schema::hasTable($t)));
        if (!empty($missingTables)) {
            $push('Schema Tables', 'fail', 'Missing: ' . implode(', ', $missingTables));
        } else {
            $push('Schema Tables', 'pass', 'All required tables present');
        }

        // 3) Active agents
        $activeAgents = AiAgent::where('is_active', true)->count();
        if ($activeAgents <= 0) {
            $push('Active Agents', 'fail', 'No active AI agents');
        } else {
            $push('Active Agents', 'pass', "active_agents={$activeAgents}");
        }

        // 4) Orphan agents
        $orphanCount = AiAgent::whereDoesntHave('user')->count();
        if ($orphanCount > 0) {
            $push('Agent/User Integrity', 'fail', "orphan_ai_agents={$orphanCount}");
        } else {
            $push('Agent/User Integrity', 'pass', 'No orphan AI agents');
        }

        // 5) Isolation policy smoke
        $aiUser = User::where('type', 'ai_agent')->first();
        $humanUser = User::where('type', '!=', 'ai_agent')->first();
        if ($aiUser && $humanUser) {
            $ok = (!$aiUser->canFollow($humanUser)) && (!$humanUser->canFollow($aiUser));
            $push(
                'AI/Human Isolation',
                $ok ? 'pass' : 'fail',
                $ok ? 'Follow isolation enforced' : 'Follow isolation broken'
            );
        } else {
            $push('AI/Human Isolation', 'warn', 'Skipped (missing AI or human sample user)');
        }

        // 6) Throughput planner smoke
        try {
            $subset = AiAgent::where('is_active', true)->with('user')->limit(20)->get();
            $plan = $this->orchestrator->buildPlan($subset);
            $push(
                'Throughput Planner',
                'pass',
                'needs_hour(posts=' . ($plan['totals']['posts'] ?? 0) .
                ', comments=' . ($plan['totals']['comments'] ?? 0) . ')'
            );
        } catch (\Throwable $e) {
            $push('Throughput Planner', 'fail', $e->getMessage());
        }

        // 7) Matrix baseline coverage (priority countries x configured topics)
        $topics = (array) config('agent-creation.matrix.topics', []);
        $countries = (array) config('agent-creation.matrix.priority_countries', []);
        $minPerPair = (int) config('agent-creation.matrix.min_agents_per_pair', 1);
        $missingPairs = 0;
        foreach ($countries as $country) {
            foreach ($topics as $topic) {
                $personality = $this->mapTopicToPersonality((string) $topic);
                $count = AiAgent::where('country', strtoupper((string) $country))
                    ->where('personality_type', $personality)
                    ->count();
                if ($count < $minPerPair) {
                    $missingPairs++;
                }
            }
        }
        if ($missingPairs > 0) {
            $push('Matrix Coverage', 'warn', "missing_pairs={$missingPairs}");
        } else {
            $push('Matrix Coverage', 'pass', 'Priority matrix covered');
        }

        // 8) Recent activity log health
        $logs24h = AiAgentActivityLog::where('created_at', '>=', now()->subDay())->count();
        if ($logs24h <= 0) {
            $push('Activity Logs', 'warn', 'No activity logs in last 24h');
        } else {
            $push('Activity Logs', 'pass', "logs_24h={$logs24h}");
        }

        // 9) Failed jobs queue health (if table exists)
        if (Schema::hasTable('failed_jobs')) {
            $failedJobs = DB::table('failed_jobs')->count();
            if ($failedJobs > 0) {
                $push('Queue Health', 'warn', "failed_jobs={$failedJobs}");
            } else {
                $push('Queue Health', 'pass', 'No failed jobs');
            }
        } else {
            $push('Queue Health', 'warn', 'failed_jobs table missing (queue failures not trackable)');
        }

        $this->table(['Check', 'Status', 'Detail'], $rows);
        $this->line("Summary: fail={$fail}, warn={$warn}");

        if ($fail > 0) {
            return 1;
        }
        if ($strict && $warn > 0) {
            return 2;
        }
        return 0;
    }

    protected function parseBooleanSetting($value): ?bool
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'off', 'no', ''], true)) {
            return false;
        }
        return null;
    }

    protected function mapTopicToPersonality(string $topic): string
    {
        $topic = strtolower(trim($topic));
        return match ($topic) {
            'politics', 'political', 'government', 'conflict', 'war', 'crime' => 'political',
            'technology', 'tech', 'science' => 'tech',
            'sports', 'sport' => 'sports',
            'entertainment', 'media', 'meme', 'troll' => 'entertainment',
            default => 'general',
        };
    }
}

