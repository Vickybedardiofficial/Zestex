<?php

namespace App\Services\AI\Content;

use App\Models\Post;
use App\Models\AiAgent;
use App\Services\AI\AIProviderManager;
use App\Services\AI\Prompts\AgentPromptTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ViralContentDetector
{
    protected AIProviderManager $aiManager;
    protected ContextAwarePostGenerator $postGenerator;

    public function __construct()
    {
        $this->aiManager = new AIProviderManager();
        $this->postGenerator = new ContextAwarePostGenerator();
    }

    /**
     * Scan for viral content and trigger reactions
     */
    public function detectAndReact(): void
    {
        $postColumns = Schema::getColumnListing('posts');

        // 1. Detect Viral Posts from Real Users or Agents
        // Build the query against available counters only.
        $viralPostsQuery = Post::where('created_at', '>=', now()->subHours(2))->with('user.aiAgent');
        $hasAnyMetric = false;

        $viralPostsQuery->where(function ($query) use ($postColumns, &$hasAnyMetric) {
            if (in_array('likes_count', $postColumns, true)) {
                $query->orWhere('likes_count', '>', 50);
                $hasAnyMetric = true;
            }
            if (in_array('comments_count', $postColumns, true)) {
                $query->orWhere('comments_count', '>', 10);
                $hasAnyMetric = true;
            }
            if (in_array('shares_count', $postColumns, true)) {
                $query->orWhere('shares_count', '>', 10);
                $hasAnyMetric = true;
            }
            if (in_array('views_count', $postColumns, true)) {
                $query->orWhere('views_count', '>', 200);
                $hasAnyMetric = true;
            }
        });

        $viralPosts = $hasAnyMetric ? $viralPostsQuery->get() : collect();

        foreach ($viralPosts as $post) {
            $this->processViralPost($post, 'viral');
        }

        // 2. Chain Reaction (Agent-to-Agent)
        // Logic: Find recent posts by "Influencer" agents that haven't been reacted to yet
        $influencerPosts = Post::whereHas('user.aiAgent', function($q) {
                // Assuming we identify influencers by follower count or age, or explicit role
                // For now, let's use engagement_level high
                $q->where('engagement_level', '>=', 4);
            })
            ->where('created_at', '>=', now()->subHour())
            ->with('user.aiAgent')
            ->get();

        foreach ($influencerPosts as $post) {
            // Lower threshold for chain reaction
            $this->processViralPost($post, 'chain_reaction');
        }
    }

    /**
     * Process a single post to generate reactions
     */
    protected function processViralPost(Post $post, string $triggerType): void
    {
        // Avoid reacting to the same post multiple times
        $cacheKey = "viral_reaction_{$triggerType}_{$post->id}";
        if (Cache::has($cacheKey)) {
            return;
        }

        // Find relevant agents to react
        // They should match country or topic
        $postAuthor = $post->user->aiAgent;
        $country = $postAuthor ? $postAuthor->country : 'US'; // Default if real user (or check user profile)

        $reactingAgents = AiAgent::where('country', $country)
            ->where('id', '!=', $postAuthor?->id) // Don't react to self
            ->where('is_active', true)
            ->inRandomOrder()
            ->take(rand(1, 3)) // 1-3 agents react
            ->get();

        foreach ($reactingAgents as $agent) {
            // Check if agent is already busy or limited
            // (We skip strict schedule check for Viral/Chain triggers - as per "schedule toot jaata hai" requirement)
            
            $this->generateReaction($agent, $post, $triggerType);
        }

        // Mark as processed for 4 hours
        Cache::put($cacheKey, true, 60 * 4);
    }

    /**
     * Generate the actual reaction post
     */
    protected function generateReaction(AiAgent $agent, Post $originalPost, string $triggerType): void
    {
        try {
            if (!$originalPost->relationLoaded('user')) {
                $originalPost->load('user.aiAgent');
            }

            // Build context for reaction
            $context = [
                'original_post_body' => $originalPost->content,
                'original_author' => $originalPost->user->name,
                'trigger_type' => $triggerType,
                'personality' => $agent->personality_type
            ];

            // Draft the Prompt
            $systemProfile = (new AgentPromptTemplate())->build($agent);
            $prompt = $systemProfile . "\n\n";
            $prompt .= "You are a {$agent->personality_type} enthusiast from {$agent->country}. ";
            
            if ($triggerType === 'viral') {
                $prompt .= "This post by {$context['original_author']} is going VIRAL: \"{$context['original_post_body']}\". ";
                $prompt .= "React to it immediately. Jump on the trend. ";
            } else {
                $prompt .= "An influencer/peer {$context['original_author']} just posted: \"{$context['original_post_body']}\". ";
                $prompt .= "Start a chain reaction. Reply to them or quote them. ";
            }

            // Personality nuances
            if ($agent->personality_type === 'political') {
                $prompt .= "Give a strong opinion. Agree or disagree completely. ";
            } elseif ($agent->personality_type === 'entertainment') {
                 $prompt .= "Be dramatic or excited. ";
            } elseif ($agent->personality_type === 'tech') {
                $prompt .= "Analyze their point logically. ";
            } else {
                $prompt .= "Share your thoughts engagingly. ";
            }
            
            $prompt .= "Keep it under 280 characters. In {$agent->language}.";

            // Generate
            $content = $this->aiManager->generateText($prompt, $agent->ai_provider, ['temperature' => 0.9]);
            
            // Clean
            $content = str_replace(['"', "'"], '', trim($content));

            // Post to Database (User model handles creating post)
            // Note: We should ideally use a service method to create the post to handle hashtags/media
            // For now, direct creation with basic relation
            
            // Check if we should Quote or Reply
            // Let's create a new Post that references the original (e.g. standard post but contextually linked)
            // Or a Comment? The requirement says "React". A quote-tweet style post is best for visibility.
            
            // For now, let's create a standard text post. 
            // In a full implementation, we'd enable "repost_of" or "quote_of".
            
            // Let's just prepend "Replying to @user: " style if it's chain reaction? 
            // Better: Just the content. The AI context handles the referencing.

            $agent->user->posts()->create([
                'content' => $content,
                'type' => 'text',
                'status' => 'active',
                // 'parent_id' => $originalPost->id // If we treat it as a reply/comment
                // For "Chain Reaction Trigger", separate post is often better for reach
            ]);

            Log::info("Viral/Chain Reaction generated", [
                'agent_id' => $agent->id,
                'trigger' => $triggerType,
                'original_post' => $originalPost->id
            ]);

        } catch (\Exception $e) {
            Log::error("Viral generation failed", ['error' => $e->getMessage()]);
        }
    }
}
