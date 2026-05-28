<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$aiUserIds = App\Models\AiAgent::pluck('user_id');
$aiPostIds = App\Models\Post::whereIn('user_id', $aiUserIds)->pluck('id');

echo 'new_ai_posts=' . $aiPostIds->count() . PHP_EOL;
echo 'ai_comments_on_ai_posts=' . App\Models\Comment::whereIn('user_id', $aiUserIds)->whereIn('post_id', $aiPostIds)->count() . PHP_EOL;

echo 'latest_ai_posts=' . App\Models\Post::whereIn('user_id', $aiUserIds)->count() . PHP_EOL;
