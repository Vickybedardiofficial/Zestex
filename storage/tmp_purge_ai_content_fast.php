<?php
ini_set('max_execution_time', '0');
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$driver = DB::connection()->getDriverName();
if ($driver !== 'mysql') {
    echo "Unsupported driver: {$driver}\n";
    exit(1);
}

$countsBefore = [
    'ai_posts' => DB::table('posts')->join('users', 'posts.user_id', '=', 'users.id')->where('users.type', 'ai_agent')->count(),
    'ai_comments' => DB::table('comments')->join('users', 'comments.user_id', '=', 'users.id')->where('users.type', 'ai_agent')->count(),
    'ai_polls' => DB::table('post_polls')->join('posts', 'post_polls.post_id', '=', 'posts.id')->join('users', 'posts.user_id', '=', 'users.id')->where('users.type', 'ai_agent')->count(),
];

foreach ($countsBefore as $k => $v) {
    echo "before_{$k}={$v}\n";
}

DB::beginTransaction();
try {
    $nullQuoted = DB::affectingStatement("\n        UPDATE posts p\n        INNER JOIN posts q ON p.quote_post_id = q.id\n        INNER JOIN users uq ON q.user_id = uq.id\n        SET p.quote_post_id = NULL, p.is_quoting = 0, p.updated_at = NOW()\n        WHERE uq.type = 'ai_agent'\n    ");

    $delPolls = DB::affectingStatement("\n        DELETE pp FROM post_polls pp\n        INNER JOIN posts p ON pp.post_id = p.id\n        INNER JOIN users u ON p.user_id = u.id\n        WHERE u.type = 'ai_agent'\n    ");

    $delReactionOnAiComments = DB::affectingStatement("\n        DELETE r FROM reactions r\n        INNER JOIN comments c ON r.reactable_id = c.id\n        INNER JOIN users u ON c.user_id = u.id\n        WHERE r.reactable_type = 'App\\\\Models\\\\Comment'\n          AND u.type = 'ai_agent'\n    ");

    $delAiComments = DB::affectingStatement("\n        DELETE c FROM comments c\n        INNER JOIN users u ON c.user_id = u.id\n        WHERE u.type = 'ai_agent'\n    ");

    $delReactionOnAiPosts = DB::affectingStatement("\n        DELETE r FROM reactions r\n        INNER JOIN posts p ON r.reactable_id = p.id\n        INNER JOIN users u ON p.user_id = u.id\n        WHERE r.reactable_type = 'App\\\\Models\\\\Post'\n          AND u.type = 'ai_agent'\n    ");

    $delAiPosts = DB::affectingStatement("\n        DELETE p FROM posts p\n        INNER JOIN users u ON p.user_id = u.id\n        WHERE u.type = 'ai_agent'\n    ");

    DB::commit();

    echo "updated_null_quoted={$nullQuoted}\n";
    echo "deleted_ai_polls={$delPolls}\n";
    echo "deleted_reactions_ai_comments={$delReactionOnAiComments}\n";
    echo "deleted_ai_comments={$delAiComments}\n";
    echo "deleted_reactions_ai_posts={$delReactionOnAiPosts}\n";
    echo "deleted_ai_posts={$delAiPosts}\n";
} catch (Throwable $e) {
    DB::rollBack();
    echo 'error=' . $e->getMessage() . "\n";
    exit(1);
}

$countsAfter = [
    'ai_posts' => DB::table('posts')->join('users', 'posts.user_id', '=', 'users.id')->where('users.type', 'ai_agent')->count(),
    'ai_comments' => DB::table('comments')->join('users', 'comments.user_id', '=', 'users.id')->where('users.type', 'ai_agent')->count(),
    'ai_polls' => DB::table('post_polls')->join('posts', 'post_polls.post_id', '=', 'posts.id')->join('users', 'posts.user_id', '=', 'users.id')->where('users.type', 'ai_agent')->count(),
];

foreach ($countsAfter as $k => $v) {
    echo "after_{$k}={$v}\n";
}
