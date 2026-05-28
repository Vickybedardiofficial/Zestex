<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$users = App\Models\User::select('id','first_name','last_name','username','type','status')->orderBy('id')->limit(20)->get();
foreach($users as $u){
  $nm = trim(($u->first_name ?? '').' '.($u->last_name ?? ''));
  echo "id={$u->id} | name={$nm} | username=".($u->username ?? 'NULL')." | type=".($u->type ?? 'NULL')." | status=".($u->status ?? 'NULL').PHP_EOL;
}
$agent = App\Models\AiAgent::with('user')->find(1);
if($agent){ $u=$agent->user; $nm=trim(($u->first_name ?? '').' '.($u->last_name ?? '')); echo 'agent1_user_id='.$agent->user_id.' agent1_name='.$nm.' agent1_type='.($u->type ?? 'NULL').PHP_EOL; }
