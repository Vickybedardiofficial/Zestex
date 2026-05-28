<?php

namespace App\Console\Commands;

use App\Services\AI\AutoAgentCreator;
use Illuminate\Console\Command;

class AutoCreateAgents extends Command
{
    protected $signature = 'ai-agents:auto-create {--count= : Number of agents to create}';
    protected $description = 'Automatically create AI agents based on trending news';

    public function handle()
    {
        // Check Admin Setting
        $adminEnabled = \Illuminate\Support\Facades\DB::table('admin_settings')->where('key', 'auto_agent_creation_enabled')->value('value');
        if ($this->parseBooleanSetting($adminEnabled) === false) {
            $this->warn('Auto-creation is currently DISABLED in the Admin Panel.');
            $this->info('Go to Admin -> AI Agents -> Enable Auto-Creation.');
            return 0;
        }

        $count = $this->option('count') ?? config('agent-creation.auto_create.per_run', 3);

        $this->info('Starting automatic agent creation...');
        $this->info("Target: {$count} agents");

        $creator = new AutoAgentCreator();
        $agents = $creator->createAgents($count);

        if (empty($agents)) {
            $this->warn('No agents were created. This could be because:');
            $this->warn('1. Daily limit reached (Check logs)');
            $this->warn('2. Max total agents reached');
            $this->warn('3. No trending countries found');
            return 0;
        }

        $this->newLine();
        $this->info('Successfully created ' . count($agents) . ' agents!');

        $this->table(
            ['ID', 'Name', 'Country', 'Personality', 'Age', 'City'],
            collect($agents)->map(fn($agent) => [
                $agent->id,
                $agent->user->name,
                $agent->country,
                $agent->personality_type,
                $agent->age,
                $agent->city,
            ])->toArray()
        );

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
}
