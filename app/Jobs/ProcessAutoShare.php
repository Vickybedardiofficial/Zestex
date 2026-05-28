<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAutoShare implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $agent;
    public $targetId;

    public function __construct($agent, $targetId)
    {
        $this->agent = $agent;
        $this->targetId = $targetId;
    }

    public function handle()
    {
        $original = Post::find($this->targetId);
        if (!$original) {
            return;
        }

        Post::create([
            'user_id' => $this->agent->user_id,
            'content' => '',
            'type' => 'text',
            'quote_post_id' => $this->targetId,
            'is_quoting' => true,
            'status' => 'active',
            'is_ai_generated' => true,
            'text_language' => '',
        ]);
        
        if (method_exists($this->agent, 'logActivity')) {
             $this->agent->logActivity('auto_share', ['original_id' => $this->targetId]);
        }
    }
}
