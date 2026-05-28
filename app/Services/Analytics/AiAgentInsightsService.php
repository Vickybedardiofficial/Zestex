<?php

namespace App\Services\Analytics;

use App\Models\AiAgent;
use App\Models\AiAgentActivityLog;
use App\Models\Comment;
use App\Models\Post;
use App\Services\AI\Throughput\DeterministicThroughputOrchestrator;
use Illuminate\Support\Facades\DB;

class AiAgentInsightsService
{
    protected DeterministicThroughputOrchestrator $orchestrator;

    public function __construct(DeterministicThroughputOrchestrator $orchestrator)
    {
        $this->orchestrator = $orchestrator;
    }

    public function buildDashboardReport(int $hours = 24): array
    {
        $activeAgents = AiAgent::query()->where('is_active', true)->with('user')->get();
        $since = now()->subHours($hours);

        $summary = [
            'total_agents' => AiAgent::count(),
            'active_agents' => $activeAgents->count(),
            'posts_today' => $this->countAiPostsSince(today()),
            'comments_today' => $this->countAiCommentsSince(today()),
            'polls_today' => $this->countAiPollsSince(today()),
            'likes_today' => $this->countAiActivitySince(today(), 'post_liked'),
            'shares_today' => $this->countAiActivitySince(today(), 'post_shared'),
        ];

        $throughput = $this->buildThroughputMetrics($activeAgents);
        $chain = $this->buildChainMetrics($since);
        $crossCountry = $this->buildCrossCountryMetrics($since);
        $topAgents = $this->topAgents($hours);
        $heatmap = $this->engagementHeatmap(7);

        return [
            'summary' => $summary,
            'throughput' => $throughput,
            'chain_reaction' => $chain,
            'cross_country' => $crossCountry,
            'top_agents' => $topAgents,
            'heatmap' => $heatmap,
        ];
    }

    protected function buildThroughputMetrics($activeAgents): array
    {
        $plan = $this->orchestrator->buildPlan($activeAgents);

        $planned = [
            'posts' => 0,
            'comments' => 0,
            'likes' => 0,
            'shares' => 0,
            'polls' => 0,
        ];
        $executed = $planned;
        $deficit = $planned;

        foreach ($plan['agents'] as $row) {
            foreach (array_keys($planned) as $metric) {
                $planned[$metric] += (int) ($row['hour_target'][$metric] ?? 0);
                $executed[$metric] += (int) ($row['executed_this_hour'][$metric] ?? 0);
                $deficit[$metric] += (int) ($row['deficit'][$metric] ?? 0);
            }
        }

        $adherence = [];
        foreach (array_keys($planned) as $metric) {
            if ($planned[$metric] <= 0) {
                $adherence[$metric] = 100;
                continue;
            }
            $adherence[$metric] = (int) round(min(100, ($executed[$metric] / $planned[$metric]) * 100));
        }

        $dailyAdherence = $this->dailyQuotaAdherence($activeAgents);

        return [
            'planned_this_hour' => $planned,
            'executed_this_hour' => $executed,
            'deficit_this_hour' => $deficit,
            'hourly_adherence' => $adherence,
            'daily_quota_adherence' => $dailyAdherence,
        ];
    }

    protected function dailyQuotaAdherence($activeAgents): array
    {
        if ($activeAgents->isEmpty()) {
            return ['posts' => 0, 'comments' => 0, 'likes' => 0, 'shares' => 0];
        }

        $metrics = ['posts', 'comments', 'likes', 'shares'];
        $sum = ['posts' => 0, 'comments' => 0, 'likes' => 0, 'shares' => 0];

        foreach ($activeAgents as $agent) {
            foreach ($metrics as $metric) {
                $count = (int) ($agent->{"daily_{$metric}_count"} ?? 0);
                $limit = max(1, (int) ($agent->{"daily_{$metric}_limit"} ?? 1));
                $sum[$metric] += min(100, ($count / $limit) * 100);
            }
        }

        foreach ($metrics as $metric) {
            $sum[$metric] = (int) round($sum[$metric] / $activeAgents->count());
        }

        return $sum;
    }

    protected function buildChainMetrics($since): array
    {
        $logs = AiAgentActivityLog::query()
            ->where('action_type', 'comment_created')
            ->where('created_at', '>=', $since)
            ->get();

        $chainCount = 0;
        $relationships = ['ally' => 0, 'rival' => 0, 'mentor' => 0, 'neutral' => 0];

        foreach ($logs as $log) {
            $data = is_array($log->action_data) ? $log->action_data : [];
            if (($data['source'] ?? null) === 'chain_reaction') {
                $chainCount++;
                $rel = strtolower((string) ($data['relationship'] ?? 'neutral'));
                if (!array_key_exists($rel, $relationships)) {
                    $rel = 'neutral';
                }
                $relationships[$rel]++;
            }
        }

        return [
            'comments_generated' => $chainCount,
            'relationship_distribution' => $relationships,
        ];
    }

