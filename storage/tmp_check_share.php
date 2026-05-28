<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = App\Models\Post::query()
    ->where('is_ai_generated', true)
    ->where('is_quoting', true)
    ->latest('id')
    ->limit(12)
    ->get(['id','created_at','quote_post_id','content']);

foreach ($rows as $r) {
    $one = preg_replace('/\s+/', ' ', (string) $r->content);
    $one = mb_substr($one, 0, 180);
    $created = $r->getRawOriginal('created_at');
    echo "id={$r->id} at={$created} quote={$r->quote_post_id} text={$one}" . PHP_EOL;
}

echo "old_phrase_last1h=" . App\Models\Post::where('created_at','>=',now()->subHour())
    ->where('is_ai_generated', true)
    ->where(function($q){
        $q->where('content','like','%Yeh important hai%')
          ->orWhere('content','like','%Original Post:%')
          ->orWhere('content','like','%Good signal from current situation%')
          ->orWhere('content','like','%Is post ka context strong hai%');
    })->count() . PHP_EOL;
