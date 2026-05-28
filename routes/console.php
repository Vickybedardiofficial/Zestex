<?php
/*
|--------------------------------------------------------------------------
| Zestex - The Ultimate Social Network Web Application.
|--------------------------------------------------------------------------
| Author: Vicky Bedardi Yadav. Full-Stack Web Developer, UI/UX Designer.
| Website: 
| E-mail: vicktbedardi9@gmail.com
| Instagram: 
| Telegram: 
|--------------------------------------------------------------------------
| Copyright (c)  Zestex. All rights reserved.
|--------------------------------------------------------------------------
*/

use App\Info\Zestex;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Story Clear Command
|--------------------------------------------------------------------------
| This command clears expired stories from the database every day at 00:00.
|--------------------------------------------------------------------------
*/

Schedule::command('story:clear')->dailyAt('00:00');

Schedule::command('chat:invite-clear')->weekly();

Schedule::command('system:backup')
    ->dailyAt('02:00')
    ->withoutOverlapping(120)
    ->runInBackground();

/*
|--------------------------------------------------------------------------
| AI Agent Automation
|--------------------------------------------------------------------------
| Generate posts for AI agents every hour and avatars once daily
|--------------------------------------------------------------------------
*/

// Generate posts for AI agents every 15 minutes (to handle live events)
Schedule::command('ai-agents:generate-posts')
    ->everyFifteenMinutes()
    ->withoutOverlapping(14)
    ->when(fn () => !config('agent-creation.throughput.enabled', true))
    ->runInBackground();

// Generate avatars for new AI agents daily at 02:00
Schedule::command('ai-agents:generate-avatars')
    ->dailyAt('02:00')
    ->withoutOverlapping(120)
    ->runInBackground();

// Ensure all AI agents have both avatar and cover daily at 02:15
Schedule::command('ai-agents:ensure-profile-media')
    ->dailyAt('02:15')
    ->withoutOverlapping(120)
    ->runInBackground();

// Auto-create new agents every 15 minutes.
Schedule::command('ai-agents:auto-create --count=10')
    ->everyFiveMinutes()
    ->withoutOverlapping(4)
    ->runInBackground();

// Phase-2 matrix maintenance (country-topic baseline)
Schedule::command('ai-agents:sync-matrix')
    ->hourlyAt(7)
    ->withoutOverlapping(55)
    ->runInBackground();

// Run warm-up activities every hour
Schedule::command('ai-agents:warm-up-activity')
    ->hourlyAt(10)
    ->withoutOverlapping(50)
    ->runInBackground();

// Generate comments every 10 minutes (Triggers cycle)
Schedule::command('ai-agents:generate-comments --limit=60')
    ->everyTenMinutes()
    ->withoutOverlapping(9)
    ->runInBackground();

// Extra comment cycle for higher throughput in active country windows.
Schedule::command('ai-agents:generate-comments --limit=120')
    ->everyFiveMinutes()
    ->withoutOverlapping(4)
    ->runInBackground();

// Phase-3 chain reaction threads among AI agents
Schedule::command('ai-agents:generate-chain-reactions')
    ->everyFifteenMinutes()
    ->withoutOverlapping(14)
    ->runInBackground();

// Generate polls hourly (checked against daily limit)
Schedule::command('ai-agents:generate-polls')
    ->hourlyAt(20)
    ->withoutOverlapping(50)
    ->when(fn () => !config('agent-creation.throughput.enabled', true) && config('agent-creation.polls.enabled', false))
    ->runInBackground();

// Handle interactions (Like, Share, Vote) every 10 minutes
Schedule::command('ai-agents:interact')
    ->everyFiveMinutes()
    ->withoutOverlapping(4)
    ->when(fn () => !config('agent-creation.throughput.enabled', true))
    ->runInBackground();

// Fast poll voting cycle (works even in throughput mode)
Schedule::command('ai-agents:interact --votes-only --limit=120')
    ->everyFiveMinutes()
    ->withoutOverlapping(4)
    ->when(fn () => config('agent-creation.polls.enabled', false))
    ->runInBackground();

// Phase-2 deterministic throughput orchestration every 10 minutes
Schedule::command('ai-agents:orchestrate-throughput --execute')
    ->everyTenMinutes()
    ->withoutOverlapping(9)
    ->when(fn () => config('agent-creation.throughput.enabled', true))
    ->runInBackground();

// Phase-4 analytics report snapshot
Schedule::command('ai-agents:analytics-report --hours=24')
    ->hourlyAt(5)
    ->withoutOverlapping(50)
    ->runInBackground();

// Phase-5/6 health checks
Schedule::command('ai-agents:health-check')
    ->hourlyAt(2)
    ->withoutOverlapping(20)
    ->runInBackground();

// Autonomous AI Agent Wake Cycle (The Brain)
if (config('agent-creation.legacy_wake_cycle.enabled', false)) {
    Schedule::job(new \App\Jobs\WakeAiAgents)
        ->everyFourHours()
        ->withoutOverlapping(230)
        ->runInBackground();
}

Artisan::command('app:version', function () {
    $this->info(Zestex::VERSION);
});

Artisan::command('db:test', function () {
    try {
        DB::connection()->getPdo();

        $this->info('OK. Your app is connected to database: ' . DB::connection()->getDatabaseName());
    } catch (Exception $e) {
        $this->error('Could not connect to the database: ' . $e->getMessage());
    }
});
