<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$legacy = App\Models\Post::query()
  ->where('is_ai_generated', true)
  ->where(function($q){
    $q->where('content','like','%Original Post:%')
      ->orWhere('content','like','%Yeh important hai%')
      ->orWhere('content','like','%Zyada logon tak pahunchna chahiye%')
      ->orWhere('content','like','%Is post ka context strong hai%')
      ->orWhere('content','like','%Good signal from current situation%');
  });

echo 'legacy_total='.(clone $legacy)->count().PHP_EOL;
echo 'legacy_active='.(clone $legacy)->where('status','active')->count().PHP_EOL;
$rows=(clone $legacy)->latest('id')->limit(10)->get(['id','status','created_at','content']);
foreach($rows as $r){
  $txt=preg_replace('/\s+/',' ',(string)$r->content);
  echo 'id='.$r->id.' status='.$r->status.' at='.$r->getRawOriginal('created_at').' text='.mb_substr($txt,0,140).PHP_EOL;
}
