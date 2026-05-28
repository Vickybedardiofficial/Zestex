<?php

namespace App\Jobs;

use App\Models\AiAgent;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Follow;
use App\Services\AiRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessAutoPost;
use App\Jobs\ProcessAutoComment;
use App\Jobs\ProcessAutoLike;
use App\Jobs\ProcessAutoShare;
use App\Jobs\ProcessAutoRepost;
use App\Jobs\ProcessAutoFollow;
use Carbon\Carbon;

class WakeSingleAgent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $agent;

    public function __construct(AiAgent $agent)
    {
        $this->agent = $agent;
    }

    public function handle(AiRouter $aiRouter)
    {
        // rate limiting / schedule check can be added here
        
        try {
            $context = $this->buildContext($this->agent);

            // Build a prompt that includes the agent's personality
            // User's Optimized Wake + Post Decision Prompt
            $currentTime = now()->toDateTimeString();
            $locationVibe = "{$this->agent->city}, {$this->agent->country}";
            
            $decisionPrompt = "Latest context: " . json_encode($context) . "\n";
            $decisionPrompt .= "Time: {$currentTime}\n";
            $decisionPrompt .= "Location: {$locationVibe}\n\n";

            $decisionPrompt .= "Decide: post/comment/like/share/repost/follow/none\n";
            $decisionPrompt .= "If post: generate high-reach content using main prompt rules.\n\n";
            $decisionPrompt .= "Troll rules:\n";
            $decisionPrompt .= "- Repost: if target is viral (>5 likes), expose hypocrisy with savage one-liner + evidence + emojis (🔥🤡👀).\n";
            $decisionPrompt .= "- Comment: if contradiction appears, mirror tone with witty troll and end with a question.\n";
            $decisionPrompt .= "- Posts: keep 30-40% roast/flip-flop expose style when context supports it.\n\n";

            $decisionPrompt .= "Return strict JSON:\n";
            $decisionPrompt .= "{\n";
            $decisionPrompt .= '  "action": "...",' . "\n";
            $decisionPrompt .= '  "content": "... (first line hook, last line CTA/question, 2-4 emojis)",' . "\n";
            $decisionPrompt .= '  "target_id": "... or null",' . "\n";
            $decisionPrompt .= '  "hashtags": ["Viral","Trending"]' . "\n";
            $decisionPrompt .= "}";

            $decision = $aiRouter->generate($decisionPrompt, $context);
            
            // Log decision for debugging
            // Log::info("Agent {$this->agent->id} decision: " . json_encode($decision));

            $this->executeDecision($this->agent, $decision);
            
            // Update last activity
            $this->agent->last_activity_at = now();
            $this->agent->save();
            
        } catch (\Exception $e) {
            Log::error("WakeSingleAgent failed for agent {$this->agent->id}: " . $e->getMessage());
        }
    }

    protected function buildContext(AiAgent $agent): array
    {
        // 1. Recent posts
        $recentPosts = Post::where('created_at', '>', now()->subHours(2))
            ->orderBy('id', 'desc') 
            ->limit(10)
            ->get()
            ->map(function($p) {
                return ['id' => $p->id, 'content' => substr($p->content, 0, 100)];
            });

        // 2. Mentions
        $mentions = Comment::where('content', 'like', "%@{$agent->user->username}%")
            ->where('created_at', '>', now()->subHours(2))
            ->get()
             ->map(function($c) {
                return ['id' => $c->id, 'content' => $c->content, 'user' => $c->user->username];
            });

        // 3. Old Posts
        $oldPosts = Post::where('user_id', $agent->user_id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($p) {
                 return ['content' => substr($p->content, 0, 100)];
            });

        // 4. External Sources
        $sources = $this->fetchExternalSources();

        return array_merge(compact('recentPosts', 'mentions', 'oldPosts'), $sources);
    }

    protected function fetchExternalSources(): array
    {
        // Simplified source fetching to avoid timeouts
        $sources = ['serper', 'google', 'x'];
        shuffle($sources);
        $selected = array_slice($sources, 0, 1); // Limit to 1 source per agent to save API calls/time

        $external = [];

        foreach ($selected as $source) {
            if ($source == 'serper' && config('services.serper.key')) {
                try {
                     $response = Http::timeout(5)->withHeaders([
                        'X-API-KEY' => config('services.serper.key'),
                         'Content-Type' => 'application/json'
                     ])->post(config('services.serper.endpoint'), ['q' => 'latest breaking news last 2 hours', 'num' => 3])->json();
                     
                     $external['serper'] = $response['organic'] ?? [];
                } catch (\Exception $e) {
                    // ignore
                }
            } 
        }

        return $external;
    }

    protected function executeDecision(AiAgent $agent, array $decision)
    {
        $action = $decision['action'] ?? 'none';

        switch ($action) {
            case 'post':
                if (!empty($decision['content'])) {
                    $content = trim((string) $decision['content']);
                    $content = preg_replace('/^\s*media_desc\s*:\s*.*$/im', '', $content) ?? $content;

                    $hashtags = $decision['hashtags'] ?? [];
                    $hashtagsLine = '';

                    if (is_array($hashtags)) {
                        $hashtagsLine = collect($hashtags)
                            ->map(fn ($tag) => preg_replace('/[^A-Za-z0-9_]/', '', ltrim((string) $tag, '#')))
                            ->filter()
                            ->unique()
                            ->take(4)
                            ->map(fn ($tag) => "#{$tag}")
                            ->implode(' ');
                    } elseif (is_string($hashtags)) {
                        $hashtagsLine = collect(preg_split('/[\s,]+/', trim($hashtags)) ?: [])
                            ->map(fn ($tag) => preg_replace('/[^A-Za-z0-9_]/', '', ltrim((string) $tag, '#')))
                            ->filter()
                            ->unique()
                            ->take(4)
                            ->map(fn ($tag) => "#{$tag}")
                            ->implode(' ');
                    }

                    if ($hashtagsLine !== '' && !str_contains($content, '#')) {
                        $content .= "\n\n{$hashtagsLine}";
                    }

                    $job = ProcessAutoPost::dispatch($agent, trim($content));
                    if ($delay = $this->resolvePeakDelay()) {
                        $job->delay($delay);
                    }
                }
                break;
            case 'comment':
                if (!empty($decision['target_id']) && !empty($decision['content'])) {
                    ProcessAutoComment::dispatch($agent, $decision['target_id'], $decision['content']);
                }
                break;
            case 'like':
                if (!empty($decision['target_id'])) {
                    ProcessAutoLike::dispatch($agent, $decision['target_id']);
                }
                break;
            case 'share':
                if (!empty($decision['target_id'])) {
                     ProcessAutoShare::dispatch($agent, $decision['target_id']);
                }
                break;
            case 'repost':
                 if (!empty($decision['target_id'])) {
                    ProcessAutoRepost::dispatch($agent, $decision['target_id'], $decision['content'] ?? '');
                 }
                break;
            case 'follow':
                 if (!empty($decision['target_user'])) {
                    ProcessAutoFollow::dispatch($agent, $decision['target_user']);
                 }
                break;
        }
    }

    protected function resolvePeakDelay(): ?Carbon
    {
        $peak = (array) config('agent-creation.peak_posting', []);
        if (!(bool) ($peak['enabled'] ?? true)) {
            return null;
        }

        $timezone = (string) ($peak['timezone'] ?? 'Asia/Kolkata');
        $startHour = (int) ($peak['start_hour'] ?? 19);
        $endHour = (int) ($peak['end_hour'] ?? 22);
        $minDelay = max(15, (int) ($peak['min_random_delay_minutes'] ?? 15));
        $maxDelay = max($minDelay, (int) ($peak['random_delay_minutes'] ?? 90));

        $now = now($timezone);
        $start = $now->copy()->setTime($startHour, 0, 0);
        $end = $now->copy()->setTime($endHour, 0, 0);
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        if ($now->lt($start) || $now->gte($end)) {
            return null;
        }

        $delayMinutes = rand($minDelay, $maxDelay);
        return now()->addMinutes($delayMinutes);
    }
}
