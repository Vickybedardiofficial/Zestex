<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\Comment;
use App\Events\User\Timeline\CommentCreatedEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAutoComment implements ShouldQueue
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
        $post = Post::find($this->targetId);

        if (!$post) {
            return;
        }

        $content = trim((string) $this->content);
        if ($content === '' || $this->hasHypocrisySignal($post)) {
            $content = $this->buildContradictionComment($post);
        }

        $comment = Comment::create([
            'user_id' => $this->agent->user_id,
            'post_id' => $post->id,
            'content' => $content,
            'text_language' => '',
        ]);

        if ((int) $post->user_id > 0 && (int) $post->user_id !== (int) $this->agent->user_id) {
            event(new CommentCreatedEvent($comment, (int) $post->user_id));
        }

        if (method_exists($this->agent, 'logActivity')) {
            $this->agent->logActivity('auto_comment', ['post_id' => $post->id]);
        }
    }

    protected function buildContradictionComment(Post $post): string
    {
        $snippet = trim((string) preg_replace('/\s+/', ' ', (string) $post->content));
        $snippet = mb_substr($snippet, 0, 80);
        $emojis = "\u{1F525}\u{1F921}\u{1F440}";

        return "Nice 'victory' claim - but old posts read opposite {$emojis} \"{$snippet}\". Flip-flop ka evidence clear hai, explain karoge?";
    }

    protected function hasHypocrisySignal(Post $post): bool
    {
        $current = mb_strtolower((string) $post->content);
        $recent = Post::query()
            ->where('user_id', $post->user_id)
            ->where('id', '!=', $post->id)
            ->latest('id')
            ->limit(10)
            ->pluck('content')
            ->filter()
            ->map(fn ($line) => mb_strtolower((string) $line))
            ->all();

        if (empty($recent) || $current === '') {
            return false;
        }

        foreach ($recent as $line) {
            if ($line === '') {
                continue;
            }

            if ((str_contains($current, 'support') && str_contains($line, 'oppose'))
                || (str_contains($current, 'oppose') && str_contains($line, 'support'))
                || (str_contains($current, 'always') && str_contains($line, 'never'))
                || (str_contains($current, 'never') && str_contains($line, 'always'))
                || (str_contains($current, 'truth') && str_contains($line, 'lies'))
                || (str_contains($current, 'lies') && str_contains($line, 'truth'))) {
                return true;
            }
        }

        return false;
    }
}
