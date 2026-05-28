<?php

namespace App\Jobs;

use App\Models\AiAgent;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostPoll;
use App\Models\User;
use App\Services\AI\Content\CommentGenerator;
use App\Services\AI\Content\ContextAwarePostGenerator;
use App\Services\AI\Interaction\InteractionManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessRealUserInteraction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $model;
    protected $type;

    /**
     * Create a new job instance.
     */
    public function __construct($model, string $type)
    {
        $this->model = $model;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle(
        InteractionManager $interactionManager,
        CommentGenerator $commentGenerator,
        ContextAwarePostGenerator $postGenerator
    ): void
    {
        // Strict AI-only mode: do not engage with real users.
        return;

        try {
            // 1. Validate: Only react to REAL users
            $user = $this->model->user;
            if (!$user || $this->isAiAgent($user)) {
                return; // Ignore AI-to-AI loops here (handled by other schedules if needed)
            }

            // 2. Determine Action based on Type
            switch ($this->type) {
                case 'post_created':
                    $this->handleNewPost($this->model, $interactionManager, $commentGenerator);
                    break;

                case 'comment_created':
                    $this->handleNewComment($this->model, $postGenerator, $commentGenerator);
                    break;
                
                case 'poll_voted':
                    // Handle poll vote notification/reaction
                    break;
            }

        } catch (\Exception $e) {
            Log::error("Real User Interaction Failed: " . $e->getMessage());
        }
    }

    protected function handleNewPost(Post $post, InteractionManager $interactionManager, CommentGenerator $commentGenerator)
    {
        // Strict AI isolation mode:
        // AI agents should not engage directly on human-authored posts.
        if (!$this->isAiAgent($post->user)) {
            return;
        }

        // Chance to React: 70%
        if (rand(1, 100) > 70) return;

        // Find relevant agent
        $agent = $this->findRelevantAgent($post->content);
        if (!$agent) return;

        // 1. Like (High chance)
        if (rand(1, 100) <= 80) {
            $interactionManager->performLike($agent, $post);
        }

        // 2. Comment (50% chance)
        if (rand(1, 100) <= 50) {
            $comment = $commentGenerator->generateComment($agent, $post);
            $post->comments()->create([
                'user_id' => $agent->user_id,
                'content' => $comment,
            ]);
            Log::info("🤖 AI {$agent->user->name} commented on real user post {$post->id}");
        }

        // 3. Share (10% chance - if viral)
        if (rand(1, 100) <= 10) {
            $interactionManager->performShare($agent, $post);
        }
    }

    protected function handleNewComment(Comment $comment, ContextAwarePostGenerator $postGenerator, CommentGenerator $commentGenerator)
    {
        // If it's a reply to an agent, AI MUST reply back (Debate/Conversation)
        // Or if it mentions an agent
        
        $isReplyToAgent = false;
        $targetAgent = null;

        // Pattern 1: Reply to Agent's Post
        if ($this->isAiAgent($comment->post->user)) {
             $targetAgent = AiAgent::where('user_id', $comment->post->user_id)->first();
             $isReplyToAgent = true;
        }

        // Pattern 2: Reply to Agent's Comment
        if ($comment->parent_id) {
            $parentUser = $comment->parent->user;
             // Check if parent user is an agent
             if ($this->isAiAgent($parentUser)) {
                 $targetAgent = AiAgent::where('user_id', $parentUser->id)->first();
                 $isReplyToAgent = true;
             }
        }
        
        // Pattern 3: Mention (@AgentName)
        // Regex to find @mentions
        if (preg_match_all('/@([a-zA-Z0-9_]+)/', $comment->content, $matches)) {
            $usernames = $matches[1];
            foreach ($usernames as $username) {
                // Find user by username
                $user = User::where('username', $username)->first();
                if ($user && $this->isAiAgent($user)) {
                    $targetAgent = AiAgent::where('user_id', $user->id)->first();
                    $isReplyToAgent = true;
                    break; // Respond to first mentioned agent
                }
            }
        }
        
        if ($isReplyToAgent && $targetAgent) {
            // Debate Logic: Iterate until conversation naturally ends?
            // For now, always reply to keep engagement
            
            $reply = $postGenerator->generateReply($targetAgent, $comment);
            
            // Create Reply
            Comment::create([
                'post_id' => $comment->post_id,
                'user_id' => $targetAgent->user_id,
                'parent_id' => $comment->id,
                'content' => $reply
            ]);
             Log::info("🤖 AI {$targetAgent->user->name} replied to real user comment {$comment->id}");
        }
    }

    protected function findRelevantAgent(string $content): ?AiAgent
    {
        // Simple keyword matching or random active agent
        // For now, pick random active agent
        return AiAgent::active()->inRandomOrder()->first();
    }

    protected function isAiAgent(User $user): bool
    {
        // Check if user exists in ai_agents table
        return AiAgent::where('user_id', $user->id)->exists();
    }
}
