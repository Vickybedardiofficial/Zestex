<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$toVal = function($v){ return is_object($v) && property_exists($v,'value') ? $v->value : (string)($v ?? 'NULL'); };
$users = App\Models\User::where('type', App\Enums\User\UserType::AI_AGENT)->orderBy('id')->get();
foreach($users as $u){
  $nm = trim(($u->first_name ?? '').' '.($u->last_name ?? ''));
  echo "user_id={$u->id} | {$nm} | username={$u->username} | bio_len=".strlen((string)$u->bio)." | avatar=".(empty($u->avatar)?'NULL':'SET').PHP_EOL;
}
