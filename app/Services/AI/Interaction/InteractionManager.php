<?php

namespace App\Services\AI\Interaction;

use App\Models\AiAgent;
use App\Models\Post;
use App\Models\PostPoll;
use App\Services\AI\Memory\AgentMemoryService;
use Illuminate\Support\Facades\Log;

class InteractionManager
{
    protected AgentMemoryService $memoryService;
    protected PostViewRecorder $postViewRecorder;

    public function __construct(AgentMemoryService $memoryService, PostViewRecorder $postViewRecorder)
    {
        $this->memoryService = $memoryService;
        $this->postViewRecorder = $postViewRecorder;
    }

    public function voteInPoll(AiAgent $agent, PostPoll $poll): void
    {
        $poll->loadMissing('post.user');
        if (!$poll->post || !$poll->post->user) {
            return;
        }

        if ($this->isAiOnlyTargetMode() && !$poll->post->user->isAiAgent()) {
            return;
        }

        $this->postViewRecorder->recordForUser((int) $agent->user_id, (int) $poll->post->id);

        $choices = $poll->choices ?? [];
        if (empty($choices)) {
            return;
        }

        $votes = $poll->votes ?? [];
        foreach ($votes as $vote) {
            if ((int) ($vote['user_id'] ?? 0) === (int) $agent->user_id) {
                return;
            }
        }

        $choiceIndex = $this->determineVoteChoice($agent, $choices);
        $votes[] = [
            'user_id' => $agent->user_id,
            'choice_index' => $choiceIndex,
            'voted_at' => now()->timestamp,
        ];

        $poll->update(['votes' => $votes]);
        $agent->logActivity('poll_voted', ['poll_id' => $poll->id, 'choice' => $choices[$choiceIndex] ?? null]);
        $this->memoryService->captureActivity($agent, 'poll_voted', [
            'topic' => 'poll',
            'poll_id' => $poll->id,
        ]);
    }

    public function performLike(AiAgent $agent, ?Post $targetPost = null): bool
    {
        $post = $targetPost;
        if ($post) {
            $post->loadMissing('user');
            if (!$post->user || (int) $post->user_id === (int) $agent->user_id) {
                return false;
            }

            if ($this->isAiOnlyTargetMode() && !$post->user->isAiAgent()) {
                return false;
            }
        } else {
            $query = Post::where('user_id', '!=', $agent->user_id)
                ->where('status', 'active')
                ->where('created_at', '>=', now()->subHours(24));

            if ($this->isAiOnlyTargetMode()) {
                $query->whereHas('user', function ($q) {
                    $q->where('type', 'ai_agent');
                });
            }

            $post = $query->inRandomOrder()->first();
        }

        if (!$post) {
            return false;
        }

        $this->postViewRecorder->recordForUser((int) $agent->user_id, (int) $post->id);

        $reaction = $post->reactions()->where('unified_id', '1f44d')->first();
        if (!$reaction) {
            $reaction = $post->reactions()->create([
                'unified_id' => '1f44d',
                'users' => [],
                'reactions_count' => 0,
                'native_symbol' => null,
            ]);
        }

        $users = collect($reaction->users ?? [])->map(fn ($id) => (int) $id);
        if ($users->contains((int) $agent->user_id)) {
            return false;
        }

        $users->push((int) $agent->user_id);
        $reaction->users = $users->unique()->values()->all();
        $reaction->reactions_count = count($reaction->users);
        $reaction->save();

        $agent->logActivity('post_liked', ['post_id' => $post->id]);
        $this->memoryService->captureActivity($agent, 'post_liked', [
            'topic' => 'engagement',
            'post_id' => $post->id,
        ]);
        return true;
    }

    public function performShare(AiAgent $agent, ?Post $targetPost = null): bool
    {
        $post = $targetPost;
        if ($post) {
            $post->loadMissing('user');
            if (!$post->user || (int) $post->user_id === (int) $agent->user_id) {
                return false;
            }

            if ($this->isAiOnlyTargetMode() && !$post->user->isAiAgent()) {
                return false;
            }
        } else {
            $query = Post::where('user_id', '!=', $agent->user_id)
                ->where('status', 'active')
                ->where('created_at', '>=', now()->subDays(2));

            if ($this->isAiOnlyTargetMode()) {
                $query->whereHas('user', function ($q) {
                    $q->where('type', 'ai_agent');
                });
            }

            $post = $query->inRandomOrder()->first();
        }

        if (!$post) {
            return false;
        }

        $this->postViewRecorder->recordForUser((int) $agent->user_id, (int) $post->id);

        $comment = $this->generateShareComment($post, $agent);
        $rootPostId = $this->resolveRootPostId($post);

        if (Post::query()
            ->where('user_id', $agent->user_id)
            ->where('content', $comment)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists()) {
            return false;
        }

        Post::create([
            'user_id' => $agent->user_id,
            'content' => $comment,
            'type' => 'text',
            'status' => 'active',
            'text_language' => '',
            'is_ai_generated' => true,
            'quote_post_id' => $rootPostId,
            'is_quoting' => true,
        ]);

        $agent->logActivity('post_shared', ['original_post_id' => $rootPostId]);
        $this->memoryService->captureActivity($agent, 'post_shared', [
            'topic' => 'engagement',
            'post_id' => $rootPostId,
        ]);
        return true;
    }

    protected function determineVoteChoice(AiAgent $agent, array $choices): int
    {
        $keywords = $this->getPersonalityKeywords($agent->personality_type);
        foreach ($choices as $index => $choice) {
            foreach ($keywords as $word) {
                if (stripos((string) $choice, $word) !== false) {
                    return (int) $index;
                }
            }
        }

        return (int) array_rand($choices);
    }

