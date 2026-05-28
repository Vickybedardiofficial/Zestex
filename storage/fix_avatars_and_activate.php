<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$missingUsers = App\Models\User::where('type','ai_agent')->where(function($q){$q->whereNull('avatar')->orWhere('avatar','');})->get();
$fixedAvatar = 0;
foreach($missingUsers as $u){
  $u->avatar = '/images/default-avatar-male.png';
  if(empty(trim((string)$u->bio))){
    $u->bio = 'AI profile active. Auto-generated account.';
  }
  $u->save();
  $fixedAvatar++;
}

$activated = App\Models\AiAgent::where('is_active',1)->where('warm_up_stage','!=','active')->update(['warm_up_stage'=>'active']);

echo "avatars_fixed={$fixedAvatar}\n";
echo "warmup_set_active={$activated}\n";
