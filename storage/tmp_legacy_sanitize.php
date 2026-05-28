<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$q = App\Models\Post::query()
  ->where('is_ai_generated', true)
  ->where(function($x){
    $x->where('content','like','%Original Post:%')
      ->orWhere('content','like','%Yeh important hai%')
      ->orWhere('content','like','%Zyada logon tak pahunchna chahiye%')
      ->orWhere('content','like','%Is post ka context strong hai%')
      ->orWhere('content','like','%Good signal from current situation%');
  });

$total=(clone $q)->count();
$updated=(clone $q)->update([
  'content' => '[Archived legacy auto-share content removed]',
  'status' => 'deleted',
  'updated_at' => now(),
]);

echo 'legacy_total='.$total.PHP_EOL;
echo 'legacy_updated='.$updated.PHP_EOL;

$remaining = App\Models\Post::query()
  ->where('is_ai_generated', true)
  ->where(function($x){
    $x->where('content','like','%Original Post:%')
      ->orWhere('content','like','%Yeh important hai%')
      ->orWhere('content','like','%Zyada logon tak pahunchna chahiye%')
      ->orWhere('content','like','%Is post ka context strong hai%')
      ->orWhere('content','like','%Good signal from current situation%');
  })->count();

echo 'legacy_remaining='.$remaining.PHP_EOL;
