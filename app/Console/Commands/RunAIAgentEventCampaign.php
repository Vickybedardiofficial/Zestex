<?php

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Models\SpecialEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunAIAgentEventCampaign extends Command
{
    protected $signature = 'ai-agents:run-event-campaign
        {event : Event type (election|war|crisis|disaster|sports)}
        {--country= : Country code (optional, default global)}
        {--title= : Custom campaign title}
        {--execute : Trigger post generation after creating event}
        {--dry-run : Print computed event payload only}';

    protected $description = 'Create/update a special event campaign from templates and optionally execute boosted generation';

    public function handle(): int
    {
        $eventType = strtolower(trim((string) $this->argument('event')));
        $country = $this->option('country') ? strtoupper(trim((string) $this->option('country'))) : null;

        $template = config("agent-creation.event_campaigns.{$eventType}");
        if (!$template) {
            $this->error("Unknown event type: {$eventType}");
            return 1;
        }

        $title = $this->option('title') ?: $this->defaultTitle($eventType, $country);
        $start = now();
        $end = now()->addHours((int) ($template['duration_hours'] ?? 24));
        $payload = [
            'title' => $title,
            'type' => $eventType,
            'country' => $country,
            'keywords' => (array) ($template['keywords'] ?? []),
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'active',
            'boost_factor' => (float) ($template['boost_factor'] ?? 1.2),
            'context_prompt' => (string) ($template['context_prompt'] ?? ''),
        ];

        if ($this->option('dry-run')) {
            $this->table(
                ['Field', 'Value'],
                collect($payload)->map(fn ($v, $k) => [$k, is_array($v) ? implode(', ', $v) : (string) $v])->all()
            );
            return 0;
        }

        $event = SpecialEvent::updateOrCreate(
            [
                'title' => $title,
                'type' => $eventType,
                'country' => $country,
                'status' => 'active',
            ],
            $payload
        );

        $this->info("Campaign active: #{$event->id} {$event->title}");

        $agentCount = AiAgent::query()
            ->where('is_active', true)
            ->when($country, fn ($q) => $q->where('country', $country))
            ->count();
        $this->info("Target active agents: {$agentCount}");

        if ($this->option('execute')) {
            Artisan::call('ai-agents:generate-posts', ['--force' => true]);
            $this->info('Triggered boosted post generation.');
        }

        return 0;
    }

    protected function defaultTitle(string $eventType, ?string $country): string
    {
        $countryLabel = $country ?: 'GLOBAL';
        return strtoupper($eventType) . " Campaign - {$countryLabel} - " . now()->format('Ymd-His');
    }
}