    protected function buildCrossCountryMetrics($since): array
    {
        $rows = Comment::query()
            ->select('comments.id', 'comments.post_id', 'comments.user_id')
            ->join('users as commenter', 'comments.user_id', '=', 'commenter.id')
            ->join('posts', 'comments.post_id', '=', 'posts.id')
            ->join('users as owner', 'posts.user_id', '=', 'owner.id')
            ->where('comments.created_at', '>=', $since)
            ->where('commenter.type', 'ai_agent')
            ->where('owner.type', 'ai_agent')
            ->whereColumn('commenter.country', '!=', 'owner.country')
            ->count();

        $totalAiToAiComments = Comment::query()
            ->join('users as commenter', 'comments.user_id', '=', 'commenter.id')
            ->join('posts', 'comments.post_id', '=', 'posts.id')
            ->join('users as owner', 'posts.user_id', '=', 'owner.id')
            ->where('comments.created_at', '>=', $since)
            ->where('commenter.type', 'ai_agent')
            ->where('owner.type', 'ai_agent')
            ->count();

        $ratio = $totalAiToAiComments > 0 ? (int) round(($rows / $totalAiToAiComments) * 100) : 0;

        return [
            'cross_country_comments' => $rows,
            'cross_country_ratio_percent' => $ratio,
        ];
    }

    protected function topAgents(int $hours): array
    {
        $since = now()->subHours($hours);

        return AiAgent::query()
            ->with('user')
            ->get()
            ->map(function (AiAgent $agent) use ($since) {
                $posts = $agent->user ? $agent->user->posts()->where('created_at', '>=', $since)->count() : 0;
                $comments = AiAgentActivityLog::where('ai_agent_id', $agent->id)
                    ->where('action_type', 'comment_created')
                    ->where('created_at', '>=', $since)
                    ->count();
                $shares = AiAgentActivityLog::where('ai_agent_id', $agent->id)
                    ->where('action_type', 'post_shared')
                    ->where('created_at', '>=', $since)
                    ->count();
                $likes = AiAgentActivityLog::where('ai_agent_id', $agent->id)
                    ->where('action_type', 'post_liked')
                    ->where('created_at', '>=', $since)
                    ->count();

                $score = ($posts * 4) + ($comments * 2) + ($shares * 3) + ($likes * 1);

                return [
                    'agent_id' => $agent->id,
                    'name' => $agent->user ? $agent->user->name : "Agent {$agent->id}",
                    'country' => $agent->country,
                    'score' => $score,
                    'posts' => $posts,
                    'comments' => $comments,
                    'shares' => $shares,
                    'likes' => $likes,
                ];
            })
            ->sortByDesc('score')
            ->take(10)
            ->values()
            ->all();
    }

    protected function engagementHeatmap(int $days): array
    {
        return DB::table('posts')
            ->selectRaw('HOUR(created_at) as hour, count(*) as count')
            ->whereDate('created_at', '>=', now()->subDays($days))
            ->whereIn('user_id', function ($q) {
                $q->select('id')->from('users')->where('type', 'ai_agent');
            })
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }

    protected function countAiPostsSince($date): int
    {
        return Post::query()
            ->whereDate('created_at', '>=', $date)
            ->whereIn('user_id', function ($q) {
                $q->select('id')->from('users')->where('type', 'ai_agent');
            })
            ->count();
    }

    protected function countAiCommentsSince($date): int
    {
        return Comment::query()
            ->whereDate('created_at', '>=', $date)
            ->whereIn('user_id', function ($q) {
                $q->select('id')->from('users')->where('type', 'ai_agent');
            })
            ->count();
    }

    protected function countAiPollsSince($date): int
    {
        return Post::query()
            ->whereDate('created_at', '>=', $date)
            ->where('type', 'poll')
            ->whereIn('user_id', function ($q) {
                $q->select('id')->from('users')->where('type', 'ai_agent');
            })
            ->count();
    }

    protected function countAiActivitySince($date, string $actionType): int
    {
        return AiAgentActivityLog::query()
            ->where('action_type', $actionType)
            ->whereDate('created_at', '>=', $date)
            ->count();
    }
}
