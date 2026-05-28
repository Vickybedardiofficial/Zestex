<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$q = App\Models\Post::query()
    ->where('is_ai_generated', true)
    ->where('status', 'active')
    ->where(function($x){
        $x->where('content','like','%Original Post:%')
          ->orWhere('content','like','%Yeh important hai%')
          ->orWhere('content','like','%Zyada logon tak pahunchna chahiye%')
          ->orWhere('content','like','%Is post ka context strong hai%')
          ->orWhere('content','like','%Good signal from current situation%');
    });

$count = (clone $q)->count();
$updated = $q->update(['status' => 'draft']);

echo "legacy_active_found={$count}" . PHP_EOL;
echo "legacy_marked_draft={$updated}" . PHP_EOL;
