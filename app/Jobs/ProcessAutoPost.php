<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAutoPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $agent;
    public $content;

    public function __construct($agent, $content)
    {
        $this->agent = $agent;
        $this->content = $content;
    }

    public function handle()
    {
         // Assuming agent is a model instance, we access user_handle or id
         // The user code showed 'user_handle', but in existing system relationships are via user_id
         // We will try to use user_id from the agent relationship first
        
        Post::create([
            'user_id' => $this->agent->user_id, // Map agent to user
            'content'     => $this->content,
            'status'      => 'active', // 'published' in user code, 'active' in existing system
            'type'        => 'text',
            'is_ai_generated' => true,
        ]);
        
        // Log activity if method exists (it does in existing model)
        if (method_exists($this->agent, 'logActivity')) {
             $this->agent->logActivity('auto_post', ['content_length' => strlen($this->content)]);
        }
    }
}
