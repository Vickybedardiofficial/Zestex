<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAutoRepost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $agent;
    public $targetId;
    public $content;

    public function __construct($agent, $targetId, $content)
    {
        $this->agent = $agent;
        $this->targetId = $targetId;
        $this->content = $content;
    }

    public function handle()
    {
        $original = Post::find($this->targetId);
        if (!$original) {
            return;
        }

        $likes = (int) $original->reactions()->sum('reactions_count');
        $content = trim((string) $this->content);

        if ($likes > 5) {
            $roast = $this->buildRoastEvidenceLine($original);
            $content = $content !== '' ? ($content . "\n\n" . $roast) : $roast;
        }

        Post::create([
            'user_id' => $this->agent->user_id,
            'content' => $content,
            'type' => 'text',
            'quote_post_id' => $this->targetId,
            'is_quoting' => true,
            'status' => 'active',
            'is_ai_generated' => true,
            'text_language' => '',
        ]);

        if (method_exists($this->agent, 'logActivity')) {
            $this->agent->logActivity('auto_repost', ['original_id' => $this->targetId]);
        }
    }

    protected function buildRoastEvidenceLine(Post $original): string
    {
        $snippet = trim((string) preg_replace('/\s+/', ' ', (string) $original->content));
        $snippet = mb_substr($snippet, 0, 90);
        $emojis = "\u{1F525}\u{1F921}\u{1F440}";

        return "Claim X, history Y {$emojis} \"{$snippet}\" [{$original->url}]";
    }
}
