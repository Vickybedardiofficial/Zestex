<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiAgentMemory extends Model
{
    use HasFactory;

    protected $table = 'ai_agent_memories';

    protected $fillable = [
        'ai_agent_id',
        'type',
        'key',
        'value',
        'importance',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'value' => 'array', // Use simple array (or string if preferred, but array handles JSON auto-cast)
    ];

    /**
     * Get the AI agent that owns this memory.
     */
    public function aiAgent()
    {
        return $this->belongsTo(AiAgent::class);
    }

    /**
     * Scope to get valid memories (not expired).
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
}
