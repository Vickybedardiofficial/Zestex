<?php

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Models\PostPoll;
use App\Services\AI\Activity\AgentScheduleManager;
use App\Services\AI\Interaction\InteractionManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIAgentInteract extends Command
{
    protected $signature = 'ai-agents:interact {--limit=100 : Max agents per run} {--votes-only : Run only poll voting cycle}';
    protected $description = 'Handle AI Agent interactions (Like, Share, Vote)';

    protected InteractionManager $interactionManager;
    protected AgentScheduleManager $scheduleManager;

    public function __construct(InteractionManager $interactionManager, AgentScheduleManager $scheduleManager)
    {
        parent::__construct();
        $this->interactionManager = $interactionManager;
        $this->scheduleManager = $scheduleManager;
    }

    public function handle(): int
    {
        if (!$this->isEngagementEnabled()) {
            $this->warn('AI engagement is disabled from Admin settings.');
            return 0;
        }

        $limit = max(1, (int) ($this->option('limit') ?: 100));
        $votesOnly = (bool) $this->option('votes-only');
        $pollsEnabled = (bool) config('agent-creation.polls.enabled', false);

        if ($votesOnly && !$pollsEnabled) {
            $this->warn('Poll voting is disabled in config(agent-creation.polls.enabled).');
            return 0;
        }

        $agents = AiAgent::active()
            ->with('user')
            ->inRandomOrder()
            ->limit($limit)
            ->get();
        $this->info("Starting interaction cycle for {$agents->count()} agents...");

        $stats = [
            'votes' => 0,
            'likes' => 0,
            'shares' => 0,
        ];

        foreach ($agents as $agent) {
            try {
                $engagementBoost = $this->getCountryWindowBoost($agent);
                $voteProbability = (int) config('agent-creation.polls.vote_probability', 0);
                if ($pollsEnabled && rand(1, 100) <= max(0, min(100, $voteProbability))) {
                    $candidatePolls = PostPoll::where('expires_at', '>', now())
                        ->whereHas('post.user', function ($q) {
                            $q->where('type', 'ai_agent');
                        })
                        ->latest('id')
                        ->limit(30)
                        ->get();

                    $activePoll = $candidatePolls->first(function ($poll) use ($agent) {
                        $votes = collect($poll->votes ?? []);
                        return !$votes->contains(function ($vote) use ($agent) {
                            return (int) ($vote['user_id'] ?? 0) === (int) $agent->user_id;
                        });
                    });

                    if ($activePoll) {
                        $this->interactionManager->voteInPoll($agent, $activePoll);
                        $this->line("VOTE {$agent->user->name} -> poll #{$activePoll->id}");
                        $stats['votes']++;
                    }
                }

                if (!$votesOnly && !$this->scheduleManager->hasReachedDailyLimit($agent, 'like')) {
                    $likeProbability = min(95, 45 + $engagementBoost);
                    if (rand(1, 100) <= $likeProbability) {
                        if ($this->interactionManager->performLike($agent)) {
                            $this->line("LIKE {$agent->user->name}");
                            $this->scheduleManager->incrementActivityCount($agent, 'like');
                            $stats['likes']++;
                        }
                    }
                }

                if (!$votesOnly && !$this->scheduleManager->hasReachedDailyLimit($agent, 'share')) {
                    $shareProbability = min(70, 12 + (int) floor($engagementBoost / 3));
                    if (rand(1, 100) <= $shareProbability) {
                        if ($this->interactionManager->performShare($agent)) {
                            $this->line("SHARE {$agent->user->name}");
                            $this->scheduleManager->incrementActivityCount($agent, 'share');
                            $stats['shares']++;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error("Interaction Failed for {$agent->id}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->table(
            ['Interaction', 'Count'],
            [
                ['Poll Votes', $stats['votes']],
                ['Likes', $stats['likes']],
                ['Reposts/Shares', $stats['shares']],
            ]
        );

        return 0;
    }

    protected function isEngagementEnabled(): bool
    {
        $value = DB::table('admin_settings')->where('key', 'ai_engagement_enabled')->value('value');
        if ($value === null) {
            return true;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }

    protected function getCountryWindowBoost(AiAgent $agent): int
    {
        $timezone = (string) config("countries.countries.{$agent->country}.timezone", 'UTC');
        $hour = (int) now($timezone)->format('H');

        if ($hour >= 5 && $hour <= 10) {
            return 25;
        }

        if ($hour >= 17 && $hour <= 22) {
            return 30;
        }

        return 0;
    }
}
