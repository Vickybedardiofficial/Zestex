<?php

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Models\User;
use App\Services\AI\ProfileGenerator\ProfileMediaGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EnsureAIAgentProfileMedia extends Command
{
    protected $signature = 'ai-agents:ensure-profile-media {--force : Overwrite existing avatar and cover for all agents} {--agent-id= : Process one AI agent id}';
    protected $description = 'Ensure avatar and cover exist for AI agent profiles';

    protected ProfileMediaGenerator $mediaGenerator;

    public function __construct(ProfileMediaGenerator $mediaGenerator)
    {
        parent::__construct();
        $this->mediaGenerator = $mediaGenerator;
    }

    public function handle(): int
    {
        if (!$this->isAutomationEnabled()) {
            $this->warn('AI automation is disabled from Admin settings.');
            return 0;
        }

        $force = (bool) $this->option('force');
        $agentId = $this->option('agent-id');

        $agents = AiAgent::query()
            ->with('user')
            ->when($agentId, fn ($q) => $q->where('id', (int) $agentId))
            ->get();

        if ($agents->isEmpty()) {
            $this->warn('No AI agents found.');
            return 0;
        }

        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($agents as $agent) {
            try {
                if (!$agent->user) {
                    $skipped++;
                    continue;
                }

                $shouldSetAvatar = $force
                    || empty($agent->user->avatar)
                    || $agent->user->hasDefaultAvatar()
                    || $this->isBrokenMediaPath((string) $agent->user->avatar);
                $shouldSetCover = $force
                    || empty($agent->user->cover)
                    || $agent->user->hasDefaultCover()
                    || $this->isBrokenMediaPath((string) $agent->user->cover);

                if (!$shouldSetAvatar && !$shouldSetCover) {
                    $skipped++;
                    continue;
                }

                $payload = [];
                if ($shouldSetAvatar) {
                    $payload['avatar'] = $this->mediaGenerator->generateAvatar($agent);
                }

                if ($shouldSetCover) {
                    $payload['cover'] = $this->mediaGenerator->generateCover($agent);
                }

                $agent->user->update($payload);
                $agent->logActivity('profile_media_generated', [
                    'avatar_updated' => $shouldSetAvatar,
                    'cover_updated' => $shouldSetCover,
                ]);

                $updated++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Failed to ensure AI agent profile media', [
                    'agent_id' => $agent->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Also fix AI-typed users that do not have ai_agents rows.
        $orphanAiUsers = User::query()
            ->leftJoin('ai_agents', 'ai_agents.user_id', '=', 'users.id')
            ->where('users.type', 'ai_agent')
            ->whereNull('ai_agents.id')
            ->select('users.*')
            ->get();

        foreach ($orphanAiUsers as $user) {
            try {
                $payload = [
                    'avatar' => $this->mediaGenerator->generateAvatarForUser($user),
                    'cover' => $this->mediaGenerator->generateCoverForUser($user),
                ];
                $user->update($payload);
                $updated++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Failed to ensure orphan AI user media', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->table(
            ['Status', 'Count'],
            [
                ['Updated', $updated],
                ['Skipped', $skipped],
                ['Failed', $failed],
                ['Total', $updated + $skipped + $failed],
            ]
        );

        return $failed > 0 ? 1 : 0;
    }

    protected function isAutomationEnabled(): bool
    {
        $value = DB::table('admin_settings')->where('key', 'auto_agent_creation_enabled')->value('value');
        if ($value === null) {
            return true;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }

    protected function isBrokenMediaPath(string $path): bool
    {
        $path = trim($path);
        if ($path === '') {
            return true;
        }

        // Remote URLs are considered valid references.
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return false;
        }

        try {
            return !Storage::disk(static_storage_disk())->exists($path);
        } catch (\Throwable $e) {
            return true;
        }
    }
}
