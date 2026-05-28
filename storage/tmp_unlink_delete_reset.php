<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$ids = DB::table('posts')
  ->where('is_ai_generated',1)
  ->where('content','like','%Fresh reset started now%')
  ->pluck('id');

echo 'target_ids=' . $ids->count() . PHP_EOL;

if ($ids->count() > 0) {
  $unlinked = DB::table('posts')
    ->whereIn('quote_post_id', $ids)
    ->update([
      'quote_post_id' => null,
      'is_quoting' => 0,
      'updated_at' => now(),
    ]);

  $deleted = DB::table('posts')->whereIn('id', $ids)->delete();

  echo 'unlinked=' . $unlinked . PHP_EOL;
  echo 'deleted=' . $deleted . PHP_EOL;
}

$remaining = DB::table('posts')->where('is_ai_generated',1)->where('content','like','%Fresh reset started now%')->count();
$top = DB::table('posts')
  ->where('is_ai_generated',1)
  ->where('status','active')
  ->where('created_at','>=',now()->subHour())
  ->select(DB::raw('count(*) c'))
  ->groupBy('content')
  ->orderByDesc('c')
  ->first();

echo 'remaining=' . $remaining . PHP_EOL;
echo 'top_dup=' . (int)($top->c ?? 0) . PHP_EOL;
