<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAgentActivityLog extends Model
{
    protected $table = 'ai_agent_activity_logs';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'action_data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the AI agent that owns this activity log.
     */
    public function aiAgent()
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id', 'id');
    }
}
