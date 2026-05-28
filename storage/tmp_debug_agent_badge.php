<?php
$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Post;

$usersByType = User::query()->select('type')->selectRaw('COUNT(*) as c')->groupBy('type')->pluck('c','type')->toArray();
echo "users_by_type=" . json_encode($usersByType) . PHP_EOL;

$latest = Post::query()->with('user:id,username,type')->latest('id')->limit(20)->get(['id','user_id','content']);
foreach ($latest as $p) {
  $type = $p->user?->type?->value ?? (string)($p->user?->type ?? 'null');
  $first = trim((string)$p->content);
  $first = preg_replace('/\s+/', ' ', $first);
  $first = mb_substr($first,0,80);
  echo "post#{$p->id} user={$p->user?->username} type={$type} text={$first}" . PHP_EOL;
}
