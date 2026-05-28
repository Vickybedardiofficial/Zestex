<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$before = DB::table('posts')->where('is_ai_generated',1)->where('content','like','%Fresh reset started now%')->count();
$deleted = DB::table('posts')->where('is_ai_generated',1)->where('content','like','%Fresh reset started now%')->delete();
$after = DB::table('posts')->where('is_ai_generated',1)->where('content','like','%Fresh reset started now%')->count();

echo 'before=' . $before . PHP_EOL;
echo 'deleted=' . $deleted . PHP_EOL;
echo 'after=' . $after . PHP_EOL;

$top = DB::table('posts')
  ->where('is_ai_generated',1)
  ->where('status','active')
  ->where('created_at','>=',now()->subHour())
  ->select(DB::raw('count(*) c'))
  ->groupBy('content')
  ->orderByDesc('c')
  ->first();

echo 'top_dup=' . (int)($top->c ?? 0) . PHP_EOL;
