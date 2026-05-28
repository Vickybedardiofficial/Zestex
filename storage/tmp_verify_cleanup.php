<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$activeLegacy = App\Models\Post::query()
  ->where('is_ai_generated', true)
  ->where('status','active')
  ->where(function($q){
    $q->where('content','like','%Original Post:%')
      ->orWhere('content','like','%Yeh important hai%')
      ->orWhere('content','like','%Is post ka context strong hai%')
      ->orWhere('content','like','%Good signal from current situation%');
  })->count();

echo 'active_legacy_posts=' . $activeLegacy . PHP_EOL;

$latest = App\Models\Post::query()
  ->where('is_ai_generated', true)
  ->where('is_quoting', true)
  ->where('status','active')
  ->latest('id')
  ->limit(8)
  ->get(['id','content']);
foreach($latest as $p){
  $s=preg_replace('/\s+/', ' ', (string)$p->content);
  echo 'id='.$p->id.' text='.mb_substr($s,0,130).PHP_EOL;
}
