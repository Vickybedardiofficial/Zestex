<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$mask = function($v){
  $v = (string)$v;
  if($v==='') return 'EMPTY';
  if(strlen($v)<=8) return str_repeat('*', strlen($v));
  return substr($v,0,4) . str_repeat('*', max(0, strlen($v)-8)) . substr($v,-4);
};

$pexels = env('PEXELS_API_KEY');
$unsplash = env('UNSPLASH_ACCESS_KEY');
$dbP = App\Models\AdminSetting::where('key','pexels_api_key')->value('value');
$dbU = App\Models\AdminSetting::where('key','unsplash_api_key')->value('value');

echo 'env_pexels=' . $mask($pexels) . PHP_EOL;
echo 'env_unsplash=' . $mask($unsplash) . PHP_EOL;
echo 'db_pexels=' . $mask($dbP) . PHP_EOL;
echo 'db_unsplash=' . $mask($dbU) . PHP_EOL;
