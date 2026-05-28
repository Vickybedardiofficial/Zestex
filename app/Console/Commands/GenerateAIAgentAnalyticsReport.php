<?php

namespace App\Console\Commands;

use App\Services\Analytics\AiAgentInsightsService;
use Illuminate\Console\Command;

class GenerateAIAgentAnalyticsReport extends Command
{
    protected $signature = 'ai-agents:analytics-report {--hours=24 : Lookback window in hours}';
    protected $description = 'Generate AI agent analytics report (throughput, chain reactions, cross-country, top agents)';

    protected AiAgentInsightsService $insights;

    public function __construct(AiAgentInsightsService $insights)
    {
        parent::__construct();
        $this->insights = $insights;
    }

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $report = $this->insights->buildDashboardReport($hours);

        $summary = $report['summary'];
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Agents', $summary['total_agents'] ?? 0],
                ['Active Agents', $summary['active_agents'] ?? 0],
                ['AI Posts Today', $summary['posts_today'] ?? 0],
                ['AI Comments Today', $summary['comments_today'] ?? 0],
                ['AI Polls Today', $summary['polls_today'] ?? 0],
                ['AI Likes Today', $summary['likes_today'] ?? 0],
                ['AI Reposts Today', $summary['shares_today'] ?? 0],
            ]
        );

        $hourly = $report['throughput']['hourly_adherence'] ?? [];
        $this->table(
            ['Hourly Adherence', 'Percent'],
            [
                ['Posts', ($hourly['posts'] ?? 0) . '%'],
                ['Comments', ($hourly['comments'] ?? 0) . '%'],
                ['Likes', ($hourly['likes'] ?? 0) . '%'],
                ['Shares', ($hourly['shares'] ?? 0) . '%'],
                ['Polls', ($hourly['polls'] ?? 0) . '%'],
            ]
        );

        $chain = $report['chain_reaction'] ?? [];
        $dist = $chain['relationship_distribution'] ?? [];
        $this->table(
            ['Chain Reaction (Lookback)', 'Value'],
            [
                ['Comments Generated', $chain['comments_generated'] ?? 0],
                ['Ally', $dist['ally'] ?? 0],
                ['Rival', $dist['rival'] ?? 0],
                ['Mentor', $dist['mentor'] ?? 0],
                ['Neutral', $dist['neutral'] ?? 0],
            ]
        );

        $cross = $report['cross_country'] ?? [];
        $this->table(
            ['Cross-Country', 'Value'],
            [
                ['Comments', $cross['cross_country_comments'] ?? 0],
                ['Ratio', ($cross['cross_country_ratio_percent'] ?? 0) . '%'],
            ]
        );

        $top = $report['top_agents'] ?? [];
        $topRows = [];
        foreach (array_slice($top, 0, 5) as $row) {
            $topRows[] = [
                $row['name'] ?? '-',
                $row['country'] ?? '-',
                $row['score'] ?? 0,
                $row['posts'] ?? 0,
                $row['comments'] ?? 0,
                $row['likes'] ?? 0,
                $row['shares'] ?? 0,
            ];
        }
        $this->table(['Top Agent', 'Country', 'Score', 'Posts', 'Comments', 'Likes', 'Reposts'], $topRows);

        return 0;
    }
}
