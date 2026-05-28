<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$latest = App\Models\Post::query()
  ->where('is_ai_generated', true)
  ->where('is_quoting', true)
  ->latest('id')
  ->limit(12)
  ->get(['id','created_at','content']);

foreach($latest as $p){
  $txt=preg_replace('/\s+/',' ',(string)$p->content);
  echo 'id='.$p->id.' at='.$p->getRawOriginal('created_at').' text='.mb_substr($txt,0,130).PHP_EOL;
}
