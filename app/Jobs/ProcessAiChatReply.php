<?php

namespace App\Jobs;

use App\Events\User\Chat\MessageReceivedEvent;
use App\Models\AdminSetting;
use App\Models\Chat;
use App\Models\HiddenChat;
use App\Models\User;
use App\Notifications\User\Chat\MessageReceivedNotification;
use App\Services\AI\AIProviderManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAiChatReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $mentionText,
        protected int $senderUserId,
        protected string $chatUuid,
        protected ?int $parentMessageId = null
    ) {}

    public function handle(): void
    {
        $sender = User::find($this->senderUserId);
        $chat = Chat::where('chat_id', $this->chatUuid)->first();

        if (! $sender || ! $chat) {
            Log::warning("ProcessAiChatReply: Sender or chat not found. sender={$this->senderUserId}, chat={$this->chatUuid}");
            return;
        }

        $botUser = $this->resolveBotUser();
        if (! $botUser) {
            return;
        }

        if ((int) $sender->id === (int) $botUser->id) {
            return;
        }

        if (! $chat->isParticipant($sender->id)) {
            return;
        }

        if (! $chat->isParticipant($botUser->id)) {
            if ($chat->type->isGroup()) {
                $chat->addParticipant($botUser->id);
            } else {
                // Do not mutate direct chat participant model.
                return;
            }
        }

        $botParticipant = $chat->participants()->where('user_id', $botUser->id)->first();
        if (! $botParticipant) {
            return;
        }

        $reply = $this->generateReply($sender);

        $message = $chat->messages()->create([
            'content' => e($reply),
            'user_id' => $botUser->id,
            'chat_uuid' => $chat->chat_id,
            'participant_id' => $botParticipant->id,
            'parent_id' => $this->parentMessageId,
            'text_language' => detect_text_language($reply),
        ]);

        $botParticipant->update([
            'last_read_message_id' => $message->id,
            'last_read_at' => now(),
        ]);

        $chat->update([
            'last_activity' => now(),
        ]);

        if ($chat->type->isDirect()) {
            HiddenChat::where('chat_id', $chat->id)->where('user_id', $botUser->id)->delete();
        }

        try {
            event(new MessageReceivedEvent($message));
            $chat->participants()
                ->whereNotIn('user_id', [$botUser->id])
                ->get()
                ->each(function ($participant) {
                    $participant->user->notify(new MessageReceivedNotification());
                });
        } catch (\Throwable $e) {
            Log::warning('ProcessAiChatReply: broadcast/notification failed.', ['error' => $e->getMessage()]);
        }
    }

    protected function resolveBotUser(): ?User
    {
        $botHandle = config('constants.BOT_HANDLE');
        $botUsername = ltrim((string) $botHandle, '@');
        $botUser = User::where('username', $botUsername)->first();

        if (! $botUser) {
            Log::error("ProcessAiChatReply: Bot user '{$botUsername}' not found.");
            return null;
        }

        return $botUser;
    }

    protected function generateReply(User $sender): string
    {
        $systemPrompt = (string) config('constants.SYSTEM_PROMPT');
        $cleanInput = $this->sanitizeMentionText($this->mentionText);
        $userContent = "USER INPUT (mirror language, tone, style, intensity exactly): {$cleanInput}\n"
            . "ADDRESSING RULE: If you use @mention, mention ONLY @{$sender->username}. Never mention bot handle.";

        try {
            $provider = AdminSetting::where('key', 'ai_default_provider')->value('value')
                ?? AdminSetting::where('key', 'ai_active_provider')->value('value');

            $aiManager = new AIProviderManager();
            $reply = $aiManager->generateText(
                trim($systemPrompt . "\n\n" . $userContent),
                $provider ?: null,
                ['max_tokens' => 200, 'temperature' => 0.8]
            );

            if (is_string($reply) && trim($reply) !== '') {
                return trim($reply);
            }
        } catch (\Throwable $e) {
            Log::warning('ProcessAiChatReply: provider manager failed, using fallback.', [
                'error' => $e->getMessage(),
            ]);
        }

        return "Hi @{$sender->username}, aapka message mil gaya. Main yahi hoon, sawaal thoda aur clear likho to direct answer dunga.";
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
}
