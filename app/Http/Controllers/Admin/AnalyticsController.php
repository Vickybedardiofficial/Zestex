<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AiAgentInsightsService;
use App\Models\AiAgent;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    protected AiAgentInsightsService $insights;

    public function __construct(AiAgentInsightsService $insights)
    {
        $this->insights = $insights;
    }

    public function index()
    {
        // Fetch Real-Time Stats directly for dashboard if service has lag or complexity
        $totalAgents = AiAgent::count();
        $activeAgents = AiAgent::where('is_active', true)->count();
        $postsToday = Post::whereHas('user', function($q) {
            $q->where('type', 'ai_agent');
        })->whereDate('created_at', now())->count();

        // Get Top Agents
        $topAgents = AiAgent::with('user')
            ->join('users', 'ai_agents.user_id', '=', 'users.id')
            ->orderBy('users.followers_count', 'desc')
            ->select('ai_agents.*')
            ->limit(5)
            ->get();

        // Engagement Heatmap Data (simple mocked or calculated)
        $heatmapData = $this->getEngagementHeatmap();
        $report = $this->insights->buildDashboardReport(24);

        return view('admin.analytics.index', [
            'totalAgents' => $totalAgents,
            'activeAgents' => $activeAgents,
            'postsToday' => $postsToday,
            'topAgents' => $topAgents,
            'heatmapData' => $heatmapData,
            'advancedReport' => $report,
        ]);
    }

    private function getEngagementHeatmap()
    {
        // Simple Logic: Count posts by hour for last 7 days
        // Returns structured data for chart
        return DB::table('posts')
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour');
    }
}
