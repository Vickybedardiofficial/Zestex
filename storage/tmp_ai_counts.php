<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$aiUserIds = App\Models\AiAgent::pluck('user_id');
echo 'ai_users=' . $aiUserIds->count() . PHP_EOL;
echo 'ai_posts=' . App\Models\Post::whereIn('user_id', $aiUserIds)->count() . PHP_EOL;
echo 'ai_comments=' . App\Models\Comment::whereIn('user_id', $aiUserIds)->count() . PHP_EOL;
echo 'ai_post_polls=' . App\Models\PostPoll::whereIn('post_id', App\Models\Post::whereIn('user_id',$aiUserIds)->pluck('id'))->count() . PHP_EOL;
