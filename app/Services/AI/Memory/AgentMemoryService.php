<?php

namespace App\Services\AI\Memory;

use App\Models\AiAgent;
use App\Models\AiAgentMemory;
use Illuminate\Support\Facades\Log;

class AgentMemoryService
{
    /**
     * Store a short-term memory (Expires in 24 hours)
     */
    public function rememberShortTerm(AiAgent $agent, string $key, $value, int $importance = 1): AiAgentMemory
    {
        return $this->remember($agent, 'short', $key, $value, $importance, now()->addHours(24));
    }

    /**
     * Store a medium-term memory (Expires in 30 days)
     */
    public function rememberMediumTerm(AiAgent $agent, string $key, $value, int $importance = 5): AiAgentMemory
    {
        return $this->remember($agent, 'medium', $key, $value, $importance, now()->addDays(30));
    }

    /**
     * Store a long-term memory (No expiry)
     */
    public function rememberLongTerm(AiAgent $agent, string $key, $value, int $importance = 10): AiAgentMemory
    {
        return $this->remember($agent, 'long', $key, $value, $importance, null);
    }

    /**
     * Core remember logic
     */
    protected function remember(AiAgent $agent, string $type, string $key, $value, int $importance, $expiresAt): AiAgentMemory
    {
        $normalizedValue = $this->normalizeMemoryValue($value);

        // Update existing memory or create new
        return AiAgentMemory::updateOrCreate(
            [
                'ai_agent_id' => $agent->id,
                'type' => $type,
                'key' => $key,
            ],
            [
                'value' => $normalizedValue,
                'importance' => $importance,
                'expires_at' => $expiresAt,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Recall memories by key pattern
     */
    public function recall(AiAgent $agent, string $keyPattern = null, string $type = 'short'): array
    {
        $query = $agent->memories()->where('type', $type)->valid();

        if ($keyPattern) {
            $query->where('key', 'like', $keyPattern);
        }

        return $query->orderBy('importance', 'desc')->get()->pluck('value', 'key')->toArray();
    }

    /**
     * Check if agent remembers something specific
     */
    public function hasMemory(AiAgent $agent, string $key, string $type = 'short'): bool
    {
        return $agent->memories()
            ->where('type', $type)
            ->where('key', $key)
            ->valid()
            ->exists();
    }

    /**
     * Forget a specific memory
     */
    public function forget(AiAgent $agent, string $key, string $type = 'short'): bool
    {
        return $agent->memories()
            ->where('type', $type)
            ->where('key', $key)
            ->delete();
    }

    /**
     * Get a formatted context string for the AI prompt
     */
    public function getMemoryContext(AiAgent $agent): string
    {
        // 1. Short Term (What did I do today?)
        $shortTerm = $this->recall($agent, null, 'short');
        $shortContext = "Recent Activity (Last 24h):\n";
        if (empty($shortTerm)) {
            $shortContext .= "- No recent activity.\n";
        } else {
            foreach ($shortTerm as $key => $val) {
                // If value is array, just take the first item or json encode
                $valStr = is_array($val) ? json_encode($val) : $val;
                $shortContext .= "- {$key}: {$valStr}\n";
            }
        }

        // 2. Medium Term (Ongoing topics)
        $mediumTerm = $this->recall($agent, 'topic:%', 'medium');
        $mediumContext = "Ongoing Narratives (Last 30 days):\n";
        if (empty($mediumTerm)) {
            $mediumContext .= "- No ongoing narratives.\n";
        } else {
            foreach ($mediumTerm as $key => $val) {
                $topicName = str_replace('topic:', '', $key);
                $valStr = is_array($val) ? json_encode($val) : $val;
                $mediumContext .= "- {$topicName}: {$valStr}\n";
            }
        }

        // 3. Long Term (Historical anchors)
        $longTerm = $this->recall($agent, 'history:%', 'long');
        $longContext = "Historical Anchors:\n";
        if (empty($longTerm)) {
            $longContext .= "- No historical anchors.\n";
        } else {
            $slice = array_slice($longTerm, 0, 5, true);
            foreach ($slice as $key => $val) {
                $historyKey = str_replace('history:', '', $key);
                $valStr = is_array($val) ? json_encode($val) : (string) $val;
                $longContext .= "- {$historyKey}: {$valStr}\n";
            }
        }

        return "{$shortContext}\n{$mediumContext}\n{$longContext}";
    }

    public function captureActivity(AiAgent $agent, string $actionType, array $context = []): void
    {
        try {
            $today = now()->toDateString();
            $bucket = now()->format('Y-m-d H:00');

            $this->rememberShortTerm(
                $agent,
                "activity:{$bucket}:{$actionType}",
                [
                    'action' => $actionType,
                    'context' => $context,
                    'timestamp' => now()->toIso8601String(),
                ],
                3
            );

            $dailyKey = "stats:{$today}";
            $existing = $this->recallValue($agent, $dailyKey, 'medium', [
                'posts' => 0,
                'comments' => 0,
                'likes' => 0,
                'shares' => 0,
                'polls' => 0,
            ]);

            $counterMap = [
                'post_generated' => 'posts',
                'comment_created' => 'comments',
                'post_liked' => 'likes',
                'post_shared' => 'shares',
                'poll_created' => 'polls',
                'poll_voted' => 'polls',
            ];

            if (isset($counterMap[$actionType])) {
                $key = $counterMap[$actionType];
                $existing[$key] = (int) ($existing[$key] ?? 0) + 1;
            }

            $this->rememberMediumTerm($agent, $dailyKey, $existing, 6);

            if (!empty($context['topic'])) {
                $topicKey = 'topic:' . strtolower(trim((string) $context['topic']));
                $topicState = $this->recallValue($agent, $topicKey, 'medium', [
                    'mentions' => 0,
                    'last_action' => null,
                    'last_seen_at' => null,
                ]);
                $topicState['mentions'] = (int) ($topicState['mentions'] ?? 0) + 1;
                $topicState['last_action'] = $actionType;
                $topicState['last_seen_at'] = now()->toIso8601String();
                $this->rememberMediumTerm($agent, $topicKey, $topicState, 7);
            }

            if (!empty($context['historical_anchor'])) {
                $anchorKey = 'history:' . strtolower(trim((string) $context['historical_anchor']));
                $this->rememberLongTerm($agent, $anchorKey, [
                    'summary' => $context['summary'] ?? null,
                    'source' => $context['source'] ?? null,
                    'captured_at' => now()->toIso8601String(),
                ], 9);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to capture AI memory activity', [
                'agent_id' => $agent->id,
                'action' => $actionType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function normalizeMemoryValue($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return ['data' => (array) $value];
        }

        return ['text' => (string) $value];
    }

    protected function recallValue(AiAgent $agent, string $key, string $type, array $default = []): array
    {
        $memory = $agent->memories()
            ->where('type', $type)
            ->where('key', $key)
            ->valid()
            ->first();

        if (!$memory || !is_array($memory->value)) {
            return $default;
        }

        return array_merge($default, $memory->value);
    }
}
