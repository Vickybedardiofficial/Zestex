<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Schema;

class AiAgent extends Model
{
    use HasFactory;

    protected $table = 'ai_agents';

    protected $fillable = [
        'user_id',
        'personality_type',
        'country',
        'language',
        'activity_schedule',
        'topics',
        'posting_frequency',
        'engagement_level',
        'profession',
        'political_leaning',
        'writing_style',
        'editorial_tone',
        'ai_provider',
        'image_provider',
        'avatar_source',
        'is_active',
        'last_activity_at',
        'peak_active_hour',
        'specific_topics',
        'is_manual_override',
        'daily_posts_limit', 'daily_posts_count',
        'daily_comments_limit', 'daily_comments_count',
        'daily_likes_limit', 'daily_likes_count',
        'daily_shares_limit', 'daily_shares_count',
        'last_limit_reset_date',
        'blocked_topics',
        'manual_instruction',
        'post_frequency_modifier',
        'evolution_stage', // Part 14
        'reputation_score', // Part 14
        'last_reputation_update', // Part 14
        'auto_created',
        'account_created_at',
        'warm_up_stage',
        'age',
        'date_of_birth',
        'city',
    ];

    protected $casts = [
        'activity_schedule' => 'array',
        'topics' => 'array',
        'is_active' => 'boolean',
        'last_activity_at' => 'datetime',
        'specific_topics' => 'array',
        'is_manual_override' => 'boolean',
        'blocked_topics' => 'array',
        'post_frequency_modifier' => 'float',
        'reputation_score' => 'integer',
        'last_reputation_update' => 'datetime',
    ];

    /**
     * Get the user that owns the AI agent.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get all activity logs for this agent.
     */
    public function activityLogs()
    {
        return $this->hasMany(AiAgentActivityLog::class, 'ai_agent_id', 'id');
    }

    /**
     * Get all memories for this agent.
     */
    public function memories()
    {
        return $this->hasMany(AiAgentMemory::class, 'ai_agent_id', 'id');
    }

    /**
     * Scope to get only active agents.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by personality type.
     */
    public function scopeByPersonality($query, string $personality)
    {
        return $query->where('personality_type', $personality);
    }

    /**
     * Scope to filter by country.
     */
    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Log an activity for this agent.
     */
    public function logActivity(string $actionType, array $actionData = [])
    {
        $payload = [
            'action_type' => $actionType,
            'action_data' => $actionData,
        ];

        if (Schema::hasColumn('ai_agent_activity_logs', 'created_at')) {
            $payload['created_at'] = now();
        }

        if (Schema::hasColumn('ai_agent_activity_logs', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        return $this->activityLogs()->create($payload);
    }

    /**
     * Update last activity timestamp.
     */
    public function updateLastActivity()
    {
        $this->update(['last_activity_at' => now()]);
    }
}
