<?php

namespace App\Services\AI\ProfileGenerator;

use App\Enums\User\FollowStatus;
use App\Enums\User\UserStatus;
use App\Enums\User\UserType;
use App\Models\User;
use App\Models\AiAgent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AutoFollowService
{
    /**
     * Auto-follow relevant accounts based on agent personality
     */
    public function autoFollow(AiAgent $agent): int
    {
        $followCount = 0;

        try {
            // Get target users to follow
            $targetsToFollow = $this->getTargetUsers($agent);

            foreach ($targetsToFollow as $targetUser) {
                try {
                    // Check if already following
                    if ($this->isAlreadyFollowing($agent->user_id, $targetUser->id)) {
                        continue;
                    }

                    // Create follow relationship
                    DB::table('follows')->insert([
                        'follower_id' => $agent->user_id,
                        'following_id' => $targetUser->id,
                        'status' => FollowStatus::FOLLOWING->value,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    $followCount++;

                    // Add small delay to seem natural
                    usleep(rand(100000, 500000)); // 0.1-0.5 seconds

                } catch (\Exception $e) {
                    Log::warning('Auto-follow failed for user', [
                        'agent_id' => $agent->id,
                        'target_user_id' => $targetUser->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log activity
            $agent->logActivity('auto_followed', [
                'follow_count' => $followCount,
                'personality' => $agent->personality_type
            ]);

            return $followCount;

        } catch (\Exception $e) {
            Log::error('Auto-follow service failed', [
                'agent_id' => $agent->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get target users to follow based on personality
     */
    protected function getTargetUsers(AiAgent $agent): array
    {
        $limit = rand(10, 25); // Follow 10-25 accounts initially

        // Get users based on personality and topics
        $query = User::where('id', '!=', $agent->user_id)
            ->where('type', UserType::AI_AGENT->value)
            ->where('status', UserStatus::ACTIVE->value)
            ->inRandomOrder()
            ->limit($limit);

        // Filter by topics if available
        if (!empty($agent->topics) && is_array($agent->topics)) {
            // This would require a topics/interests field on users table
            // For now, we'll just get random active users
        }

        // Prioritize other AI agents with similar personality
        $similarAgents = AiAgent::where('personality_type', $agent->personality_type)
            ->where('id', '!=', $agent->id)
            ->where('is_active', true)
            ->with('user')
            ->limit(5)
            ->get()
            ->pluck('user')
            ->filter();

        // Get some random users
        $randomUsers = $query->get();

        // Merge and return unique users
        return $similarAgents->merge($randomUsers)
            ->unique('id')
            ->take($limit)
            ->all();
    }

    /**
     * Check if already following
     */
    protected function isAlreadyFollowing(int $followerId, int $followingId): bool
    {
        return DB::table('follows')
            ->where('follower_id', $followerId)
            ->where('following_id', $followingId)
            ->exists();
    }

    /**
     * Follow specific topics/hashtags
     */
    public function followTopics(AiAgent $agent): int
    {
        if (empty($agent->topics) || !is_array($agent->topics)) {
            return 0;
        }

        $followedCount = 0;

        foreach ($agent->topics as $topic) {
            try {
                if (!Schema::hasTable('topics') || !Schema::hasTable('topic_followers')) {
                    return 0;
                }

                // Check if topics table exists
                $topicExists = DB::table('topics')
                    ->where('name', $topic)
                    ->exists();

                if (!$topicExists) {
                    // Create topic if doesn't exist
                    DB::table('topics')->insert([
                        'name' => $topic,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                // Follow topic
                $topicId = DB::table('topics')->where('name', $topic)->value('id');

                if ($topicId) {
                    DB::table('topic_followers')->insertOrIgnore([
                        'user_id' => $agent->user_id,
                        'topic_id' => $topicId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    $followedCount++;
                }

            } catch (\Exception $e) {
                Log::warning('Topic follow failed', [
                    'agent_id' => $agent->id,
                    'topic' => $topic,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $followedCount;
    }
}
