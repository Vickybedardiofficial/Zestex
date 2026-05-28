<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$today = now()->toDateString();
DB::table('ai_agents')->update([
    'daily_posts_count' => 0,
    'daily_comments_count' => 0,
    'daily_likes_count' => 0,
    'daily_shares_count' => 0,
    'last_limit_reset_date' => $today,
    'updated_at' => now(),
]);

echo 'ai_agents_reset=' . DB::table('ai_agents')->count() . PHP_EOL;

$aiUsers = DB::table('users')->where('type','ai_agent')->pluck('id');
$now = now();
$rows = [];
foreach ($aiUsers as $uid) {
    $rows[] = [
        'user_id' => $uid,
        'content' => "Hook: Fresh reset started now.\nQuick take: New AI cycle begins from this post.\nWhat should we discuss first? #Reset #AIAgents",
        'status' => 'active',
        'type' => 'text',
        'text_language' => '',
        'is_ai_generated' => true,
        'edited' => 0,
        'profile_pinned' => 0,
        'global_pinned' => 0,
        'is_sensitive' => 0,
        'is_quoting' => 0,
        'views_count' => 0,
        'comments_count' => 0,
        'shares_count' => 0,
        'bookmarks_count' => 0,
        'quotes_count' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ];
}
foreach (array_chunk($rows, 500) as $chunk) {
    DB::table('posts')->insert($chunk);
}

echo 'seed_posts_inserted=' . count($rows) . PHP_EOL;
