<?php

namespace App\Services\AI\Content;

use App\Models\AiAgent;
use App\Models\Comment;
use App\Models\Post;
use App\Services\AI\AIProviderManager;
use Illuminate\Support\Facades\Log;

class CommentGenerator
{
    protected AIProviderManager $aiManager;

    public function __construct()
    {
        $this->aiManager = new AIProviderManager();
    }

    public function shouldComment(AiAgent $agent, Post $post): bool
    {
        if ($post->user_id === $agent->user_id) {
            return false;
        }
        if (!$post->user) {
            return false;
        }

        return $this->isRelevantPost($agent, $post)
            ? rand(1, 100) <= 40
            : rand(1, 100) <= 10;
    }

    public function generateComment(AiAgent $agent, Post $post, string $triggerType = 'general'): string
    {
        if (!$post->user) {
            return '';
        }

        $prompt = $this->buildOptimizedPrompt($agent, $post, $triggerType);

        try {
            $raw = $this->aiManager->generateText($prompt, $agent->ai_provider, [
                'temperature' => 0.95,
                'max_tokens' => 120,
            ]);

            $content = $this->extractCommentFromResponse($raw);
            if ($content !== null && $content !== '') {
                $content = $this->normalizeComment($content);
                if ($content !== '' && !$this->isDuplicateComment($agent, $content)) {
                    return $content;
                }
            }

            return $this->buildFallbackComment($agent, $post, $triggerType);
        } catch (\Throwable $e) {
            Log::error('Comment generation failed', [
                'agent_id' => $agent->id,
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);

            return $this->buildFallbackComment($agent, $post, $triggerType);
        }
    }

    protected function buildOptimizedPrompt(AiAgent $agent, Post $post, string $triggerType): string
    {
        $agentName = $agent->user->name ?? ($agent->name ?? 'AI Agent');
        $authorName = $post->user->name ?? 'author';
        $style = $this->pickCommentStyle($agent, $post, $triggerType);
        $recentComments = $this->getRecentCommentSamples($agent);
        $isRival = $triggerType === 'rival';
        $rivalRule = $isRival
            ? "- If contradiction/hypocrisy is visible, write a witty savage mirror-tone comment and end with a question.\n"
            : '';

        return <<<EOT
You are {$agentName}, a high-engagement social AI agent.
Goal: write one unique comment that drives replies.

Strict rules:
- First line must be a hook (question, tension, or surprising fact).
- Total length 40-90 words.
- End with a question or CTA.
- Include 1-3 emojis and max 1-2 hashtags only if naturally relevant.
- No "Original Post:", no generic fillers, no repeated phrase patterns.
- Avoid these exact starters: "Interesting take", "Good signal", "Yeh important hai", "Fresh view", "Quick signal check", "Signal update", "Worth amplifying", "Re-sharing with context".
- Mirror target post language and intensity.
- Trigger mode: {$triggerType}.
- Comment style for this turn: {$style} (must follow strictly).
- Emojis preference for sharp tone: ?? ?? ??.
{$rivalRule}- For rival mode: contradiction line first, witty roast second, question at end.
- Never repeat sentence structure from recent comments.
- Output strict JSON only: {"content":"..."}

Recent comments to avoid repeating:
{$recentComments}

Target post by {$authorName}:
{$post->content}
EOT;
    }

    protected function extractCommentFromResponse(string $raw): ?string
    {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['content'])) {
            return trim((string) $decoded['content']);
        }

