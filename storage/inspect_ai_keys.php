<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$mask=function($v){$v=(string)$v; if($v==='')return 'EMPTY'; if(strlen($v)<=8)return str_repeat('*',strlen($v)); return substr($v,0,4).str_repeat('*',strlen($v)-8).substr($v,-4);} ;
$rows=Illuminate\Support\Facades\DB::table('admin_settings')->where('key','like','%api_key%')->orderBy('key')->get();
foreach($rows as $r){ echo $r->key.'='.$mask($r->value).PHP_EOL; }

$envFile = file(base_path('.env'));
$targets = ['PEXELS_API_KEY','UNSPLASH_ACCESS_KEY'];
foreach($targets as $t){
  $matches = array_values(array_filter($envFile, fn($line)=>str_starts_with(trim($line), $t.'=')));
  echo $t.'_lines='.count($matches).PHP_EOL;
  foreach($matches as $line){
    $parts = explode('=', trim($line), 2);
    $val = $parts[1] ?? '';
    echo '  '. $t . '=' . $mask(trim($val, "\"")) . PHP_EOL;
  }
}
