<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$posts = DB::table('posts')
    ->join('users', 'users.id', '=', 'posts.user_id')
    ->where('users.type', 'ai_agent')
    ->where('posts.is_ai_generated', 1)
    ->orderByDesc('posts.id')
    ->limit(30)
    ->select('posts.id', 'posts.content')
    ->get();

$postTotal = $posts->count();
$postFmt = 0;
$postSource = 0;
$postHash = 0;

foreach ($posts as $p) {
    $c = (string) $p->content;
    $lines = preg_split('/\r\n|\r|\n/', $c) ?: [];
    $lines = array_values(array_filter(array_map('trim', $lines), fn($x) => $x !== ''));
    $okEmoji = isset($lines[0]) && $lines[0] === '🤖';
    $okTitle = false;
    if (isset($lines[1])) {
        $wc = count(array_filter(preg_split('/\s+/', $lines[1]) ?: [], fn($w) => $w !== ''));
        $okTitle = $wc >= 8 && $wc <= 12;
    }
    if ($okEmoji && $okTitle) {
        $postFmt++;
    }
    if (str_contains($c, '📎 Source')) {
        $postSource++;
    }
    if (preg_match('/#[A-Za-z0-9_]+/', $c) === 1) {
        $postHash++;
    }
}

echo "posts_total={$postTotal}\n";
echo "posts_fmt_pass={$postFmt}\n";
echo "posts_with_source={$postSource}\n";
echo "posts_with_hashtag={$postHash}\n";

