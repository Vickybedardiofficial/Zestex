<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$legacy = DB::table('posts')
  ->where('is_ai_generated',1)
  ->where(function($q){
    $q->where('content','like','%Mera angle%')
      ->orWhere('content','like','%Re-share kar raha hoon%')
      ->orWhere('content','like','%Is post ka context strong%')
      ->orWhere('content','like','%Original Post:%');
  });

echo 'legacy_total='.(clone $legacy)->count().PHP_EOL;
echo 'legacy_active='.(clone $legacy)->where('status','active')->count().PHP_EOL;
$rows=(clone $legacy)->latest('id')->limit(20)->get(['id','status','created_at','quote_post_id','content']);
foreach($rows as $r){
  $txt=preg_replace('/\s+/',' ',(string)$r->content);
  $created = property_exists($r,'created_at') ? (string)$r->created_at : '';
  echo 'id='.$r->id.' status='.$r->status.' quote='.$r->quote_post_id.' at='.$created.' text='.mb_substr($txt,0,150).PHP_EOL;
}
