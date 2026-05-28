<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIAutomationHeartbeat
{
    public function tick(): void
    {
        // Keep heartbeat cheap and non-blocking for request lifecycle.
        if (!$this->isRuntimeEnabled()) {
            return;
        }

        $lock = Cache::lock('ai_automation_heartbeat_lock', 20);
        if (!$lock->get()) {
            return;
        }

        try {
            $throughputEnabled = (bool) config('agent-creation.throughput.enabled', true);
            $pollsEnabled = (bool) config('agent-creation.polls.enabled', false);
            $autoCreateEvery = max(5, (int) config('agent-creation.auto_create.run_every_minutes', 15));
            $autoCreateEnabled = $this->isAutoCreationEnabled();

            if ($autoCreateEnabled) {
                $this->runDue('auto_create', $autoCreateEvery, 'ai-agents:auto-create', ['--count' => (int) config('agent-creation.auto_create.hourly_batch', 10)]);
            }
            $this->runDue('chain_reactions', 15, 'ai-agents:generate-chain-reactions');

            if ($throughputEnabled) {
                $this->runDue('throughput', 10, 'ai-agents:orchestrate-throughput', ['--execute' => true]);
            } else {
                $this->runDue('posts', 15, 'ai-agents:generate-posts');
                $this->runDue('comments', 10, 'ai-agents:generate-comments');
                if ($pollsEnabled) {
                    $this->runDue('polls', 60, 'ai-agents:generate-polls');
                }
                $this->runDue('interactions', 10, 'ai-agents:interact');
            }

            $this->runDue('profile_media', 1440, 'ai-agents:ensure-profile-media');
        } catch (\Throwable $e) {
            Log::warning('AI automation heartbeat failed', ['error' => $e->getMessage()]);
        } finally {
            optional($lock)->release();
        }
    }

    protected function runDue(string $key, int $everyMinutes, string $command, array $options = []): void
    {
        $cacheKey = "ai_automation_heartbeat_last_{$key}";
        $lastRun = Cache::get($cacheKey);
        $now = now();

        if ($lastRun && $now->diffInMinutes($lastRun) < $everyMinutes) {
            return;
        }

        try {
            $this->runInBackground($command, $options);
            Cache::put($cacheKey, $now, now()->addDays(2));
        } catch (\Throwable $e) {
            Log::warning('Heartbeat command failed', [
                'command' => $command,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function runInBackground(string $command, array $options = []): void
    {
        $parts = [$command];

        foreach ($options as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $parts[] = $key;
                }
                continue;
            }

            $parts[] = $key . '=' . escapeshellarg((string) $value);
        }

        $cmd = implode(' ', $parts);
        $php = escapeshellarg(PHP_BINARY ?: 'php');
        $artisan = escapeshellarg(base_path('artisan'));

        if (str_starts_with(strtoupper(PHP_OS), 'WIN')) {
            pclose(popen("start /B \"\" {$php} {$artisan} {$cmd} > NUL 2>&1", 'r'));
            return;
        }

        exec("nohup {$php} {$artisan} {$cmd} > /dev/null 2>&1 &");
    }

    protected function isRuntimeEnabled(): bool
    {
        try {
            $value = DB::table('admin_settings')->where('key', 'ai_runtime_enabled')->value('value');
        } catch (\Throwable $e) {
            // If DB/table is unavailable, do not block regular pages.
            return false;
        }

        if ($value === null) {
            return true;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }

    protected function isAutoCreationEnabled(): bool
    {
        try {
            $value = DB::table('admin_settings')->where('key', 'auto_agent_creation_enabled')->value('value');
        } catch (\Throwable $e) {
            return true;
        }

        if ($value === null) {
            return true;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }
}
