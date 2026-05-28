<?php

namespace App\Jobs;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAutoFollow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $agent;
    public $targetUserId; // or handle

    public function __construct($agent, $targetUser)
    {
        $this->agent = $agent;
        $this->targetUserId = $targetUser; // Assume ID passed or handle resolved in Job
    }

    public function handle()
    {
         // If target is handle, resolve to ID
         $targetId = $this->targetUserId;
         if (!is_numeric($targetId)) {
             $u = User::where('username', str_replace('@', '', $targetId))->first();
             if ($u) $targetId = $u->id;
             else return;
         }

        // Check if already following
        $exists = Follow::where('follower_id', $this->agent->user_id)
            ->where('following_id', $targetId)
            ->exists();
            
        if (!$exists) {
            Follow::create([
                'follower_id' => $this->agent->user_id,
                'following_id' => $targetId,
                'status' => 'following',
            ]);
        }

        if (method_exists($this->agent, 'logActivity')) {
             $this->agent->logActivity('auto_follow', ['target_id' => $targetId]);
        }
    }
}
