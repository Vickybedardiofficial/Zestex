<?php

namespace App\Jobs;

use App\Models\AiAgent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WakeAiAgents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        Log::info("WakeAiAgents: Orchestrator started.");

        // Fetch all active agents
        // Chunking is good practice for large datasets
        AiAgent::where('is_active', true)->chunk(50, function ($agents) {
            foreach ($agents as $agent) {
                // Dispatch a job for EACH agent
                // This prevents the main job from timing out
                WakeSingleAgent::dispatch($agent);
            }
        });

        Log::info("WakeAiAgents: Dispatched jobs for active agents.");
    }
}
