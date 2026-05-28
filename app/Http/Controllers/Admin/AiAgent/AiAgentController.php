<?php

namespace App\Http\Controllers\Admin\AiAgent;

use App\Models\User;
use App\Models\AiAgent;
use App\Enums\User\UserType;
use App\Enums\User\UserStatus;
use App\Support\Views\Flash;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiAgentController extends Controller
{
    private $filters = [];

    public function index(Request $request)
    {
        $personalityType = $request->string('personality')->value;
        $country = $request->string('country')->value;
        $status = $request->string('status')->value;

        $this->filters = [
            'search' => $request->string('search')->value,
            'personality' => $personalityType,
            'country' => $country,
            'status' => $status,
        ];

        $agents = AiAgent::with('user')
            ->when(!empty($this->filters['search']), function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('first_name', 'like', "%{$this->filters['search']}%")
                      ->orWhere('last_name', 'like', "%{$this->filters['search']}%")
                      ->orWhere('username', 'like', "%{$this->filters['search']}%");
                });
            })
            ->when(!empty($this->filters['personality']), function ($query) {
                $query->where('personality_type', $this->filters['personality']);
            })
            ->when(!empty($this->filters['country']), function ($query) {
                $query->where('country', $this->filters['country']);
            })
            ->when($this->filters['status'] === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($this->filters['status'] === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->latest()
            ->paginate(10);

        // Part 1: Get Auto-Creation Status
        $autoCreationEnabled = DB::table('admin_settings')->where('key', 'auto_agent_creation_enabled')->value('value');
        $autoCreationEnabled = $this->parseBooleanSetting($autoCreationEnabled) === true;
        $engagementEnabled = DB::table('admin_settings')->where('key', 'ai_engagement_enabled')->value('value');
        $engagementEnabled = $this->parseBooleanSetting($engagementEnabled);
        $engagementEnabled = $engagementEnabled === null ? true : $engagementEnabled;

        return view('admin::ai-agents.index.index', [
            'agents' => $agents,
            'filters' => $this->filters,
            'autoCreationEnabled' => $autoCreationEnabled,
            'engagementEnabled' => $engagementEnabled,
        ]);
    }

    public function create()
    {
        return view('admin::ai-agents.create.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'bio' => 'nullable|string|max:500',
            'personality_type' => 'required|string',
            'country' => 'required|string|max:2',
            'language' => 'required|string|max:10',
            'posting_frequency' => 'required|integer|min:1|max:50',
            'engagement_level' => 'required|integer|min:1|max:5',
            'ai_provider' => 'nullable|string',
            'image_provider' => 'nullable|string',
            'topics' => 'nullable|array',
        ]);

        // Create user account for AI agent
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'bio' => $validated['bio'] ?? '',
            'email' => $validated['username'] . '@aiagent.local',
            'password' => Hash::make(Str::random(32)),
            'type' => UserType::AI_AGENT->value,
            'status' => UserStatus::ACTIVE->value,
            'country' => $validated['country'],
            'language' => $validated['language'],
        ]);

        // Create AI agent configuration
        $aiAgent = AiAgent::create([
            'user_id' => $user->id,
            'personality_type' => $validated['personality_type'],
            'country' => $validated['country'],
            'language' => $validated['language'],
            'posting_frequency' => $validated['posting_frequency'],
            'engagement_level' => $validated['engagement_level'],
            'ai_provider' => $validated['ai_provider'] ?? null,
            'image_provider' => $validated['image_provider'] ?? null,
            'topics' => $validated['topics'] ?? [],
            'is_active' => true,
        ]);

        // Log activity
        $aiAgent->logActivity('agent_created', [
            'admin_id' => me()->id,
            'admin_name' => me()->name,
        ]);

        return redirect()
            ->route('admin.ai-agents.show', $aiAgent->id)
            ->with('flashMessage', (new Flash(content: 'AI Agent created successfully!'))->get());
    }

    public function show(int $id)
    {
        $agent = AiAgent::with(['user', 'activityLogs'])->findOrFail($id);
        
        // Get recent posts by this agent
        $recentPosts = $agent->user->posts()->latest()->limit(10)->get();
        
        // Get activity statistics
        $stats = [
            'total_posts' => $agent->user->publications_count,
            'total_followers' => $agent->user->followers_count,
            'total_following' => $agent->user->following_count,
            'total_activities' => $agent->activityLogs()->count(),
        ];

        return view('admin::ai-agents.show.index', [
            'agent' => $agent,
            'recentPosts' => $recentPosts,
            'stats' => $stats,
        ]);
    }

    public function edit(int $id)
    {
        $agent = AiAgent::with('user')->findOrFail($id);
        
        return view('admin::ai-agents.edit.index', [
            'agent' => $agent,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $agent = AiAgent::with('user')->findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:500',
            'personality_type' => 'required|string',
            'country' => 'required|string|max:2',
            'language' => 'required|string|max:10',
            'posting_frequency' => 'required|integer|min:1|max:50',
            'engagement_level' => 'required|integer|min:1|max:5',
            'ai_provider' => 'nullable|string',
            'image_provider' => 'nullable|string',
            'topics' => 'nullable|array',
            'peak_active_hour' => 'nullable|integer|min:0|max:23',
            'specific_topics' => 'nullable|array',
            'is_manual_override' => 'nullable|boolean',
            
            // Part 9: Admin Controls
            'blocked_topics' => 'nullable|array',
            'manual_instruction' => 'nullable|string',
            'post_frequency_modifier' => 'nullable|numeric|min:0.1|max:5.0',
        ]);

        // Update user
        $agent->user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'bio' => $validated['bio'] ?? '',
            'country' => $validated['country'],
            'language' => $validated['language'],
        ]);

        // Update AI agent configuration
        $agent->update([
            'personality_type' => $validated['personality_type'],
            'country' => $validated['country'],
            'language' => $validated['language'],
            'posting_frequency' => $validated['posting_frequency'],
            'engagement_level' => $validated['engagement_level'],
            'ai_provider' => $validated['ai_provider'] ?? null,
            'image_provider' => $validated['image_provider'] ?? null,
            'topics' => $validated['topics'] ?? [],
            'peak_active_hour' => $validated['peak_active_hour'] ?? null,
            'specific_topics' => $validated['specific_topics'] ?? [],
            'is_manual_override' => $request->has('is_manual_override'),
            
            // Part 9
            'blocked_topics' => $validated['blocked_topics'] ?? [],
            'manual_instruction' => $validated['manual_instruction'] ?? null,
            'post_frequency_modifier' => $validated['post_frequency_modifier'] ?? 1.0,
        ]);

        // Log activity
        $agent->logActivity('agent_updated', [
            'admin_id' => me()->id,
            'admin_name' => me()->name,
        ]);

        return redirect()
            ->route('admin.ai-agents.show', $agent->id)
            ->with('flashMessage', (new Flash(content: 'AI Agent updated successfully!'))->get());
    }

    public function destroy(int $id)
    {
        $agent = AiAgent::with('user')->findOrFail($id);
        
        // Delete user (cascade will delete agent)
        $agent->user->delete();

        return redirect()
            ->route('admin.ai-agents.index')
            ->with('flashMessage', (new Flash(content: 'AI Agent deleted successfully!'))->get());
    }

    public function toggleStatus(int $id)
    {
        $agent = AiAgent::findOrFail($id);
        
        $newStatus = !$agent->is_active;
        $agent->update(['is_active' => $newStatus]);

        // Log activity
        $agent->logActivity($newStatus ? 'agent_activated' : 'agent_deactivated', [
            'admin_id' => me()->id,
            'admin_name' => me()->name,
        ]);

        $message = $newStatus ? 'AI Agent activated successfully!' : 'AI Agent deactivated successfully!';

        return redirect()
            ->route('admin.ai-agents.show', $agent->id)
            ->with('flashMessage', (new Flash(content: $message))->get());
    }
    public function toggleAutoCreation(Request $request)
    {
        Log::info('Toggling auto-creation triggered. Params:', $request->all());
        $enabled = $request->boolean('enabled');
        
        DB::table('admin_settings')->updateOrInsert(
            ['key' => 'auto_agent_creation_enabled'],
            [
                'value' => $enabled ? '1' : '0',
                'type' => 'boolean',
                'updated_at' => now(),
            ]
        );

        if ($enabled) {
            try {
                $this->triggerAutoCreateInBackground();
            } catch (\Exception $e) {
                Log::error('Auto-create command failed on toggle', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = $enabled ? 'Auto-Creation enabled! Agents are being created...' : 'Auto-Creation disabled successfully!';

        return redirect()
            ->route('admin.ai-agents.index')
            ->with('flashMessage', (new Flash(content: $message))->get());
    }

    protected function triggerAutoCreateInBackground(): void
    {
        $php = escapeshellarg(PHP_BINARY ?: 'php');
        $artisan = escapeshellarg(base_path('artisan'));
        $command = 'ai-agents:auto-create --count=1';

        if (str_starts_with(strtoupper(PHP_OS), 'WIN')) {
            // Fire-and-forget on Windows so request returns instantly.
            pclose(popen("start /B \"\" {$php} {$artisan} {$command} > NUL 2>&1", 'r'));
            return;
        }

        exec("nohup {$php} {$artisan} {$command} > /dev/null 2>&1 &");
    }

    public function toggleEngagement(Request $request)
    {
        $enabled = $request->boolean('enabled');

        DB::table('admin_settings')->updateOrInsert(
            ['key' => 'ai_engagement_enabled'],
            [
                'value' => $enabled ? '1' : '0',
                'type' => 'boolean',
                'updated_at' => now(),
            ]
        );

        $message = $enabled
            ? 'AI engagement enabled (comments, likes, reposts, shares).'
            : 'AI engagement disabled (comments, likes, reposts, shares).';

        return redirect()
            ->route('admin.ai-agents.index')
            ->with('flashMessage', (new Flash(content: $message))->get());
    }

    public function analytics()
    {
        // 1. Top 5 Popular Agents (by Followers)
        $topAgents = AiAgent::with('user')
            ->join('users', 'ai_agents.user_id', '=', 'users.id')
            ->orderBy('users.followers_count', 'desc')
            ->select('ai_agents.*')
            ->limit(5)
            ->get();

        // 2. Most Active Agents (Posts in last 24h)
        $mostActive = AiAgent::with('user')
            ->orderBy('daily_posts_count', 'desc')
            ->limit(5)
            ->get();

        // 3. Real User Interactions (Total Comments on Agent Posts by Real Users)
        // Approximate this by counting total comments on agent posts minus agent own comments
        $totalInteractions = DB::table('comments')
            ->join('posts', 'comments.post_id', '=', 'posts.id')
            ->join('ai_agents', 'posts.user_id', '=', 'ai_agents.user_id')
            ->whereNotIn('comments.user_id', function($query) {
                $query->select('user_id')->from('ai_agents');
            })
            ->count();

        // 4. Peak Activity Time (from logs)
        // Simplified: Just returning static data for now as logs might be sparse
        $peakTime = "20:00 - 23:00"; 

        return view('admin::ai-agents.analytics.index', [
            'topAgents' => $topAgents,
            'mostActive' => $mostActive,
            'totalInteractions' => $totalInteractions,
            'peakTime' => $peakTime
        ]);
    }

    private function parseBooleanSetting($value): ?bool
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
