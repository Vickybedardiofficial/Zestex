<?php

namespace App\Services\AI\Content;

use App\Services\AI\AIProviderManager;
use Illuminate\Support\Facades\Log;

class PostGenerator
{
    protected AIProviderManager $aiManager;

    public function __construct()
    {
        $this->aiManager = new AIProviderManager();
    }

    public function generatePost(array $context, ?string $provider = null): array
    {
        $prompt = (string) ($context['prompt'] ?? '');
        $contextJson = json_encode($context['context'] ?? $context, JSON_UNESCAPED_UNICODE);
        $provider = $provider ?: null;

        if ($prompt === '') {
            $prompt = <<<EOT
You are a viral AI agent. Goal: max likes, comments, shares.

Strict rules:
- First line: strong hook (question/shock/emoji)
- Body: 80-120 words max, punchy only
- End: question or CTA
- Emojis: 2-4 (??????????)
- Hashtags: 2-4 trending (string format: #Tag1 #Tag2)
- Tone: witty/savage if needed
- No long analysis, no repetition
- Must anchor on real-time/last 2h context
- Never start with: Fresh view, Quick signal check, This thread deserves a second look, Re-sharing with context, Worth amplifying, Signal update
- Media description: null (do not print it in content)
- Output: strict JSON only: {"content":"...","hashtags":["Viral","Trending"],"media_desc":null}

Context: {$contextJson}
Generate now.
EOT;
        }

        try {
            $temperature = random_int(95, 100) / 100;
            $raw = $this->aiManager->generateText($prompt, $provider, [
                'temperature' => $temperature,
                'max_tokens' => 180,
            ]);

            $json = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $clean = preg_replace('/^```json\s*|\s*```$/i', '', $raw);
                $json = json_decode((string) $clean, true);
            }

            if (is_array($json) && isset($json['content'])) {
                $json['content'] = trim((string) $json['content']);
                $json['content'] = preg_replace('/^\s*media_desc\s*:\s*.*$/im', '', $json['content']) ?? $json['content'];
                $json['content'] = preg_replace(
                    '/\b(Fresh view|Quick signal check|This thread deserves a second look|Re-?sharing with context|Worth amplifying|Signal update|Big update)\b/i',
                    '',
                    $json['content']
                ) ?? $json['content'];
                $json['content'] = trim((string) (preg_replace('/\s+/', ' ', $json['content']) ?? $json['content']));

                if (isset($json['hashtags']) && is_array($json['hashtags'])) {
                    $normalized = collect($json['hashtags'])
                        ->map(fn ($tag) => preg_replace('/[^A-Za-z0-9_]/', '', ltrim((string) $tag, '#')))
                        ->filter()
                        ->take(4)
                        ->map(fn ($tag) => "#{$tag}")
                        ->implode(' ');
                    $json['hashtags'] = $this->enforceHashtagRange(trim((string) $normalized));
                } elseif (isset($json['hashtags']) && is_string($json['hashtags'])) {
                    $normalized = collect(preg_split('/[\s,]+/', trim($json['hashtags'])) ?: [])
                        ->map(fn ($tag) => preg_replace('/[^A-Za-z0-9_]/', '', ltrim((string) $tag, '#')))
                        ->filter()
                        ->take(4)
                        ->map(fn ($tag) => "#{$tag}")
                        ->implode(' ');
                    $json['hashtags'] = $this->enforceHashtagRange(trim((string) $normalized));
                } else {
                    $json['hashtags'] = '#Viral #Trending #Breaking';
                }

                unset($json['media_desc']);
                return $json;
            }

            return $this->buildFallbackPayload($context);
        } catch (\Throwable $e) {
            Log::error('Post generation failed', ['error' => $e->getMessage()]);
            return $this->buildFallbackPayload($context);
        }
    }

    protected function buildFallbackPayload(array $context): array
    {
        $ctx = (array) ($context['context'] ?? $context);
        $focus = (string) ($ctx['focus_topic'] ?? $ctx['post_type'] ?? 'trend');
        $country = (string) ($ctx['country'] ?? 'global');
        $seed = abs(crc32($focus . '|' . $country . '|' . now()->format('Y-m-d-H-i')));
        $latestNews = (array) ($ctx['realtime_news'] ?? $ctx['latest_news'] ?? []);
        $newsTitle = trim((string) (($latestNews[0]['title'] ?? '') ?: "{$focus} momentum is accelerating"));
        $newsTitle = preg_replace('/[^A-Za-z0-9\s\-\?]/', ' ', $newsTitle) ?? $newsTitle;
        $newsTitle = trim((string) (preg_replace('/\s+/', ' ', $newsTitle) ?? $newsTitle));

        $hooks = [
            "?? {$newsTitle} - are we ready for what comes next?",
            "?? Hard question: is {$focus} real progress or just hype?",
            "?? Hypocrisy check on {$focus}: why are promises outrunning delivery?",
            "?? {$focus} just shifted fast - did we miss the real signal?",
        ];
        $bodies = [
            "Live cues from {$country} show sentiment rising but delivery still uneven. Attention is high, execution is the real test. Momentum is real, but consistency remains weak. If outcomes stay soft, this narrative flips quickly. Short-term hype is cheap; trust is expensive.",
            "Promise vs performance gap is visible right now and the next phase will be decided by measurable delivery. Public memory tracks contradictions faster than before, and repeated flip-flops reduce confidence. The next 30 days need hard proof, not louder claims.",
            "Current signals show fast narrative movement, but execution quality still decides real reach. If institutions miss timelines again, audience sentiment can reverse in a single cycle. Evidence-based updates will outperform generic slogans and vague reassurance.",
            "This trend is expanding, but a lot of headlines hide weak follow-through. People are not reacting to words anymore, they are reacting to outcomes. If promises and history don’t match, backlash builds fast and credibility drops harder.",
        ];
        $ctas = [
            "Which one metric would change your view first? ??",
            "Agree or disagree - what evidence are we ignoring? ??",
            "If this continues 30 days, what breaks first? ??",
            "Drop one hard fact that supports your side. ??",
        ];

        $hashtags = ['#Viral', '#Trending', '#Breaking', '#Debate', '#NoSpin', '#Now'];
        foreach ((array) ($ctx['trending_topics'] ?? []) as $topic) {
            $clean = preg_replace('/[^A-Za-z0-9_]/', '', (string) $topic);
            if ($clean) {
                $hashtags[] = '#' . ucfirst($clean);
            }
            if (count($hashtags) >= 10) {
                break;
            }
        }
        shuffle($hashtags);
        $picked = $this->enforceHashtagRange(implode(' ', array_slice($hashtags, 0, 3)));

        $content = implode("\n\n", [
            $hooks[$seed % count($hooks)],
            $bodies[$seed % count($bodies)],
            $ctas[$seed % count($ctas)],
        ]);

        return [
            'content' => $content,
            'hashtags' => $picked,
        ];
    }

    protected function enforceHashtagRange(string $hashtags): string
    {
        $parts = collect(preg_split('/[\s,]+/', trim($hashtags)) ?: [])
            ->map(fn ($tag) => '#' . preg_replace('/[^A-Za-z0-9_]/', '', ltrim((string) $tag, '#')))
            ->filter(fn ($tag) => $tag !== '#')
            ->unique()
            ->values()
            ->all();

        while (count($parts) < 2) {
            $parts[] = count($parts) === 0 ? '#Viral' : '#Trending';
        }

        return implode(' ', array_slice($parts, 0, 4));
    }
}
