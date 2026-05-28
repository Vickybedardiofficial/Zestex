<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use App\Models\AdminSetting;
use App\Services\AI\AIProviderManager;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Notifications\User\Post\PostCommentedNotification;
use App\Notifications\User\Mention\CommentMentionNotification;

class ProcessAiReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mentionText;
    protected $userId; 
    protected $postId;
    protected $parentId;

    /**
     * Create a new job instance.
     *
     * @param string $mentionText The text of the comment triggering the bot.
     * @param int $userId The ID of the user who made the comment.
     * @param int $postId The ID of the post.
     * @param int|null $parentId The ID of the parent comment (if reply).
     */
    public function __construct(
        string $mentionText,
        int $userId,
        int $postId,
        ?int $parentId = null
    ) {
        $this->mentionText = $mentionText;
        $this->userId = $userId;
        $this->postId = $postId;
        $this->parentId = $parentId;
    }

    public function handle(): void
    {
        $post = Post::find($this->postId);
        $user = User::find($this->userId);

        if (! $post || ! $user) {
            Log::warning("ProcessAiReply: Post or User not found. PostID: {$this->postId}, UserID: {$this->userId}");
            return;
        }

        $context = $this->buildContext($post, $user);
        $prompt = $this->buildUserContent($this->sanitizeMentionText($this->mentionText), $context);
        $systemPrompt = (string) config('constants.SYSTEM_PROMPT');

        $reply = null;

        try {
            $provider = AdminSetting::where('key', 'ai_default_provider')->value('value')
                ?? AdminSetting::where('key', 'ai_active_provider')->value('value');

            $aiManager = new AIProviderManager();
            $reply = $aiManager->generateText(
                trim($systemPrompt . "\n\n" . $prompt),
                $provider ?: null,
                ['max_tokens' => 220, 'temperature' => 0.8]
            );
        } catch (\Throwable $e) {
            Log::warning('ProcessAiReply: provider manager failed, using fallback.', [
                'error' => $e->getMessage()
            ]);
        }

        if (! $reply) {
            // Keep bot responsive even when external AI keys are not configured.
            $reply = $this->fallbackReply($user);
        }

        $this->postReply($post, $reply);
    }

    protected function buildContext(Post $post, User $user): array
    {
        // Media context
        $mediaDescription = 'No media attached.';
        if ($post->media && $post->media->isNotEmpty()) {
            $mediaDescription = $post->media->map(fn($m) => 
                strtoupper($m->type ?? 'unknown') . ': ' . ($m->url ?? 'no url')
            )->implode("\n");
        }

        // Fetch User's last 5 posts (text only)
        $oldPosts = $user->posts()
            ->whereNotNull('content')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($p) => "- {$p->content}")
            ->implode("\n");
        
        $oldPostsFormatted = $oldPosts ?: 'No previous posts found.';

        // Fetch Thread History (Parent Comment)
        $threadHistory = 'No thread history available (Direct reply to post).';
        if ($this->parentId) {
            $parent = \App\Models\Comment::with('user')->find($this->parentId);
            if ($parent) {
                $threadHistory = "Replying to @{$parent->user->username}: \"{$parent->content}\"";
            }
        }

        return [
            'userHandle'         => '@' . $user->username,
            'currentPostText'    => $post->content ?: 'No text content', 
            'mediaDescription'   => $mediaDescription,
            'oldPostsFormatted'  => $oldPostsFormatted, 
            'externalFacts'      => 'No external information available.',
            'threadHistory'      => $threadHistory
        ];
    }

    protected function buildUserContent(string $mentionText, array $context): string
    {
        return <<<EOT
USER INPUT (mirror language, tone, style, intensity exactly): {$mentionText}

CONTEXT SUMMARY:
- Mentioned User: {$context['userHandle']}
- Addressing Rule: If you use @mention, mention ONLY {$context['userHandle']}. Never mention bot handle.
- Current Post: {$context['currentPostText']}
- Media (images/videos): {$context['mediaDescription']}
- Relevant Past Posts:
  {$context['oldPostsFormatted']}
- External Context:
  {$context['externalFacts']}
- Thread History:
  {$context['threadHistory']}
EOT;
    }

    protected function sanitizeMentionText(string $text): string
    {
        $botHandle = trim((string) config('constants.BOT_HANDLE'));
        $sanitized = $text;

        if ($botHandle !== '') {
            $pattern = '/(?<![A-Za-z0-9_])' . preg_quote($botHandle, '/') . '(?![A-Za-z0-9_])/iu';
            $sanitized = (string) preg_replace($pattern, '', $sanitized);
        }

        $sanitized = trim(preg_replace('/\s+/u', ' ', $sanitized) ?? '');
        return $sanitized !== '' ? $sanitized : $text;
    }

    protected function postReply(Post $post, string $replyText): void
    {
        // Find the bot user
        $botHandle = config('constants.BOT_HANDLE');
        $botUsername = str_replace('@', '', $botHandle);
        $botUser = User::where('username', $botUsername)->first();

        if (! $botUser) {
            Log::error("ProcessAiReply: Bot user '{$botUsername}' not found.");
            return;
        }

        // Create the comment
        $comment = $post->comments()->create([
            'content' => $replyText,
            'user_id' => $botUser->id,
            'parent_id' => $this->parentId,
            'text_language' => $post->text_language // Or detect language if possible
        ]);
        
        // Update post comments count
        $post->comments_count = $post->comments()->count();
        $post->save();

        // Send Notifications
        if (! $post->is_owner && empty($this->parentId)) {
            // Notify post owner if bot replied to post directly (and bot is not owner)
             if ($post->user_id !== $botUser->id) {
                $post->user->notify(new PostCommentedNotification($post, $replyText));
             }
        } elseif ($this->parentId) {
             // Notify parent comment owner
             $parentComment = $post->comments()->find($this->parentId);
             if ($parentComment && $parentComment->user_id !== $botUser->id) {
                 $parentComment->user->notify(new CommentMentionNotification($parentComment, $replyText));
             }
        }
    }

    protected function fallbackReply(User $user): string
    {
        return "Hi @{$user->username}, maine aapka message dekh liya. "
            . "Abhi AI response service configured nahi hai, phir bhi main yahan hoon. "
            . "Aap apna sawaal thoda detail me likhen, main short reply dunga.";
    }
}
