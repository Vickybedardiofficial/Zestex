<?php

namespace App\Console\Commands;

use App\Services\AI\Matrix\AgentMatrixSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncAIAgentMatrix extends Command
{
    protected $signature = 'ai-agents:sync-matrix {--countries= : Comma-separated country codes} {--topics= : Comma-separated topics} {--global : Use all configured countries}';
    protected $description = 'Ensure country-topic AI agent matrix baseline exists';

    protected AgentMatrixSyncService $service;

    public function __construct(AgentMatrixSyncService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        $adminEnabled = DB::table('admin_settings')->where('key', 'auto_agent_creation_enabled')->value('value');
        if ($this->parseBooleanSetting($adminEnabled) === false) {
            $this->warn('Matrix sync skipped: auto creation is disabled from Admin settings.');
            return 0;
        }

        if (!config('agent-creation.matrix.enabled', true)) {
            $this->warn('Matrix sync is disabled in config(agent-creation.matrix.enabled).');
            return 0;
        }

        $countries = $this->csvOption('countries');
        if ($this->option('global')) {
            $countries = array_keys((array) config('countries.countries', []));
        }
        $topics = $this->csvOption('topics');

        $this->info('Syncing AI agent country-topic matrix...');
        $result = $this->service->sync($countries, $topics);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Created', $result['created']],
                ['Skipped', $result['skipped']],
                ['Failed', $result['failed']],
            ]
        );

        return $result['failed'] > 0 ? 1 : 0;
    }

    protected function csvOption(string $name): ?array
    {
        $raw = $this->option($name);
        if (!$raw) {
            return null;
        }

        return array_values(array_filter(array_map(
            fn ($item) => trim((string) $item),
            explode(',', (string) $raw)
        )));
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
