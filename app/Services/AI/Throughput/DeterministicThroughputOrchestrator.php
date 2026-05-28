<?php

namespace App\Services\AI\Throughput;

use App\Models\AiAgent;
use App\Models\AiAgentActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeterministicThroughputOrchestrator
{
    /**
     * Build deterministic per-hour targets and deficits for active agents.
     */
    public function buildPlan(?Collection $agents = null): array
    {
        $agents = $agents ?: AiAgent::query()->where('is_active', true)->with('user')->get();

        $perAgent = [];
        $totals = [
            'posts' => 0,
            'comments' => 0,
            'likes' => 0,
            'shares' => 0,
            'polls' => 0,
        ];

        foreach ($agents as $agent) {
            $row = $this->planForAgent($agent);
            $perAgent[] = $row;

            $totals['posts'] += $row['deficit']['posts'];
            $totals['comments'] += $row['deficit']['comments'];
            $totals['likes'] += $row['deficit']['likes'];
            $totals['shares'] += $row['deficit']['shares'];
            $totals['polls'] += $row['deficit']['polls'];
        }

        return [
            'agents' => $perAgent,
            'totals' => $totals,
        ];
    }

    protected function planForAgent(AiAgent $agent): array
    {
        $tz = config("countries.countries.{$agent->country}.timezone", 'UTC');
        $now = Carbon::now($tz);
        $endOfDay = $now->copy()->endOfDay();
        $remainingHours = max(1, (int) ceil($now->floatDiffInHours($endOfDay)));
        $hourStartUtc = $now->copy()->startOfHour()->timezone('UTC');

        $remaining = [
            'posts' => max(0, (int) $agent->daily_posts_limit - (int) $agent->daily_posts_count),
            'comments' => max(0, (int) $agent->daily_comments_limit - (int) $agent->daily_comments_count),
            'likes' => max(0, (int) $agent->daily_likes_limit - (int) $agent->daily_likes_count),
            'shares' => max(0, (int) $agent->daily_shares_limit - (int) $agent->daily_shares_count),
            'polls' => $this->isPollDue($agent) ? 1 : 0,
        ];

        $hourTarget = [
            'posts' => (int) ceil($remaining['posts'] / $remainingHours),
            'comments' => (int) ceil($remaining['comments'] / $remainingHours),
            'likes' => (int) ceil($remaining['likes'] / $remainingHours),
            'shares' => (int) ceil($remaining['shares'] / $remainingHours),
            'polls' => min(1, (int) ceil($remaining['polls'] / $remainingHours)),
        ];

        $executedThisHour = [
            'posts' => $this->countActivity($agent, 'post_generated', $hourStartUtc),
            'comments' => $this->countActivity($agent, 'comment_created', $hourStartUtc),
            'likes' => $this->countActivity($agent, 'post_liked', $hourStartUtc),
            'shares' => $this->countActivity($agent, 'post_shared', $hourStartUtc),
            'polls' => $this->countActivity($agent, 'poll_created', $hourStartUtc),
        ];

        $deficit = [
            'posts' => max(0, $hourTarget['posts'] - $executedThisHour['posts']),
            'comments' => max(0, $hourTarget['comments'] - $executedThisHour['comments']),
            'likes' => max(0, $hourTarget['likes'] - $executedThisHour['likes']),
            'shares' => max(0, $hourTarget['shares'] - $executedThisHour['shares']),
            'polls' => max(0, $hourTarget['polls'] - $executedThisHour['polls']),
        ];

        return [
            'agent_id' => $agent->id,
            'user_id' => $agent->user_id,
            'timezone' => $tz,
            'remaining_hours' => $remainingHours,
            'remaining_daily' => $remaining,
            'hour_target' => $hourTarget,
            'executed_this_hour' => $executedThisHour,
            'deficit' => $deficit,
        ];
    }

    protected function countActivity(AiAgent $agent, string $actionType, Carbon $hourStartUtc): int
    {
        return AiAgentActivityLog::query()
            ->where('ai_agent_id', $agent->id)
            ->where('action_type', $actionType)
            ->where('created_at', '>=', $hourStartUtc)
            ->count();
    }

    protected function isPollDue(AiAgent $agent): bool
    {
        if (!(bool) config('agent-creation.polls.enabled', false)) {
            return false;
        }

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

        $last = $this->latestCarbon(
            $this->normalizeToCarbon($lastPollPostAt),
            $this->normalizeToCarbon($lastPollLogAt)
        );

        if (!$last) {
            return true;
        }

        $min = (int) config('agent-creation.polls.cooldown_days_min', 2);
        $max = (int) config('agent-creation.polls.cooldown_days_max', 3);
        if ($max <= $min) {
            $cooldown = max(1, $min);
        } else {
            $seed = $agent->id . '|' . $last->toDateString();
            $hash = (int) sprintf('%u', crc32($seed));
            $cooldown = $min + ($hash % ($max - $min + 1));
        }

        return $last->diffInHours(now()) >= ($cooldown * 24);
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

    protected function latestCarbon(?Carbon $first, ?Carbon $second): ?Carbon
    {
        if ($first && $second) {
            return $first->greaterThan($second) ? $first : $second;
        }

        return $first ?: $second;
    }
}
