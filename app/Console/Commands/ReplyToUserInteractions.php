<?php

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Models\Comment;
use App\Models\User;
use App\Services\AI\Content\ContextAwarePostGenerator;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReplyToUserInteractions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-agents:reply-to-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate replies for real user comments on agent posts';

    protected $postGenerator;

    public function __construct(ContextAwarePostGenerator $postGenerator)
    {
        parent::__construct();
        $this->postGenerator = $postGenerator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔍 Scanning for real user interactions...");

        $agents = AiAgent::active()->get();
        $replyCount = 0;

        foreach ($agents as $agent) {
            // Get comments on this agent's posts from REAL users (not other agents)
            // Created between 5 and 15 minutes ago (Natural delay window)
            $comments = Comment::whereHas('post', function($q) use ($agent) {
                    $q->where('user_id', $agent->user_id);
                })
                ->where('user_id', '!=', $agent->user_id) // Not self
                ->whereDoesntHave('user', function($q) {
                    $q->whereHas('aiAgent'); // Check if commenter is an agent
                })
                ->whereBetween('created_at', [
                    now()->subMinutes(15), 
                    now()->subMinutes(5)
                ])
                ->whereDoesntHave('replies', function($q) use ($agent) {
                    $q->where('user_id', $agent->user_id); // Not already replied by this agent
                })
                ->get();

            foreach ($comments as $comment) {
                // 60% Chance to reply (as per user request)
                if (rand(1, 100) > 60) {
                    continue;
                }

                $this->line("💬 Found interaction for {$agent->user->name} from {$comment->user->name}");
                
                // Determine sentiment/context (Simplified for now, can use AI analysis later)
                // For now, we'll let the AI decide the tone based on the comment body
                
                try {
                    $replyContent = $this->postGenerator->generateReply($agent, $comment);
                    
                    // Create Reply (Comment)
                    Comment::create([
                        'user_id' => $agent->user_id,
                        'post_id' => $comment->post_id,
                        'parent_id' => $comment->id, // Threaded reply
                        'content' => $replyContent
                    ]);

                    $agent->increment('daily_comments_count');
                    $this->info("✅ Replied to {$comment->user->name}: \"{$replyContent}\"");
                    $replyCount++;

                } catch (\Exception $e) {
                    Log::error("Failed to reply to user", [
                        'agent' => $agent->id,
                        'comment' => $comment->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        $this->info("✨ Processed interactions. Sent {$replyCount} replies.");
        return 0;
    }
}
