<?php

namespace App\Services\AI\Matrix;

use App\Models\AiAgent;
use App\Services\AI\AutoAgentCreator;
use Illuminate\Support\Facades\Log;

class AgentMatrixSyncService
{
    protected AutoAgentCreator $creator;

    public function __construct(AutoAgentCreator $creator)
    {
        $this->creator = $creator;
    }

    /**
     * Ensure country-topic matrix baseline exists.
     *
     * @return array{created:int, skipped:int, failed:int, details:array<int,array<string,mixed>>}
     */
    public function sync(?array $countries = null, ?array $topics = null): array
    {
        $countries = $countries ?: (array) config('agent-creation.matrix.priority_countries', []);
        $topics = $topics ?: (array) config('agent-creation.matrix.topics', []);

        $minPerPair = (int) config('agent-creation.matrix.min_agents_per_pair', 1);
        $maxPerPair = (int) config('agent-creation.matrix.max_agents_per_pair', 3);

        $created = 0;
        $skipped = 0;
        $failed = 0;
        $details = [];

        foreach ($countries as $country) {
            foreach ($topics as $topic) {
                $topic = strtolower(trim((string) $topic));
                $country = strtoupper(trim((string) $country));

                $current = AiAgent::query()
                    ->where('country', $country)
                    ->where('personality_type', $this->mapTopicToPersonality($topic))
                    ->count();

                if ($current >= $minPerPair) {
                    $skipped++;
                    $details[] = [
                        'country' => $country,
                        'topic' => $topic,
                        'status' => 'ok',
                        'existing' => $current,
                    ];
                    continue;
                }

                $needed = min($minPerPair - $current, max(0, $maxPerPair - $current));
                if ($needed <= 0) {
                    $skipped++;
                    continue;
                }

                for ($i = 0; $i < $needed; $i++) {
                    try {
                        $agent = $this->creator->createAgentForCountryTopic($country, $topic);
                        $created++;
                        $details[] = [
                            'country' => $country,
                            'topic' => $topic,
                            'status' => 'created',
                            'agent_id' => $agent->id,
                        ];
                    } catch (\Throwable $e) {
                        $failed++;
                        $details[] = [
                            'country' => $country,
                            'topic' => $topic,
                            'status' => 'failed',
                            'error' => $e->getMessage(),
                        ];

                        Log::error('Matrix sync create failed', [
                            'country' => $country,
                            'topic' => $topic,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'failed' => $failed,
            'details' => $details,
        ];
    }

    protected function mapTopicToPersonality(string $topic): string
    {
        return match ($topic) {
            'politics', 'political', 'government', 'conflict', 'war', 'crime' => 'political',
            'technology', 'tech', 'science' => 'tech',
            'sports', 'sport' => 'sports',
            'troll' => 'troll',
            'entertainment', 'media', 'meme' => 'entertainment',
            default => 'general',
        };
    }
}