        $clean = trim((string) preg_replace('/^```json\s*|\s*```$/i', '', $raw));
        $decoded = json_decode($clean, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['content'])) {
            return trim((string) $decoded['content']);
        }

        $plain = trim(strip_tags($clean));
        $plain = preg_replace('/\s+/', ' ', $plain);
        if (is_string($plain) && strlen($plain) >= 20) {
            return mb_substr($plain, 0, 260);
        }

        return null;
    }

    protected function isDuplicateComment(AiAgent $agent, string $content): bool
    {
        if (Comment::query()
            ->where('content', $content)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists()) {
            return true;
        }

        $currentNorm = $this->normalizeForSimilarity($content);
        $recent = Comment::query()
            ->where('user_id', $agent->user_id)
            ->where('created_at', '>=', now()->subHours(24))
            ->latest('id')
            ->limit(20)
            ->pluck('content');

        foreach ($recent as $existing) {
            $existingNorm = $this->normalizeForSimilarity((string) $existing);
            if ($existingNorm === '' || $currentNorm === '') {
                continue;
            }

            similar_text($currentNorm, $existingNorm, $pct);
            if (($pct / 100) >= 0.82) {
                return true;
            }
        }

        return false;
    }

    protected function buildFallbackComment(AiAgent $agent, Post $post, string $triggerType): string
    {
        $topic = $this->extractTopicWord($post->content);
        $style = $this->pickCommentStyle($agent, $post, $triggerType);

        $openers = match ($style) {
            'roast' => ['Hypocrisy alert', 'Roast mode', 'Hard mirror', 'Contradiction check'],
            'agree' => ['Fair point', 'Strong agree', 'Valid read', 'Solid take'],
            'challenge' => ['Direct challenge', 'Counter-read', 'Hard question', 'Pressure test'],
            default => ['Quick question', 'Reality check', 'One angle', 'Data point'],
        };

        $questions = [
            'What metric should we track first?',
            'Which proof point matters most here?',
            'What outcome would change your view?',
            'Where is the real risk in this trend?',
            'Which hard evidence changes the conclusion?',
        ];

        $seed = abs(crc32($agent->id . '|' . $post->id . '|' . $topic . '|' . $triggerType . '|' . now()->format('Y-m-d-H')));
        $opener = $openers[$seed % count($openers)];
        $question = $questions[$seed % count($questions)];

        $templates = [
            "{$opener}: {$topic} looks loud, but delivery quality is the actual scoreboard. {$question} ?? #Debate",
            "{$opener}: if {$topic} was truly strong, we'd already see cleaner evidence on execution. {$question} ?? #Signal",
            "{$opener}: good narrative, weak proof chain so far. {$question} ?? #PublicView",
            "{$opener}: short-term buzz around {$topic} is easy, long-term consistency is hard. {$question} ?? #RealityCheck",
        ];

        return $templates[$seed % count($templates)];
    }

    protected function extractTopicWord(string $text): string
    {
        $clean = strtolower(strip_tags($text));
        $clean = preg_replace('/[^a-z0-9\s]/', ' ', $clean);
        $words = array_values(array_filter(explode(' ', (string) $clean), fn ($w) => strlen($w) >= 4));

        if (empty($words)) {
            return 'the trend';
        }

        return $words[array_rand($words)];
    }

    protected function normalizeComment(string $content): string
    {
        $content = trim((string) preg_replace('/\s+/', ' ', $content));

        if ($content === '') {
            return '';
        }

        $wordCount = count(array_values(array_filter(preg_split('/\s+/', $content) ?: [])));
        if ($wordCount < 6 || mb_strlen($content) < 35 || str_ends_with($content, ':')) {
            return '';
        }

        $blockedPatterns = [
            '/\bOriginal Post\b/i',
            '/\b(Fresh angle|Strategic angle|Counter-point|Support angle)\s*:/i',
            '/\b(signal\s*update|re-?\s*sharing with context|worth\s*amplifying)\b\s*:/i',
            '/\b(Fresh view|Quick signal check|This thread deserves a second look)\b/i',
            '/\bis moving fast right now\b/i',
            '/\bWhat metric should we track first\b/i',
            '/\bInteresting take\b/i',
        ];

        foreach ($blockedPatterns as $rx) {
            if (preg_match($rx, $content)) {
                return '';
            }
        }

        if (!$this->hasQuestionOrCta($content)) {
            $content = rtrim($content, ". \t\n\r\0\x0B") . '?';
        }

        return mb_substr($content, 0, 260);
    }

    protected function isRelevantPost(AiAgent $agent, Post $post): bool
    {
        $text = strtolower($post->content);
        $keywords = $agent->keywords ?? ['politics', 'news', 'tech', 'controversy', 'hypocrisy'];

        foreach ($keywords as $kw) {
            if (str_contains($text, (string) $kw)) {
                return true;
            }
        }

        return false;
    }

    protected function pickCommentStyle(AiAgent $agent, Post $post, string $triggerType): string
    {
        if ($triggerType === 'rival') {
            return 'roast';
        }

        $styles = ['question', 'agree', 'challenge', 'roast'];
        $seed = abs(crc32($agent->id . '|' . $post->id . '|' . $triggerType . '|' . now()->format('Y-m-d-H')));
        return $styles[$seed % count($styles)];
    }

    protected function getRecentCommentSamples(AiAgent $agent): string
    {
        $recent = Comment::query()
            ->where('user_id', $agent->user_id)
            ->latest('id')
            ->limit(8)
            ->pluck('content')
            ->map(fn ($v) => trim((string) preg_replace('/\s+/', ' ', (string) $v)))
            ->filter()
            ->values()
            ->all();

        if (empty($recent)) {
            return '[]';
        }

        return json_encode($recent, JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    protected function hasQuestionOrCta(string $content): bool
    {
        if (str_contains($content, '?')) {
            return true;
        }

        return (bool) preg_match('/\b(share|drop|reply|tell|vote|choose|debate)\b/i', $content);
    }

    protected function normalizeForSimilarity(string $text): string
    {
        $normalized = mb_strtolower($text);
        $normalized = preg_replace('/#[\p{L}\p{N}_]+/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', ' ', trim($normalized)) ?? trim($normalized);

        return $normalized;
    }
}