    protected function getPersonalityKeywords(string $type): array
    {
        return match ($type) {
            'political' => ['policy', 'government', 'nation', 'rights', 'vote'],
            'tech' => ['ai', 'future', 'data', 'innovation'],
            'sports' => ['win', 'team', 'game', 'match'],
            default => ['yes', 'agree', 'better'],
        };
    }

    protected function generateShareComment(Post $post, AiAgent $agent): string
    {
        $keywords = $this->extractPostKeywords((string) $post->content);
        $k1 = $keywords[0] ?? 'this trend';
        $k2 = $keywords[1] ?? 'execution';
        $k3 = $keywords[2] ?? 'public trust';
        $evidence = $this->buildEvidenceHint($post);
        $style = $this->pickRepostStyle($agent, $post);

        $questions = [
            'Which hard data changes your mind here?',
            'Do you still defend this after seeing the evidence?',
            'What exactly proves this is not pure spin?',
            'Which metric survives a 30-day reality check?',
            'Who is accountable if this collapses next week?',
        ];
        $tags = ['#HypocrisyCheck', '#Receipts', '#NoSpin', '#Debate', '#PublicView', '#TrendWatch'];
        shuffle($tags);
        $pickedTags = array_slice($tags, 0, 3);

        $seed = abs(crc32($agent->id . '|' . $post->id . '|' . now()->format('Y-m-d-H')));
        $q = $questions[$seed % count($questions)];

        $templates = match ($style) {
            'savage' => [
                "🔥 Hypocrisy check: they sell {$k1}, then hide when {$k2} fails on ground.\n🤡 Savage take: this is branding theatre, not delivery.\n📎 Evidence: {$evidence}\n{$q} {$pickedTags[0]} {$pickedTags[1]} {$pickedTags[2]}",
                "👀 Double-standard alert: loud claims on {$k1}, silent record on {$k2} and {$k3}.\n💀 Roast: same script, new headline, zero accountability.\n📎 Evidence: {$evidence}\n{$q} {$pickedTags[0]} {$pickedTags[1]} {$pickedTags[2]}",
            ],
            'forensic' => [
                "🔥 Hypocrisy check: promise curve up, execution curve down on {$k1}.\n🧪 Forensic read: the mismatch on {$k2} is too clear to ignore.\n📎 Evidence: {$evidence}\n{$q} {$pickedTags[0]} {$pickedTags[1]} {$pickedTags[2]}",
                "🤡 Contradiction map: narrative says progress, data on {$k2} and {$k3} says stall.\n⚔️ Roast level: facts are undefeated.\n📎 Evidence: {$evidence}\n{$q} {$pickedTags[0]} {$pickedTags[1]} {$pickedTags[2]}",
            ],
            default => [
                "👀 Hypocrisy check: headline celebrates {$k1}, reality flags {$k2}.\n🔥 Roast: spin is cheap, delivery is expensive.\n📎 Evidence: {$evidence}\n{$q} {$pickedTags[0]} {$pickedTags[1]} {$pickedTags[2]}",
                "💀 Double-standard watch: they demand trust while dodging proof on {$k1} and {$k3}.\n🤡 Savage but fair: show receipts or drop the hype.\n📎 Evidence: {$evidence}\n{$q} {$pickedTags[0]} {$pickedTags[1]} {$pickedTags[2]}",
            ],
        };

        return $templates[$seed % count($templates)];
    }

    protected function extractPostKeywords(string $content): array
    {
        $text = strtolower($content);
        $text = preg_replace('/https?:\\/\\/\\S+/i', ' ', $text);
        $text = preg_replace('/[^a-z0-9\\s]/i', ' ', (string) $text);
        $raw = array_values(array_filter(explode(' ', (string) $text), fn ($w) => strlen($w) >= 5));

        if (empty($raw)) {
            return ['this trend', 'execution', 'public trust'];
        }

        $counts = [];
        foreach ($raw as $word) {
            $counts[$word] = ($counts[$word] ?? 0) + 1;
        }
        arsort($counts);

        return array_slice(array_keys($counts), 0, 3);
    }

    protected function resolveRootPostId(Post $post): int
    {
        $visited = [];
        $current = $post;

        while ($current && $current->quote_post_id) {
            if (in_array($current->id, $visited, true)) {
                break;
            }

            $visited[] = $current->id;
            $next = Post::find($current->quote_post_id);
            if (!$next) {
                break;
            }

            $current = $next;
        }

        return (int) ($current?->id ?? $post->id);
    }

    protected function isAiOnlyTargetMode(): bool
    {
        return (bool) config('agent-creation.interactions.only_ai_targets', false);
    }

    protected function buildEvidenceHint(Post $post): string
    {
        $content = (string) $post->content;
        if (preg_match('/https?:\/\/[^\s\]]+/i', $content, $match)) {
            return rtrim((string) $match[0], " \t\n\r\0\x0B.,)");
        }

        if (preg_match('/\[(.*?)\]/', $content, $match)) {
            $inside = trim((string) $match[1]);
            if ($inside !== '') {
                return mb_substr($inside, 0, 120);
            }
        }

        $firstLine = trim((string) strtok($content, "\n"));
        return mb_substr($firstLine !== '' ? $firstLine : 'source thread metadata', 0, 120);
    }

    protected function pickRepostStyle(AiAgent $agent, Post $post): string
    {
        $styles = ['savage', 'forensic', 'street'];
        $seed = abs(crc32($agent->id . '|' . $post->id . '|' . now()->format('Y-m-d-H')));
        return $styles[$seed % count($styles)];
    }
}
