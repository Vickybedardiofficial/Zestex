<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$aiUserIds = App\Models\AiAgent::pluck('user_id');
$aiPostIds = App\Models\Post::whereIn('user_id', $aiUserIds)->pluck('id');

$aiCommentsOnAi = App\Models\Comment::whereIn('user_id', $aiUserIds)->whereIn('post_id', $aiPostIds)->count();
$totalAiPosts = App\Models\Post::whereIn('user_id',$aiUserIds)->count();
$totalAiComments = App\Models\Comment::whereIn('user_id',$aiUserIds)->count();

echo 'total_ai_posts=' . $totalAiPosts . PHP_EOL;
echo 'total_ai_comments=' . $totalAiComments . PHP_EOL;
echo 'ai_comments_on_ai_posts=' . $aiCommentsOnAi . PHP_EOL;

$top = Illuminate\Support\Facades\DB::table('posts')
  ->where('is_ai_generated',1)
  ->where('status','active')
  ->where('created_at','>=',now()->subHour())
  ->select('content', Illuminate\Support\Facades\DB::raw('count(*) c'))
  ->groupBy('content')
  ->orderByDesc('c')
  ->limit(3)
  ->get();

foreach($top as $t){
  $txt = preg_replace('/\s+/', ' ', (string)$t->content);
  echo 'dup=' . $t->c . ' text=' . mb_substr($txt,0,110) . PHP_EOL;
}
